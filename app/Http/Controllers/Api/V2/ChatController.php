<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Order;
use App\Services\ChatPresenceService;
use App\Support\Api\VendorApiPresenter;
use App\Support\ChatAttachmentSupport;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends VendorApiController
{
    public function __construct(
        protected ChatPresenceService $presence
    ) {}

    public function index(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        // Listing chats does not mean the chat screen is open — do not mark online here.
        $search = trim((string) $request->input('search', ''));

        $query = $vendor->conversations()
            ->with(['customer', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->whereHas('customer', function ($customer) use ($term) {
                $customer->where('name', 'like', $term)
                    ->orWhere('mobile', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        $chats = $query->paginate($request->integer('per_page', 20));

        $payload = VendorApiPresenter::paginator(
            $chats,
            fn (Conversation $chat) => VendorApiPresenter::chatSummary($chat)
        );

        // Common names / no prior chat: also return booking customers the vendor can start chatting with.
        if ($search !== '') {
            $existingCustomerIds = $vendor->conversations()->pluck('customer_id');

            $startable = Customer::query()
                ->where(function ($q) use ($search) {
                    $term = '%'.$search.'%';
                    $q->where('name', 'like', $term)
                        ->orWhere('mobile', 'like', $term)
                        ->orWhere('email', 'like', $term);
                })
                ->whereIn('id', function ($q) use ($vendor) {
                    $q->select('customer_id')
                        ->from('orders')
                        ->where('vendor_id', $vendor->id)
                        ->where('payment_status', 'success')
                        ->whereNotNull('customer_id');
                })
                ->when($existingCustomerIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $existingCustomerIds))
                ->orderBy('name')
                ->limit(20)
                ->get()
                ->map(fn (Customer $customer) => VendorApiPresenter::chatStartableCustomer($customer))
                ->values()
                ->all();

            $payload['startable_customers'] = $startable;
        }

        return $this->success($payload);
    }

    public function presence(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:online,offline'],
        ]);

        if (($data['status'] ?? 'online') === 'offline') {
            $this->presence->leave(ChatPresenceService::ROLE_VENDOR, (int) $vendor->id);
            $online = false;
        } else {
            $this->presence->touch(ChatPresenceService::ROLE_VENDOR, (int) $vendor->id);
            $online = true;
        }

        return $this->success([
            'is_online' => $online,
            'online_status' => $online ? 'online' : 'offline',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->presence->touch(ChatPresenceService::ROLE_VENDOR, (int) $vendor->id);

        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        $hasBooking = Order::query()
            ->where('vendor_id', $vendor->id)
            ->where('customer_id', $data['customer_id'])
            ->paymentConfirmed()
            ->exists();

        if (! $hasBooking) {
            return $this->error('You can only chat with customers who have a paid booking with you.', 422);
        }

        $conversation = Conversation::query()->firstOrCreate(
            [
                'customer_id' => (int) $data['customer_id'],
                'vendor_id' => $vendor->id,
            ],
            ['last_message_at' => now()]
        );

        if (! empty($data['message'])) {
            $message = $conversation->messages()->create([
                'sender_type' => ChatMessage::SENDER_VENDOR,
                'sender_id' => $vendor->id,
                'body' => $data['message'],
            ]);
            $conversation->update(['last_message_at' => $message->created_at]);
        }

        $conversation->load(['customer', 'latestMessage']);

        return $this->success([
            'chat' => VendorApiPresenter::chatDetail($conversation),
        ], 'Chat ready.', 201);
    }

    public function show(Request $request, Conversation $chat): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsChat($chat, $vendor);
        $this->presence->touch(ChatPresenceService::ROLE_VENDOR, (int) $vendor->id);

        return $this->success(VendorApiPresenter::chatDetail($chat));
    }

    public function messages(Request $request, Conversation $chat): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsChat($chat, $vendor);
        $this->presence->touch(ChatPresenceService::ROLE_VENDOR, (int) $vendor->id);

        $chat->load('customer');

        if ($request->filled('since_id')) {
            $messages = $chat->messages()
                ->where('id', '>', $request->integer('since_id'))
                ->orderBy('id')
                ->get();

            $chat->messages()
                ->where('sender_type', ChatMessage::SENDER_CUSTOMER)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return $this->success([
                'messages' => $messages
                    ->map(fn (ChatMessage $message) => VendorApiPresenter::chatMessage($message))
                    ->values()
                    ->all(),
            ]);
        }

        $messages = $chat->messages()
            ->orderBy('id')
            ->paginate($request->integer('per_page', 50));

        $chat->messages()
            ->where('sender_type', ChatMessage::SENDER_CUSTOMER)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success([
            'chat' => VendorApiPresenter::chatDetail($chat),
            ...VendorApiPresenter::paginator($messages, fn (ChatMessage $message) => VendorApiPresenter::chatMessage($message)),
        ]);
    }

    public function sendMessage(Request $request, Conversation $chat): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsChat($chat, $vendor);
        $this->presence->touch(ChatPresenceService::ROLE_VENDOR, (int) $vendor->id);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ChatAttachmentSupport::validationRules(true),
        ]);

        abort_if(empty($data['body']) && ! $request->hasFile('attachment'), 422, 'Message body or attachment is required.');

        $message = $chat->messages()->create([
            'sender_type' => ChatMessage::SENDER_VENDOR,
            'sender_id' => $vendor->id,
            'body' => $data['body'] ?? null,
            'attachment_path' => $request->hasFile('attachment')
                ? StoresUploadedFiles::store($request->file('attachment'), 'chat/attachments')
                : null,
            'attachment_name' => $request->file('attachment')?->getClientOriginalName(),
        ]);

        $chat->update(['last_message_at' => $message->created_at]);

        return $this->success([
            'message' => VendorApiPresenter::chatMessage($message),
        ], 'Message sent.', 201);
    }
}

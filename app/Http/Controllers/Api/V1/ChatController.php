<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Vendor;
use App\Support\Api\CustomerApiPresenter;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $query = $customer->conversations()
            ->with(['vendor', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->whereHas('vendor', function ($vendor) use ($term) {
                $vendor->where('brand_name', 'like', $term)
                    ->orWhere('shop_name', 'like', $term);
            });
        }

        $conversations = $query->paginate($request->integer('per_page', 20));

        return $this->success(
            CustomerApiPresenter::paginator($conversations, fn (Conversation $conversation) => CustomerApiPresenter::chatSummary($conversation))
        );
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $data = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        $vendor = Vendor::query()->active()->findOrFail($data['vendor_id']);

        $conversation = Conversation::query()->firstOrCreate(
            [
                'customer_id' => $customer->id,
                'vendor_id' => $vendor->id,
            ],
            ['last_message_at' => now()]
        );

        if (! empty($data['message'])) {
            $this->createMessage($conversation, $customer->id, $data['message']);
            $conversation->refresh();
        }

        $conversation->load(['vendor', 'latestMessage']);

        return $this->success([
            'chat' => CustomerApiPresenter::chatSummary($conversation),
        ], 'Chat ready.', 201);
    }

    public function messages(Request $request, Conversation $chat): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($chat->customer_id === $customer->id, 403);

        $chat->load('vendor');

        $messages = $chat->messages()
            ->orderBy('id')
            ->paginate($request->integer('per_page', 50));

        $chat->messages()
            ->where('sender_type', ChatMessage::SENDER_VENDOR)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success([
            'chat' => CustomerApiPresenter::chatSummary($chat),
            ...CustomerApiPresenter::paginator($messages, fn (ChatMessage $message) => CustomerApiPresenter::chatMessage($message)),
        ]);
    }

    public function sendMessage(Request $request, Conversation $chat): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($chat->customer_id === $customer->id, 403);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096', 'required_without:body'],
        ]);

        abort_if(empty($data['body']) && ! $request->hasFile('attachment'), 422, 'Message body or attachment is required.');

        $message = $this->createMessage(
            $chat,
            $customer->id,
            $data['body'] ?? null,
            $request->file('attachment')
        );

        return $this->success([
            'message' => CustomerApiPresenter::chatMessage($message),
        ], 'Message sent.', 201);
    }

    protected function createMessage(
        Conversation $conversation,
        int $customerId,
        ?string $body,
        $attachment = null
    ): ChatMessage {
        $message = $conversation->messages()->create([
            'sender_type' => ChatMessage::SENDER_CUSTOMER,
            'sender_id' => $customerId,
            'body' => $body,
            'attachment_path' => $attachment
                ? StoresUploadedFiles::store($attachment, 'chat/attachments')
                : null,
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        return $message;
    }
}

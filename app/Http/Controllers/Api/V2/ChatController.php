<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Support\Api\VendorApiPresenter;
use App\Support\ChatAttachmentSupport;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends VendorApiController
{
    public function index(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        $query = $vendor->conversations()
            ->with(['customer', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->whereHas('customer', fn ($customer) => $customer->where('name', 'like', $term));
        }

        $chats = $query->paginate($request->integer('per_page', 20));

        return $this->success(
            VendorApiPresenter::paginator($chats, fn (Conversation $chat) => VendorApiPresenter::chatSummary($chat))
        );
    }

    public function show(Request $request, Conversation $chat): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsChat($chat, $vendor);

        return $this->success(VendorApiPresenter::chatDetail($chat));
    }

    public function messages(Request $request, Conversation $chat): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsChat($chat, $vendor);

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
        ]);

        $chat->update(['last_message_at' => $message->created_at]);

        return $this->success([
            'message' => VendorApiPresenter::chatMessage($message),
        ], 'Message sent.', 201);
    }
}

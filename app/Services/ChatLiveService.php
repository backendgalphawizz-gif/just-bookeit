<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Support\WebChatLivePresenter;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatLiveService
{
    /**
     * @param  Builder<Conversation>|Relation  $conversationsQuery
     * @param  Closure(Conversation): array<string, mixed>  $threadPresenter
     * @param  Closure(Conversation): void  $authorizeChat
     */
    public function poll(
        Request $request,
        Builder|Relation $conversationsQuery,
        string $viewerRole,
        Closure $threadPresenter,
        Closure $authorizeChat,
    ): JsonResponse {
        $data = $request->validate([
            'chat_id' => ['nullable', 'integer'],
            'after_message_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $afterId = (int) ($data['after_message_id'] ?? 0);

        $threads = (clone $conversationsQuery)
            ->with(['customer', 'vendor', 'latestMessage'])
            ->orderByRaw('last_message_at is null')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Conversation $conversation) => $threadPresenter($conversation))
            ->values()
            ->all();

        $messages = [];

        if (! empty($data['chat_id'])) {
            $chat = Conversation::query()->findOrFail((int) $data['chat_id']);
            $authorizeChat($chat);

            $incomingSender = $viewerRole === ChatMessage::SENDER_VENDOR
                ? ChatMessage::SENDER_CUSTOMER
                : ChatMessage::SENDER_VENDOR;

            $chat->messages()
                ->where('sender_type', $incomingSender)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $messages = $chat->messages()
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->get()
                ->map(fn (ChatMessage $message) => WebChatLivePresenter::message($message, $viewerRole))
                ->values()
                ->all();
        }

        return response()->json([
            'messages' => $messages,
            'threads' => $threads,
        ]);
    }
}

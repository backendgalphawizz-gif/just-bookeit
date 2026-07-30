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
    public function __construct(
        protected ChatReadReceiptService $readReceipts,
    ) {}

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
            'include_threads' => ['nullable', 'boolean'],
        ]);

        $afterId = (int) ($data['after_message_id'] ?? 0);
        $includeThreads = ! array_key_exists('include_threads', $data)
            || filter_var($data['include_threads'], FILTER_VALIDATE_BOOLEAN);

        $threads = [];

        if ($includeThreads) {
            $threads = (clone $conversationsQuery)
                ->with(['customer', 'vendor', 'latestMessage'])
                ->orderByRaw('last_message_at is null')
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->get()
                ->map(fn (Conversation $conversation) => $threadPresenter($conversation))
                ->values()
                ->all();
        }

        $messages = [];
        $readIds = [];

        if (! empty($data['chat_id'])) {
            $chat = Conversation::query()->findOrFail((int) $data['chat_id']);
            $authorizeChat($chat);

            $this->readReceipts->markIncomingAsRead($chat, $viewerRole);

            $messages = $chat->messages()
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->get()
                ->map(fn (ChatMessage $message) => WebChatLivePresenter::message($message, $viewerRole))
                ->values()
                ->all();

            // Own messages already on screen that the peer has read (tick updates).
            $readIds = $chat->messages()
                ->where('sender_type', $viewerRole)
                ->whereNotNull('read_at')
                ->when($afterId > 0, fn ($query) => $query->where('id', '<=', $afterId))
                ->orderByDesc('id')
                ->limit(100)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return response()->json([
            'messages' => $messages,
            'threads' => $threads,
            'read_ids' => $readIds,
        ]);
    }
}

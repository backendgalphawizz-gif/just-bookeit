<?php

namespace App\Services;

use App\Events\ChatMessagesRead;
use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatReadReceiptService
{
    /**
     * Mark peer messages as read and broadcast receipt IDs to the sender.
     *
     * @return list<int>
     */
    public function markIncomingAsRead(Conversation $chat, string $viewerRole): array
    {
        $incomingSender = $viewerRole === ChatMessage::SENDER_VENDOR
            ? ChatMessage::SENDER_CUSTOMER
            : ChatMessage::SENDER_VENDOR;

        $ids = $chat->messages()
            ->where('sender_type', $incomingSender)
            ->whereNull('read_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($ids === []) {
            return [];
        }

        $chat->messages()
            ->whereIn('id', $ids)
            ->update(['read_at' => now()]);

        $this->safeBroadcast(fn () => broadcast(new ChatMessagesRead(
            (int) $chat->id,
            $ids,
            $viewerRole,
            (int) $chat->customer_id,
            (int) $chat->vendor_id,
        ))->toOthers());

        return $ids;
    }

    protected function safeBroadcast(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            Log::warning('Chat read receipt broadcast failed: '.$e->getMessage(), [
                'exception' => $e::class,
            ]);
        }
    }
}

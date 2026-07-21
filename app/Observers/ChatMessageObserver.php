<?php

namespace App\Observers;

use App\Events\ChatMessageCreated;
use App\Events\ChatMessageDeleted;
use App\Events\ChatMessageUpdated;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Services\AppPushNotificationService;

class ChatMessageObserver
{
    public function created(ChatMessage $message): void
    {
        app(AppPushNotificationService::class)->chatMessageCreated($message);

        broadcast(new ChatMessageCreated($message))->toOthers();
    }

    public function updated(ChatMessage $message): void
    {
        if ($message->wasChanged(['body', 'edited_at', 'attachment_path', 'attachment_name'])) {
            broadcast(new ChatMessageUpdated($message->fresh()))->toOthers();
        }
    }

    public function deleted(ChatMessage $message): void
    {
        $conversation = Conversation::query()
            ->with(['customer', 'vendor', 'latestMessage'])
            ->find($message->conversation_id);

        broadcast(new ChatMessageDeleted(
            (int) $message->conversation_id,
            (int) $message->id,
            $conversation,
        ))->toOthers();
    }
}

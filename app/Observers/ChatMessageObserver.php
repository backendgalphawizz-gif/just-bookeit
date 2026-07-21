<?php

namespace App\Observers;

use App\Events\ChatMessageCreated;
use App\Events\ChatMessageDeleted;
use App\Events\ChatMessageUpdated;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Services\AppPushNotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatMessageObserver
{
    public function created(ChatMessage $message): void
    {
        app(AppPushNotificationService::class)->chatMessageCreated($message);

        $this->safeBroadcast(fn () => broadcast(new ChatMessageCreated($message))->toOthers());
    }

    public function updated(ChatMessage $message): void
    {
        if ($message->wasChanged(['body', 'edited_at', 'attachment_path', 'attachment_name'])) {
            $fresh = $message->fresh();
            if ($fresh) {
                $this->safeBroadcast(fn () => broadcast(new ChatMessageUpdated($fresh))->toOthers());
            }
        }
    }

    public function deleted(ChatMessage $message): void
    {
        $conversation = Conversation::query()
            ->with(['customer', 'vendor', 'latestMessage'])
            ->find($message->conversation_id);

        $this->safeBroadcast(fn () => broadcast(new ChatMessageDeleted(
            (int) $message->conversation_id,
            (int) $message->id,
            $conversation,
        ))->toOthers());
    }

    /** Never fail the HTTP request if Reverb is down / misconfigured. */
    protected function safeBroadcast(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            Log::warning('Chat broadcast failed: '.$e->getMessage(), [
                'exception' => $e::class,
            ]);
        }
    }
}

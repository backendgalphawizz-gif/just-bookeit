<?php

namespace App\Observers;

use App\Models\ChatMessage;
use App\Services\AppPushNotificationService;

class ChatMessageObserver
{
    public function created(ChatMessage $message): void
    {
        app(AppPushNotificationService::class)->chatMessageCreated($message);
    }
}

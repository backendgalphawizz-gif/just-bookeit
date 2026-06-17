<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Api\Concerns\HandlesNotificationInbox;
use App\Services\NotificationInboxService;
use Illuminate\Http\Request;

class NotificationController extends DriverApiController
{
    use HandlesNotificationInbox;

    protected function notificationRecipientType(): string
    {
        return NotificationInboxService::TYPE_DRIVER;
    }

    protected function notificationRecipientId(Request $request): int
    {
        return $this->driver($request)->id;
    }
}

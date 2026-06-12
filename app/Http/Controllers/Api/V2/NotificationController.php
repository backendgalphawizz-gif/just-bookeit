<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\Concerns\HandlesNotificationInbox;
use App\Models\Vendor;
use App\Services\NotificationInboxService;
use Illuminate\Http\Request;

class NotificationController extends VendorApiController
{
    use HandlesNotificationInbox;

    protected function notificationRecipientType(): string
    {
        return NotificationInboxService::TYPE_VENDOR;
    }

    protected function notificationRecipientId(Request $request): int
    {
        return $this->vendor($request)->id;
    }
}

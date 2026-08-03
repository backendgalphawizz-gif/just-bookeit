<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Services\Admin\AdminInboxNotificationService;

class OrderItemObserver
{
    public function updated(OrderItem $item): void
    {
        if (! $item->wasChanged('status') && ! $item->wasChanged('driver_id')) {
            return;
        }

        $order = $item->order()->first();

        if ($order) {
            app(AdminInboxNotificationService::class)->notifyOrderAwaitingDriver($order);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Api\ApiController;
use App\Models\Driver;
use App\Models\Order;
use App\Support\Api\DriverApiPresenter;
use App\Support\Api\DriverDeliveryTab;
use Illuminate\Http\Request;

abstract class DriverApiController extends ApiController
{
    protected function driver(Request $request): Driver
    {
        /** @var Driver $driver */
        $driver = $request->user();

        return $driver;
    }

    protected function assertOwnsDelivery(Order $order, Driver $driver): void
    {
        abort_unless(DriverApiPresenter::driverOwnsDelivery($order, $driver), 403);
    }

    protected function assertCanAcceptDelivery(Order $order, Driver $driver): void
    {
        if (! DriverApiPresenter::isDispatchReady($order)) {
            abort(422, 'This delivery is not available yet. The vendor must mark the order in progress first.');
        }

        $itemAssignedToDriver = $order->orderItems()
            ->where('driver_id', $driver->id)
            ->whereIn('status', DriverDeliveryTab::activeDeliveryStatuses())
            ->exists();

        if (
            $order->driver_id !== null
            && (int) $order->driver_id !== (int) $driver->id
            && ! $itemAssignedToDriver
        ) {
            abort(422, 'This delivery has already been assigned to another driver.');
        }

        if (
            $order->driver_id === null
            || (
                (int) $order->driver_id === (int) $driver->id
                && $order->driver_delivery_status === null
            )
            || $itemAssignedToDriver
        ) {
            return;
        }

        if (
            (int) $order->driver_id === (int) $driver->id
            && $order->driver_delivery_status === Order::DRIVER_STATUS_ACCEPTED
        ) {
            return;
        }

        abort(422, 'This delivery is no longer available.');
    }
}

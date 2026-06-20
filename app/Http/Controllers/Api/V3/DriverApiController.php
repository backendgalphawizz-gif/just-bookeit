<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Api\ApiController;
use App\Models\Driver;
use App\Models\Order;
use App\Support\OrderDispatchSupport;
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
        abort_unless($order->driver_id === $driver->id, 403);
    }

    protected function assertCanAcceptDelivery(Order $order, Driver $driver): void
    {
        if (! OrderDispatchSupport::isDispatchStatus($order->status)) {
            abort(422, 'This delivery is not available yet. The vendor must mark the order in progress first.');
        }

        if ($order->driver_id !== null && (int) $order->driver_id !== (int) $driver->id) {
            abort(422, 'This delivery has already been assigned to another driver.');
        }

        if (
            $order->driver_id === null
            || (
                (int) $order->driver_id === (int) $driver->id
                && $order->driver_delivery_status === null
            )
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

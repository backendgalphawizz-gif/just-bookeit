<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Api\ApiController;
use App\Models\Driver;
use App\Models\Order;
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

    protected function assertAvailableDelivery(Order $order): void
    {
        abort_unless($order->status === 'in_transit' && $order->driver_id === null, 422, 'This delivery is no longer available.');
    }
}

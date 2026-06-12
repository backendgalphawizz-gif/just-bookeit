<?php

namespace App\Http\Controllers\Api\V3;

use App\Models\Order;
use App\Services\Driver\DriverWalletService;
use App\Support\Api\DriverApiPresenter;
use App\Support\Api\DriverDeliveryTab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DeliveryController extends DriverApiController
{
    public function __construct(
        protected DriverWalletService $wallet
    ) {}

    public function index(Request $request): JsonResponse
    {
        $driver = $this->driver($request);

        $request->validate([
            'tab' => DriverDeliveryTab::validationRule(),
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = DriverDeliveryTab::applyToQuery(
            Order::query()
                ->with(['customer', 'vendor', 'category']),
            $driver,
            $request->input('tab')
        );

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('order_number', 'like', $term)
                    ->orWhere('item_title', 'like', $term)
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', $term));
            });
        }

        $deliveries = $query
            ->orderByDesc('updated_at')
            ->paginate($request->integer('per_page', 10));

        return $this->success(
            DriverApiPresenter::paginator(
                $deliveries,
                fn (Order $order) => DriverApiPresenter::deliverySummary($order, $driver)
            )
        );
    }

    public function show(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);

        abort_unless(
            ($delivery->status === 'in_transit' && $delivery->driver_id === null)
            || $delivery->driver_id === $driver->id,
            403
        );

        $delivery->load(['customer', 'vendor', 'category']);

        return $this->success(DriverApiPresenter::deliveryDetail($delivery, $driver));
    }

    public function accept(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertAvailableDelivery($delivery);

        $delivery->update([
            'driver_id' => $driver->id,
            'driver_delivery_status' => Order::DRIVER_STATUS_ACCEPTED,
            'driver_assigned_at' => now(),
        ]);

        $delivery->ensureDeliveryOtp();

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category']), $driver),
        ], 'Delivery accepted.');
    }

    public function reject(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);

        if ($delivery->driver_id === null) {
            return $this->success(null, 'Delivery skipped.');
        }

        $this->assertOwnsDelivery($delivery, $driver);

        if ($delivery->driver_delivery_status !== Order::DRIVER_STATUS_ACCEPTED) {
            return $this->error('This delivery cannot be rejected.', 422);
        }

        $delivery->update([
            'driver_id' => null,
            'driver_delivery_status' => null,
            'driver_assigned_at' => null,
        ]);

        return $this->success(null, 'Delivery rejected.');
    }

    public function pickup(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertOwnsDelivery($delivery, $driver);

        if ($delivery->driver_delivery_status !== Order::DRIVER_STATUS_ACCEPTED) {
            return $this->error('Pickup is only available after accepting the delivery.', 422);
        }

        $delivery->update([
            'driver_delivery_status' => Order::DRIVER_STATUS_PICKED_UP,
            'driver_pickup_at' => now(),
        ]);

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category']), $driver),
        ], 'Order picked up.');
    }

    public function outForDelivery(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertOwnsDelivery($delivery, $driver);

        if ($delivery->driver_delivery_status !== Order::DRIVER_STATUS_PICKED_UP) {
            return $this->error('Mark pickup before going out for delivery.', 422);
        }

        $delivery->update([
            'driver_delivery_status' => Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
        ]);

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category']), $driver),
        ], 'Out for delivery.');
    }

    public function deliver(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertOwnsDelivery($delivery, $driver);

        if (! in_array($delivery->driver_delivery_status, [
            Order::DRIVER_STATUS_PICKED_UP,
            Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
        ], true)) {
            return $this->error('This delivery is not ready to be completed.', 422);
        }

        $data = $request->validate([
            'delivery_otp' => ['required', 'digits:4'],
        ]);

        if ($delivery->delivery_otp !== $data['delivery_otp']) {
            return $this->error('Invalid delivery OTP.', 422);
        }

        $delivery->update([
            'status' => 'delivered',
            'driver_delivery_status' => null,
            'driver_delivered_at' => now(),
        ]);

        try {
            $this->wallet->creditDeliveryEarning($delivery->fresh(), $driver);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category']), $driver),
        ], 'Delivery completed.');
    }
}

<?php

namespace App\Http\Controllers\Api\V3;

use App\Models\DriverDeliverySkip;
use App\Models\Order;
use App\Services\Driver\DriverWalletService;
use App\Support\Api\DriverApiPresenter;
use App\Support\Api\DriverDeliveryTab;
use App\Support\AppliesListDateFilter;
use App\Support\DriverValidationRules;
use App\Support\StoresUploadedFiles;
use App\Support\OrderDispatchSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DeliveryController extends DriverApiController
{
    use AppliesListDateFilter;

    public function __construct(
        protected DriverWalletService $wallet
    ) {}

    public function index(Request $request): JsonResponse
    {
        $driver = $this->driver($request);

        $request->validate(array_merge([
            'tab' => DriverDeliveryTab::validationRule(),
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'search' => ['nullable', 'string', 'max:100'],
        ], $this->listDateRules()));

        $query = DriverDeliveryTab::applyToQuery(
            Order::query()->with(['customer', 'vendor', 'category']),
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

        $this->applyDateRange($query, $request, 'updated_at');

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
            (OrderDispatchSupport::isDispatchStatus($delivery->status) && $delivery->driver_id === null)
            || $delivery->driver_id === $driver->id,
            403
        );

        $delivery->load(['customer', 'vendor', 'category']);

        return $this->success(DriverApiPresenter::deliveryDetail($delivery, $driver));
    }

    public function accept(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertCanAcceptDelivery($delivery, $driver);

        if (
            (int) $delivery->driver_id === (int) $driver->id
            && $delivery->driver_delivery_status === Order::DRIVER_STATUS_ACCEPTED
        ) {
            return $this->success([
                'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category']), $driver),
            ], 'Delivery already accepted.');
        }

        $delivery->update([
            'driver_id' => $driver->id,
            'driver_delivery_status' => Order::DRIVER_STATUS_ACCEPTED,
            'driver_assigned_at' => now(),
            'driver_rejection_reason' => null,
            'driver_scheduled_for' => null,
            'driver_rescheduled_at' => null,
        ]);

        $delivery->ensureDeliveryOtp();

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category']), $driver),
        ], 'Delivery accepted.');
    }

    public function reject(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $data = $request->validate(DriverValidationRules::deliveryReject());

        if ($delivery->driver_id === null) {
            abort_unless(OrderDispatchSupport::isDispatchStatus($delivery->status), 422, 'This delivery is no longer available.');

            DriverDeliverySkip::query()->updateOrCreate(
                ['driver_id' => $driver->id, 'order_id' => $delivery->id],
                ['reason' => trim($data['reason'])]
            );

            return $this->success(null, 'Delivery rejected.');
        }

        $this->assertOwnsDelivery($delivery, $driver);

        if ($delivery->driver_delivery_status === null) {
            $delivery->update([
                'driver_id' => null,
                'driver_rejection_reason' => trim($data['reason']),
            ]);

            return $this->success(null, 'Delivery rejected.');
        }

        if (! in_array($delivery->driver_delivery_status, [
            Order::DRIVER_STATUS_ACCEPTED,
            Order::DRIVER_STATUS_RESCHEDULED,
        ], true)) {
            return $this->error('This delivery cannot be rejected.', 422);
        }

        $delivery->update([
            'driver_id' => null,
            'driver_delivery_status' => null,
            'driver_assigned_at' => null,
            'driver_pickup_at' => null,
            'driver_scheduled_for' => null,
            'driver_rescheduled_at' => null,
            'driver_rejection_reason' => trim($data['reason']),
        ]);

        return $this->success(null, 'Delivery rejected.');
    }

    public function pickup(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertOwnsDelivery($delivery, $driver);

        if (! in_array($delivery->driver_delivery_status, [
            Order::DRIVER_STATUS_ACCEPTED,
            Order::DRIVER_STATUS_RESCHEDULED,
        ], true)) {
            return $this->error('Pickup is only available after accepting the delivery.', 422);
        }

        $delivery->update([
            'driver_delivery_status' => Order::DRIVER_STATUS_PICKED_UP,
            'driver_pickup_at' => now(),
            'driver_scheduled_for' => null,
            'driver_rescheduled_at' => null,
        ]);

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category']), $driver),
        ], 'Order picked up.');
    }

    public function dispatch(Request $request, Order $delivery): JsonResponse
    {
        return $this->outForDelivery($request, $delivery);
    }

    public function outForDelivery(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertOwnsDelivery($delivery, $driver);

        if (! in_array($delivery->driver_delivery_status, [
            Order::DRIVER_STATUS_PICKED_UP,
            Order::DRIVER_STATUS_RESCHEDULED,
        ], true)) {
            return $this->error('Mark pickup before dispatching for delivery.', 422);
        }

        $updates = [
            'driver_delivery_status' => Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
            'driver_scheduled_for' => null,
            'driver_rescheduled_at' => null,
        ];

        if ($delivery->driver_delivery_status === Order::DRIVER_STATUS_RESCHEDULED && ! $delivery->driver_pickup_at) {
            $updates['driver_pickup_at'] = now();
        }

        $delivery->update($updates);

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category']), $driver),
        ], 'Out for delivery.');
    }

    public function delivered(Request $request, Order $delivery): JsonResponse
    {
        return $this->deliver($request, $delivery);
    }

    public function deliver(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertOwnsDelivery($delivery, $driver);

        if (! in_array($delivery->driver_delivery_status, [
            Order::DRIVER_STATUS_PICKED_UP,
            Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
            Order::DRIVER_STATUS_RESCHEDULED,
        ], true)) {
            return $this->error('This delivery is not ready to be completed.', 422);
        }

        $data = $request->validate(DriverValidationRules::deliveryComplete());

        if ($delivery->delivery_otp !== $data['delivery_otp']) {
            return $this->error('Invalid delivery OTP.', 422);
        }

        $finalStatus = $delivery->status === 're_intransit' ? 're_delivered' : 'delivered';

        $updates = [
            'status' => $finalStatus,
            'driver_delivery_status' => null,
            'driver_delivered_at' => now(),
            'driver_scheduled_for' => null,
            'driver_rescheduled_at' => null,
            'cod_collected_at' => $delivery->isCod() ? now() : null,
        ];

        if ($request->hasFile('delivery_image')) {
            $updates['driver_delivery_proof_path'] = StoresUploadedFiles::replace(
                $request->file('delivery_image'),
                $delivery->driver_delivery_proof_path,
                'driver/delivery-proofs'
            );
        }

        $delivery->update($updates);

        try {
            $this->wallet->creditDeliveryEarning($delivery->fresh(), $driver);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category']), $driver),
        ], 'Delivery completed.');
    }

    public function rescheduled(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertOwnsDelivery($delivery, $driver);

        if (! in_array($delivery->driver_delivery_status, [
            Order::DRIVER_STATUS_ACCEPTED,
            Order::DRIVER_STATUS_PICKED_UP,
            Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
        ], true)) {
            return $this->error('This delivery cannot be rescheduled.', 422);
        }

        $data = $request->validate(DriverValidationRules::deliveryReschedule());

        $delivery->update([
            'driver_delivery_status' => Order::DRIVER_STATUS_RESCHEDULED,
            'driver_scheduled_for' => $data['scheduled_date'],
            'driver_rescheduled_at' => now(),
            'driver_rejection_reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : null,
            'driver_pickup_at' => null,
        ]);

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category']), $driver),
        ], 'Delivery rescheduled.');
    }

    /** @return array<string, array<int, string>> */
    protected function listDateRules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'date' => ['nullable', 'date'],
        ];
    }
}

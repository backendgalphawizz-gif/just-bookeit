<?php

namespace App\Http\Controllers\Api\V3;

use App\Models\DriverDeliverySkip;
use App\Models\Order;
use App\Services\Driver\DriverWalletService;
use App\Support\Api\DriverApiPresenter;
use App\Support\Api\DriverDeliveryTab;
use App\Support\AppliesListDateFilter;
use App\Support\DriverValidationRules;
use App\Support\OrderItemDriverDeliverySupport;
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
            Order::query()->with(['customer', 'vendor', 'category', 'orderItems']),
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
        $delivery->load(['customer', 'vendor', 'category', 'orderItems']);

        $isOpenPool = DriverApiPresenter::isDispatchReady($delivery)
            && (
                $delivery->driver_id === null
                || $delivery->orderItems->contains(
                    fn ($item) => $item->driver_id === null
                        && in_array($item->status, DriverDeliveryTab::activeDeliveryStatuses(), true)
                )
            );

        abort_unless(
            $isOpenPool || DriverApiPresenter::driverOwnsDelivery($delivery, $driver),
            403
        );

        return $this->success(DriverApiPresenter::deliveryDetail($delivery, $driver));
    }

    public function accept(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertCanAcceptDelivery($delivery, $driver);

        try {
            $item = $this->resolveActionItem($request, $delivery, $driver, requiredWhenMultiple: false);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        if ($item) {
            try {
                OrderItemDriverDeliverySupport::syncItem($item, $driver, Order::DRIVER_STATUS_ACCEPTED);
            } catch (InvalidArgumentException $exception) {
                return $this->error($exception->getMessage(), 422);
            }
            OrderItemDriverDeliverySupport::refreshBookingDriverStatusFromItems($delivery->fresh(['orderItems']), $driver);

            return $this->success([
                'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category', 'orderItems']), $driver),
            ], 'Item accepted for delivery.');
        }

        if (
            (int) $delivery->driver_id === (int) $driver->id
            && $delivery->driver_delivery_status === Order::DRIVER_STATUS_ACCEPTED
        ) {
            return $this->success([
                'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category', 'orderItems']), $driver),
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

        // Legacy only: no per-item drivers → mark active lines accepted for this driver.
        OrderItemDriverDeliverySupport::syncForDriver(
            $delivery->fresh(['orderItems']),
            $driver,
            Order::DRIVER_STATUS_ACCEPTED
        );

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category', 'orderItems']), $driver),
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

        try {
            $item = $this->resolveActionItem($request, $delivery, $driver, requiredWhenMultiple: true);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        if (! $item) {
            return $this->error('Pass item_id to pick up a specific item on this booking.', 422);
        }

        $current = $item->driver_delivery_status;
        if ($current === Order::DRIVER_STATUS_PICKED_UP || $current === Order::DRIVER_STATUS_OUT_FOR_DELIVERY) {
            return $this->success([
                'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category', 'orderItems']), $driver),
                'item' => DriverApiPresenter::deliveryLineItem($item->fresh()),
            ], 'Item already picked up.');
        }

        if (! in_array($current, [null, Order::DRIVER_STATUS_ACCEPTED, Order::DRIVER_STATUS_RESCHEDULED], true)) {
            return $this->error('Pickup is not available for this item status ('.($current ?: 'none').').', 422);
        }

        try {
            $item = OrderItemDriverDeliverySupport::syncItem(
                $item,
                $driver,
                Order::DRIVER_STATUS_PICKED_UP,
                ['driver_pickup_at' => now()]
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        OrderItemDriverDeliverySupport::refreshBookingDriverStatusFromItems($delivery->fresh(['orderItems']), $driver);
        $delivery->ensureDeliveryOtp();

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category', 'orderItems']), $driver),
            'item' => DriverApiPresenter::deliveryLineItem($item->fresh()),
        ], 'Item picked up.');
    }

    public function dispatch(Request $request, Order $delivery): JsonResponse
    {
        return $this->outForDelivery($request, $delivery);
    }

    public function outForDelivery(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertOwnsDelivery($delivery, $driver);

        try {
            $item = $this->resolveActionItem($request, $delivery, $driver, requiredWhenMultiple: true);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        if (! $item) {
            return $this->error('Pass item_id to dispatch a specific item on this booking.', 422);
        }

        if (! in_array($item->driver_delivery_status, [
            Order::DRIVER_STATUS_PICKED_UP,
            Order::DRIVER_STATUS_RESCHEDULED,
        ], true)) {
            return $this->error('Mark pickup for this item before dispatching.', 422);
        }

        $extra = [];
        if ($item->driver_delivery_status === Order::DRIVER_STATUS_RESCHEDULED && ! $item->driver_pickup_at) {
            $extra['driver_pickup_at'] = now();
        }

        try {
            $item = OrderItemDriverDeliverySupport::syncItem(
                $item,
                $driver,
                Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
                $extra
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        OrderItemDriverDeliverySupport::refreshBookingDriverStatusFromItems($delivery->fresh(['orderItems']), $driver);

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category', 'orderItems']), $driver),
            'item' => DriverApiPresenter::deliveryLineItem($item->fresh()),
        ], 'Item out for delivery.');
    }

    public function delivered(Request $request, Order $delivery): JsonResponse
    {
        return $this->deliver($request, $delivery);
    }

    public function deliver(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertOwnsDelivery($delivery, $driver);

        try {
            $item = $this->resolveActionItem($request, $delivery, $driver, requiredWhenMultiple: true);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        if (! $item) {
            return $this->error('Pass item_id to complete delivery for a specific item.', 422);
        }

        if (! in_array($item->driver_delivery_status, [
            Order::DRIVER_STATUS_PICKED_UP,
            Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
            Order::DRIVER_STATUS_RESCHEDULED,
        ], true)) {
            return $this->error('This item is not ready to be completed.', 422);
        }

        $request->validate(DriverValidationRules::deliveryComplete());

        $itemFinal = $item->status === 're_intransit'
            ? (\App\Support\OrderItemStatusSupport::isRentalItem($item, $delivery) ? 'returned' : 're_delivered')
            : \App\Support\OrderItemStatusSupport::statusAfterOutboundDelivery($item, $delivery);

        $item->update([
            'status' => $itemFinal,
            'driver_delivery_status' => null,
        ]);

        app(\App\Services\Checkout\VendorBookingItemService::class)
            ->syncBookingFromItems($delivery->fresh(['orderItems', 'checkoutOrder']));

        if ($request->hasFile('delivery_image')) {
            $delivery->update([
                'driver_delivery_proof_path' => StoresUploadedFiles::replace(
                    $request->file('delivery_image'),
                    $delivery->driver_delivery_proof_path,
                    'driver/delivery-proofs'
                ),
                'cod_collected_at' => $delivery->isCod() ? ($delivery->cod_collected_at ?: now()) : $delivery->cod_collected_at,
            ]);
        } elseif ($delivery->isCod() && ! $delivery->cod_collected_at) {
            $delivery->update(['cod_collected_at' => now()]);
        }

        OrderItemDriverDeliverySupport::refreshBookingDriverStatusFromItems($delivery->fresh(['orderItems']), $driver);

        $remaining = $delivery->fresh(['orderItems'])->orderItems
            ->where('driver_id', $driver->id)
            ->filter(fn ($row) => in_array($row->status, ['in_progress', 're_intransit'], true)
                || in_array($row->driver_delivery_status, [
                    Order::DRIVER_STATUS_ACCEPTED,
                    Order::DRIVER_STATUS_PICKED_UP,
                    Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
                    Order::DRIVER_STATUS_RESCHEDULED,
                ], true));

        if ($remaining->isEmpty()) {
            $delivery->update([
                'driver_delivery_status' => null,
                'driver_delivered_at' => now(),
                'driver_scheduled_for' => null,
                'driver_rescheduled_at' => null,
            ]);

            try {
                $this->wallet->creditDeliveryEarning($delivery->fresh(), $driver);
            } catch (InvalidArgumentException $exception) {
                return $this->error($exception->getMessage(), 422);
            }
        }

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category', 'orderItems']), $driver),
            'item' => DriverApiPresenter::deliveryLineItem($item->fresh(['driver', 'order'])),
        ], 'Item delivered.');
    }

    public function rescheduled(Request $request, Order $delivery): JsonResponse
    {
        $driver = $this->driver($request);
        $this->assertOwnsDelivery($delivery, $driver);

        try {
            $item = $this->resolveActionItem($request, $delivery, $driver, requiredWhenMultiple: true);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        if (! $item) {
            return $this->error('Pass item_id to reschedule a specific item.', 422);
        }

        if (! in_array($item->driver_delivery_status, [
            Order::DRIVER_STATUS_ACCEPTED,
            Order::DRIVER_STATUS_PICKED_UP,
            Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
        ], true)) {
            return $this->error('This item cannot be rescheduled.', 422);
        }

        $data = $request->validate(DriverValidationRules::deliveryReschedule());

        try {
            $item = OrderItemDriverDeliverySupport::syncItem(
                $item,
                $driver,
                Order::DRIVER_STATUS_RESCHEDULED,
                [
                    'driver_pickup_at' => null,
                ]
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $delivery->update([
            'driver_scheduled_for' => $data['scheduled_date'],
            'driver_rescheduled_at' => now(),
            'driver_rejection_reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : null,
        ]);
        OrderItemDriverDeliverySupport::refreshBookingDriverStatusFromItems($delivery->fresh(['orderItems']), $driver);

        return $this->success([
            'delivery' => DriverApiPresenter::deliveryDetail($delivery->fresh(['customer', 'vendor', 'category', 'orderItems']), $driver),
            'item' => DriverApiPresenter::deliveryLineItem($item->fresh()),
        ], 'Item rescheduled.');
    }

    /**
     * Resolve which line item this driver action targets.
     * Prefer route item id (/deliveries/{itemId}/pickup), then body item_id.
     */
    protected function resolveActionItem(
        Request $request,
        Order $delivery,
        \App\Models\Driver $driver,
        bool $requiredWhenMultiple = true
    ): ?\App\Models\OrderItem {
        $itemId = $request->attributes->get('delivery_item_id');
        if ($itemId === null && $request->filled('item_id')) {
            $itemId = (int) $request->input('item_id');
        }
        $itemId = $itemId !== null ? (int) $itemId : null;

        $item = OrderItemDriverDeliverySupport::resolveTargetItem($delivery, $driver, $itemId);

        if ($item) {
            return $item;
        }

        if (! $requiredWhenMultiple) {
            return null;
        }

        $delivery->loadMissing('orderItems');
        $mine = $delivery->orderItems->where('driver_id', $driver->id);
        if ($mine->count() > 1 || ($mine->isEmpty() && $delivery->orderItems->count() > 1)) {
            throw new InvalidArgumentException(
                'Multiple items on this booking. Pass item_id (or call /deliveries/{item_id}/pickup) for the specific item.'
            );
        }

        return null;
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

<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Checkout\VendorBookingItemService;
use App\Support\Api\VendorApiPresenter;
use App\Support\Api\VendorBookingStatus;
use App\Support\AppliesListDateFilter;
use App\Support\OrderDispatchSupport;
use App\Support\VendorValidationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class BookingController extends VendorApiController
{
    use AppliesListDateFilter;

    public function __construct(
        protected VendorBookingItemService $items
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->validateListDateRange($request);
        $vendor = $this->vendor($request);

        $query = Order::query()
            ->where('vendor_id', $vendor->id)
            ->paymentConfirmed()
            ->with(['customer', 'category', 'driver', 'orderItems', 'checkoutOrder'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('order_number', 'like', $term)
                        ->orWhere('item_title', 'like', $term)
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', $term))
                        ->orWhereHas('orderItems', function ($items) use ($term) {
                            $items->where('item_snapshot->title', 'like', $term);
                        });
                });
            })
            ->when($request->filled('tab'), function ($q) use ($request) {
                $statuses = VendorBookingStatus::statusesForTab($request->string('tab')->toString());

                if ($statuses !== null) {
                    $q->whereIn('status', $statuses);
                }
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $raw = strtolower(trim(str_replace('_', '-', $request->string('status')->toString())));
                $statuses = VendorBookingStatus::statusesForTab($raw);

                if ($statuses !== null) {
                    $q->whereIn('status', $statuses);

                    return;
                }

                $status = VendorBookingStatus::normalizeInput($request->string('status')->toString());
                $q->where('status', $status);
            });

        $bookings = $this->applyDateRange($query, $request)
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success(
            VendorApiPresenter::paginator($bookings, fn (Order $order) => VendorApiPresenter::bookingSummary($order))
        );
    }

    public function show(Request $request, string $booking): JsonResponse
    {
        // Detail should open for any booking the vendor owns (same id as list/home).
        $order = $this->resolveOwnedBooking($request, $booking, requirePaymentConfirmed: false);

        return $this->success(
            VendorApiPresenter::bookingDetail(
                $order->load([
                    'customer.measurements',
                    'vendor',
                    'category',
                    'driver',
                    'review.customer',
                    'orderItems',
                    'checkoutOrder',
                    'refunds',
                ])
            )
        );
    }

    public function accept(Request $request, string $booking): JsonResponse
    {
        $order = $this->resolveOwnedBooking($request, $booking);

        try {
            $updated = $this->items->acceptAll($order);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($updated),
        ], 'Booking accepted. All pending items were accepted.');
    }

    public function reject(Request $request, string $booking): JsonResponse
    {
        $order = $this->resolveOwnedBooking($request, $booking);

        $data = $this->validateVendor($request, VendorValidationRules::bookingReject());

        try {
            $result = $this->items->rejectAll($order, trim($data['reason']));
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $refund = $result['refund'];

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($result['booking']),
            'partial_refund' => $refund ? [
                'id' => $refund->id,
                'amount' => (float) $refund->amount,
                'status' => $refund->status,
                'auto_processed' => (bool) $refund->auto_processed,
            ] : null,
        ], 'Booking rejected.');
    }

    public function acceptItem(Request $request, string $booking, string $item): JsonResponse
    {
        $order = $this->resolveOwnedBooking($request, $booking);
        $orderItem = $this->resolveOwnedItem($order, $item);

        try {
            $updated = $this->items->acceptItem($order, $orderItem);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $pendingCount = $updated->orderItems
            ->where('status', OrderItem::STATUS_PENDING)
            ->count();

        $message = $pendingCount > 0
            ? 'Item accepted. Booking stays pending until all items are accepted.'
            : 'Item accepted. All items are accepted — booking is now accepted.';

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($updated),
            'item' => VendorApiPresenter::orderLineItem($orderItem->fresh()),
            'pending_items_count' => $pendingCount,
            'booking_fully_accepted' => $pendingCount === 0 && $updated->status === 'accepted',
        ], $message);
    }

    public function rejectItem(Request $request, string $booking, string $item): JsonResponse
    {
        $order = $this->resolveOwnedBooking($request, $booking);
        $orderItem = $this->resolveOwnedItem($order, $item);

        $data = $this->validateVendor($request, VendorValidationRules::bookingReject());

        try {
            $result = $this->items->rejectItem($order, $orderItem, trim($data['reason']));
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $refund = $result['refund'];
        $updated = $result['booking'];

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($updated),
            'item' => VendorApiPresenter::orderLineItem($orderItem->fresh()),
            'pending_items_count' => $updated->orderItems->where('status', OrderItem::STATUS_PENDING)->count(),
            'partial_refund' => $refund ? [
                'id' => $refund->id,
                'amount' => (float) $refund->amount,
                'status' => $refund->status,
                'auto_processed' => (bool) $refund->auto_processed,
            ] : null,
        ], 'Item rejected.');
    }

    public function updateStatus(Request $request, string $booking): JsonResponse
    {
        $order = $this->resolveOwnedBooking($request, $booking);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(VendorBookingStatus::acceptedInputStatuses())],
        ]);

        $nextStatus = VendorBookingStatus::normalizeInput($data['status']);

        if (! in_array($nextStatus, Order::STATUSES, true)) {
            return $this->error('Invalid booking status.', 422);
        }

        if (! OrderDispatchSupport::canTransitionTo($order, $nextStatus)) {
            return $this->error(
                'Invalid status transition from '.VendorBookingStatus::toApi($order->status).' to '.VendorBookingStatus::toApi($nextStatus).'.',
                422
            );
        }

        $order->status = $nextStatus;

        if (OrderDispatchSupport::isDispatchStatus($nextStatus)) {
            OrderDispatchSupport::prepareForTransit($order);
        }

        $order->save();

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($order->fresh([
                'customer',
                'category',
                'driver',
                'orderItems',
                'checkoutOrder',
            ])),
        ], 'Booking status updated.');
    }

    public function updateDamage(Request $request, string $booking): JsonResponse
    {
        $order = $this->resolveOwnedBooking($request, $booking);

        if ($order->status !== 'returned') {
            return $this->error('Damage deduction can only be recorded for returned bookings.', 422);
        }

        $data = $this->validateVendor($request, VendorValidationRules::bookingDamage());

        $order->update([
            'damage_note' => $data['damage_note'] ?? null,
            'damage_deduct_percent' => $data['damage_deduct_percent'] ?? null,
        ]);

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($order->fresh([
                'customer',
                'category',
                'driver',
                'orderItems',
                'checkoutOrder',
            ])),
        ], 'Damage deduction updated.');
    }
}

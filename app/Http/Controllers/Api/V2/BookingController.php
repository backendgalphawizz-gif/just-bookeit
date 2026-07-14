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
                $status = VendorBookingStatus::normalizeInput($request->string('status')->toString());
                if ($status === 'new') {
                    $q->whereIn('status', ['new', 'pending_acceptance']);
                } else {
                    $q->where('status', $status);
                }
            });

        $bookings = $this->applyDateRange($query, $request)
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success(
            VendorApiPresenter::paginator($bookings, fn (Order $order) => VendorApiPresenter::bookingSummary($order))
        );
    }

    public function show(Request $request, Order $booking): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsOrder($booking, $vendor);

        return $this->success(
            VendorApiPresenter::bookingDetail(
                $booking->load([
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

    public function accept(Request $request, Order $booking): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsOrder($booking, $vendor);

        try {
            $updated = $this->items->acceptAll($booking);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($updated),
        ], 'Booking accepted.');
    }

    public function reject(Request $request, Order $booking): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsOrder($booking, $vendor);

        $data = $this->validateVendor($request, VendorValidationRules::bookingReject());

        try {
            $result = $this->items->rejectAll($booking, trim($data['reason']));
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

    public function acceptItem(Request $request, Order $booking, OrderItem $item): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsOrder($booking, $vendor);

        try {
            $updated = $this->items->acceptItem($booking, $item);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($updated),
            'item' => VendorApiPresenter::orderLineItem($item->fresh()),
        ], 'Item accepted.');
    }

    public function rejectItem(Request $request, Order $booking, OrderItem $item): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsOrder($booking, $vendor);

        $data = $this->validateVendor($request, VendorValidationRules::bookingReject());

        try {
            $result = $this->items->rejectItem($booking, $item, trim($data['reason']));
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $refund = $result['refund'];

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($result['booking']),
            'item' => VendorApiPresenter::orderLineItem($item->fresh()),
            'partial_refund' => $refund ? [
                'id' => $refund->id,
                'amount' => (float) $refund->amount,
                'status' => $refund->status,
                'auto_processed' => (bool) $refund->auto_processed,
            ] : null,
        ], 'Item rejected.');
    }

    public function updateStatus(Request $request, Order $booking): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsOrder($booking, $vendor);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(VendorBookingStatus::acceptedInputStatuses())],
        ]);

        $nextStatus = VendorBookingStatus::normalizeInput($data['status']);

        if (! in_array($nextStatus, Order::STATUSES, true)) {
            return $this->error('Invalid booking status.', 422);
        }

        if (! OrderDispatchSupport::canTransitionTo($booking, $nextStatus)) {
            return $this->error(
                'Invalid status transition from '.VendorBookingStatus::toApi($booking->status).' to '.VendorBookingStatus::toApi($nextStatus).'.',
                422
            );
        }

        $booking->status = $nextStatus;

        if (OrderDispatchSupport::isDispatchStatus($nextStatus)) {
            OrderDispatchSupport::prepareForTransit($booking);
        }

        $booking->save();

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($booking->fresh([
                'customer',
                'category',
                'driver',
                'orderItems',
                'checkoutOrder',
            ])),
        ], 'Booking status updated.');
    }

    public function updateDamage(Request $request, Order $booking): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsOrder($booking, $vendor);

        if ($booking->status !== 'returned') {
            return $this->error('Damage deduction can only be recorded for returned bookings.', 422);
        }

        $data = $this->validateVendor($request, VendorValidationRules::bookingDamage());

        $booking->update([
            'damage_note' => $data['damage_note'] ?? null,
            'damage_deduct_percent' => $data['damage_deduct_percent'] ?? null,
        ]);

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($booking->fresh([
                'customer',
                'category',
                'driver',
                'orderItems',
                'checkoutOrder',
            ])),
        ], 'Damage deduction updated.');
    }
}

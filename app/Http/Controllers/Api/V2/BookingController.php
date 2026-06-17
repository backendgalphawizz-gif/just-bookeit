<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Order;
use App\Support\Api\VendorApiPresenter;
use App\Support\Api\VendorBookingStatus;
use App\Support\AppliesListDateFilter;
use App\Support\OrderDispatchSupport;
use App\Support\VendorValidationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends VendorApiController
{
    use AppliesListDateFilter;

    public function index(Request $request): JsonResponse
    {
        $this->validateListDateRange($request);
        $vendor = $this->vendor($request);

        $query = Order::query()
            ->where('vendor_id', $vendor->id)
            ->with(['customer', 'category', 'driver'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('order_number', 'like', $term)
                        ->orWhere('item_title', 'like', $term)
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', $term));
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
                $booking->load(['customer.measurements', 'vendor', 'category', 'driver', 'review.customer'])
            )
        );
    }

    public function accept(Request $request, Order $booking): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsOrder($booking, $vendor);

        if (! in_array($booking->status, ['new', 'pending_acceptance'], true)) {
            return $this->error('This booking cannot be accepted.', 422);
        }

        $booking->update(['status' => 'accepted']);

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($booking->fresh(['customer', 'category', 'driver'])),
        ], 'Booking accepted.');
    }

    public function reject(Request $request, Order $booking): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsOrder($booking, $vendor);

        if (! in_array($booking->status, ['new', 'pending_acceptance'], true)) {
            return $this->error('This booking cannot be rejected.', 422);
        }

        $data = $this->validateVendor($request, VendorValidationRules::bookingReject());

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => trim($data['reason']),
        ]);

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($booking->fresh(['customer', 'category', 'driver'])),
        ], 'Booking rejected.');
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

        if (! OrderDispatchSupport::canTransitionTo($booking->status, $nextStatus)) {
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
            'booking' => VendorApiPresenter::bookingDetail($booking->fresh(['customer', 'category', 'driver'])),
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
            'booking' => VendorApiPresenter::bookingDetail($booking->fresh(['customer', 'category', 'driver'])),
        ], 'Damage deduction updated.');
    }
}

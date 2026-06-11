<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Order;
use App\Support\Api\VendorApiPresenter;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                match ($request->string('tab')->toString()) {
                    'accepted' => $q->whereIn('status', ['accepted', 'in_progress']),
                    'in_transit' => $q->where('status', 'in_transit'),
                    'new' => $q->whereIn('status', ['new', 'pending_acceptance']),
                    'completed' => $q->where('status', 'delivered'),
                    default => null,
                };
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->string('status')->toString();
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

        return $this->success(VendorApiPresenter::bookingDetail($booking));
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

        $booking->update(['status' => 'cancelled']);

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($booking->fresh(['customer', 'category', 'driver'])),
        ], 'Booking rejected.');
    }

    public function updateStatus(Request $request, Order $booking): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsOrder($booking, $vendor);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Order::STATUSES)],
        ]);

        $booking->update(['status' => $data['status']]);

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($booking->fresh(['customer', 'category', 'driver'])),
        ], 'Booking status updated.');
    }
}

<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Order;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends VendorController
{
    use AppliesListDateFilter;

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);
        $vendor = $this->vendor();

        $orders = Order::query()
            ->where('vendor_id', $vendor->id)
            ->with(['customer', 'category', 'driver'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('order_number', 'like', $term)
                        ->orWhere('item_title', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->string('status')->toString();
                if ($status === 'new') {
                    $q->whereIn('status', ['new', 'pending_acceptance']);
                } else {
                    $q->where('status', $status);
                }
            });
        $orders = $this->applyDateRange($orders, $request)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('vendor.bookings.index', compact('orders'));
    }

    public function show(Order $booking): View
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);
        $booking->load(['customer', 'category', 'driver']);

        return view('vendor.bookings.show', compact('booking'));
    }

    public function accept(Order $booking): RedirectResponse
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);

        if (! in_array($booking->status, ['new', 'pending_acceptance'], true)) {
            return back()->with('error', 'This booking cannot be accepted.');
        }

        $booking->update(['status' => 'accepted']);

        return back()->with('success', 'Booking accepted.');
    }

    public function reject(Order $booking): RedirectResponse
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);

        if (! in_array($booking->status, ['new', 'pending_acceptance'], true)) {
            return back()->with('error', 'This booking cannot be rejected.');
        }

        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'Booking rejected.');
    }

    public function updateStatus(Request $request, Order $booking): RedirectResponse
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Order::STATUSES)],
        ]);

        $booking->update(['status' => $data['status']]);

        return back()->with('success', 'Booking status updated.');
    }
}

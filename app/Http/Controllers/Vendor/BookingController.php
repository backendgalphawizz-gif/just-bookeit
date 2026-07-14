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
            ->paymentConfirmed()
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
        abort_unless($booking->isPaymentConfirmed(), 404);

        $booking->load([
            'customer.measurements',
            'category',
            'driver',
            'checkoutOrder',
            'orderItems.portfolioItem',
            'refund',
            'dispute',
        ]);

        $measurementSections = \App\Support\WebMeasurementForm::sections();
        $measurementValues = $this->measurementValuesFor($booking);

        return view('vendor.bookings.show', [
            'booking' => $booking,
            'quickActions' => $this->quickActionsFor($booking),
            'manageableStatuses' => $this->manageableStatusesFor($booking),
            'measurementSections' => $measurementSections,
            'measurementValues' => $measurementValues,
        ]);
    }

    /** @return array<string, string|null> */
    protected function measurementValuesFor(Order $booking): array
    {
        $checkout = $booking->checkoutOrder;
        $profile = $booking->customer?->measurements()->latest('id')->first();
        $values = \App\Support\WebMeasurementForm::valuesFromProfile($profile);

        $extra = array_merge(
            is_array($checkout?->measure_extra) ? $checkout->measure_extra : [],
            is_array($booking->measure_extra) ? $booking->measure_extra : [],
        );

        foreach (\App\Support\WebMeasurementForm::labelToField() as $field) {
            if (array_key_exists($field, $extra) && filled($extra[$field])) {
                $values[$field] = (string) $extra[$field];
            }
        }

        if ($booking->measure_height_cm ?? $checkout?->measure_height_cm) {
            $values['height_cm'] = (string) ($booking->measure_height_cm ?? $checkout->measure_height_cm);
        }
        if ($booking->measure_chest_cm ?? $checkout?->measure_chest_cm) {
            $values['chest_cm'] = (string) ($booking->measure_chest_cm ?? $checkout->measure_chest_cm);
        }
        if ($booking->measure_waist_cm ?? $checkout?->measure_waist_cm) {
            $values['waist_cm'] = (string) ($booking->measure_waist_cm ?? $checkout->measure_waist_cm);
        }

        return $values;
    }

    /** @return array<int, array<string, mixed>> */
    protected function quickActionsFor(Order $booking): array
    {
        return match ($booking->status) {
            'accepted' => [
                ['label' => 'Start preparing outfit', 'status' => 'in_progress', 'variant' => 'primary'],
            ],
            'in_progress' => [
                ['label' => 'Mark delivered', 'status' => 'delivered', 'variant' => 'success'],
            ],
            'delivered' => $booking->isRental()
                ? [
                    ['label' => 'Start return pickup', 'status' => 're_intransit', 'variant' => 'primary'],
                    ['label' => 'Mark returned', 'status' => 'returned', 'variant' => 'outline'],
                ]
                : [
                    ['label' => 'Mark returned', 'status' => 'returned', 'variant' => 'primary'],
                    ['label' => 'Send for rework', 'status' => 'rework', 'variant' => 'outline'],
                ],
            're_intransit' => $booking->isRental()
                ? [['label' => 'Mark returned', 'status' => 'returned', 'variant' => 'success']]
                : [['label' => 'Mark re-delivered', 'status' => 're_delivered', 'variant' => 'success']],
            'rework' => [
                ['label' => 'Dispatch rework', 'status' => 're_intransit', 'variant' => 'primary'],
            ],
            default => [],
        };
    }

    /** @return array<int, string> */
    protected function manageableStatusesFor(Order $booking): array
    {
        $statuses = match ($booking->status) {
            'new', 'pending_acceptance' => ['accepted', 'cancelled'],
            'accepted' => ['accepted', 'in_progress', 'cancelled'],
            'in_progress' => ['in_progress', 'delivered', 'cancelled'],
            'delivered' => $booking->isRental()
                ? ['delivered', 're_intransit', 'returned']
                : ['delivered', 'returned', 'rework'],
            're_intransit' => $booking->isRental()
                ? ['re_intransit', 'returned']
                : ['re_intransit', 're_delivered'],
            'rework' => ['rework', 're_intransit'],
            'returned', 're_delivered' => [$booking->status],
            default => array_values(array_unique([$booking->status, 'cancelled'])),
        };

        return array_values(array_filter(
            $statuses,
            fn (string $status) => in_array($status, Order::STATUSES, true)
        ));
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

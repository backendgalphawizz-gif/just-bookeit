<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Checkout\VendorBookingItemService;
use App\Support\Api\VendorBookingListStatus;
use App\Support\AppliesListDateFilter;
use App\Support\OrderDispatchSupport;
use App\Support\VendorValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class BookingController extends VendorController
{
    use AppliesListDateFilter;

    public function __construct(
        protected VendorBookingItemService $items
    ) {}

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);
        $vendor = $this->vendor();

        // Show unpaid NEW bookings too (same as vendor API), so designers see
        // orders as soon as customers place them. Accept still requires payment.
        $orders = Order::query()
            ->where('vendor_id', $vendor->id)
            ->with(['customer', 'category', 'driver', 'orderItems'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('order_number', 'like', $term)
                        ->orWhere('item_title', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                VendorBookingListStatus::applyTabFilter($q, $request->string('status')->toString());
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

        $booking->load([
            'customer.measurements',
            'category',
            'driver',
            'checkoutOrder',
            'orderItems.portfolioItem',
            'orderItems.driver',
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
                ['label' => 'Mark In Transit', 'status' => 'in_progress', 'variant' => 'primary'],
            ],
            'in_progress' => [
                ['label' => 'Mark Delivered', 'status' => 'delivered', 'variant' => 'success'],
            ],
            'delivered' => $booking->isRental()
                ? [
                    ['label' => 'Start Return Pickup', 'status' => 're_intransit', 'variant' => 'primary'],
                ]
                : [
                    ['label' => 'Mark Completed', 'status' => 'completed', 'variant' => 'success'],
                    ['label' => 'Send for Rework', 'status' => 'rework', 'variant' => 'outline'],
                ],
            'rental_active' => [
                ['label' => 'Start Return Pickup', 'status' => 're_intransit', 'variant' => 'primary'],
                ['label' => 'Send for Rework', 'status' => 'rework', 'variant' => 'outline'],
            ],
            're_intransit' => $booking->isRental()
                ? [['label' => 'Mark Returned', 'status' => 'returned', 'variant' => 'success']]
                : [['label' => 'Mark Re-delivered', 'status' => 're_delivered', 'variant' => 'success']],
            'rework' => [
                ['label' => 'Dispatch Rework (Return In Transit)', 'status' => 're_intransit', 'variant' => 'primary'],
            ],
            'returned', 're_delivered' => [
                ['label' => 'Mark Completed', 'status' => 'completed', 'variant' => 'success'],
            ],
            default => [],
        };
    }

    /** @return array<int, string> */
    protected function manageableStatusesFor(Order $booking): array
    {
        $allowed = OrderDispatchSupport::allowedNextStatuses($booking);

        return array_values(array_unique(array_filter(
            [$booking->status, ...$allowed],
            fn (string $status) => in_array($status, Order::STATUSES, true)
        )));
    }

    public function accept(Order $booking): RedirectResponse
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);

        try {
            $this->items->acceptAll($booking);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Booking accepted.');
    }

    public function reject(Request $request, Order $booking): RedirectResponse
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'min:5', 'max:500'],
        ]);
        $reason = trim($data['reason'] ?? 'Rejected by vendor');

        try {
            $this->items->rejectAll($booking, $reason);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Booking rejected.');
    }

    public function acceptItem(Order $booking, OrderItem $item): RedirectResponse
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);

        try {
            $this->items->acceptItem($booking, $item);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Item accepted.');
    }

    public function rejectItem(Request $request, Order $booking, OrderItem $item): RedirectResponse
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->items->rejectItem($booking, $item, trim($data['reason']));
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Item rejected.');
    }

    public function updateStatus(Request $request, Order $booking): RedirectResponse
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Order::STATUSES)],
        ]);

        try {
            $this->items->updateBookingStatus($booking, $data['status']);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Booking status updated from item statuses.');
    }

    public function updateDamage(Request $request, Order $booking): RedirectResponse
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);

        $booking->loadMissing('orderItems');
        $data = $request->validate(VendorValidationRules::bookingDamage());

        try {
            $this->applyDamageDeduction($booking, $data);
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Damage deduction updated.');
    }

    /**
     * @param  array{item_id?: int|null, damage_note?: string|null, damage_deduct_percent?: float|int|string|null}  $data
     */
    protected function applyDamageDeduction(Order $booking, array $data): void
    {
        $booking->loadMissing('orderItems');
        $itemId = isset($data['item_id']) ? (int) $data['item_id'] : null;

        if ($booking->orderItems->isNotEmpty()) {
            $item = null;
            if ($itemId) {
                $item = $booking->orderItems->firstWhere('id', $itemId);
                if (! $item) {
                    throw new \InvalidArgumentException('Item does not belong to this booking.');
                }
            } else {
                $returned = $booking->orderItems->where('status', 'returned')->values();
                if ($returned->count() === 1) {
                    $item = $returned->first();
                } elseif ($returned->isEmpty()) {
                    throw new \InvalidArgumentException(
                        'Damage deduction can only be recorded for returned items. Pass item_id.'
                    );
                } else {
                    throw new \InvalidArgumentException(
                        'Pass item_id to record damage for a specific returned item.'
                    );
                }
            }

            if ($item->status !== 'returned') {
                throw new \InvalidArgumentException(
                    'Damage deduction can only be recorded when this item is Returned to Vendor.'
                );
            }

            $resolved = \App\Models\OrderItem::resolveDamageFields(
                (float) $item->line_amount,
                $data['damage_amount'] ?? $data['damage_deduct_amount'] ?? null,
                $data['damage_deduct_percent'] ?? null,
                'item line amount'
            );

            $item->update([
                'damage_note' => $data['damage_note'] ?? null,
                'damage_amount' => $resolved['damage_amount'],
                'damage_deduct_percent' => $resolved['damage_deduct_percent'],
            ]);

            $this->syncBookingDamageFromItems($booking->fresh(['orderItems']));

            return;
        }

        if ($booking->status !== 'returned') {
            throw new \InvalidArgumentException(
                'Damage deduction can only be recorded for returned bookings.'
            );
        }

        $resolved = \App\Models\OrderItem::resolveDamageFields(
            max(0.01, $booking->subtotal()),
            $data['damage_amount'] ?? $data['damage_deduct_amount'] ?? null,
            $data['damage_deduct_percent'] ?? null,
            'booking subtotal'
        );

        $booking->update([
            'damage_note' => $data['damage_note'] ?? null,
            'damage_amount' => $resolved['damage_amount'],
            'damage_deduct_percent' => $resolved['damage_deduct_percent'],
        ]);
    }

    protected function syncBookingDamageFromItems(Order $booking): void
    {
        $booking->loadMissing('orderItems');
        $withDamage = $booking->orderItems->filter(
            fn (\App\Models\OrderItem $item) => $item->hasDamageRecord()
        );

        if ($withDamage->isEmpty()) {
            $booking->update([
                'damage_note' => null,
                'damage_amount' => null,
                'damage_deduct_percent' => null,
            ]);

            return;
        }

        $totalDeduction = round(
            (float) $booking->orderItems->sum(
                fn (\App\Models\OrderItem $item) => $item->damageDeduction()
            ),
            2
        );

        $booking->update([
            'damage_note' => $withDamage->pluck('damage_note')->filter()->implode('; ') ?: null,
            'damage_amount' => $totalDeduction > 0 ? $totalDeduction : null,
            'damage_deduct_percent' => $withDamage->every(fn (\App\Models\OrderItem $item) => $item->damage_deduct_percent !== null)
                ? round(($totalDeduction / max(0.01, $booking->subtotal())) * 100, 2)
                : null,
        ]);
    }
}

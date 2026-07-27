<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Checkout\VendorBookingItemService;
use App\Support\Api\VendorBookingListStatus;
use App\Support\Api\VendorBookingStatus;
use App\Support\AppliesListDateFilter;
use App\Support\OrderDispatchSupport;
use App\Support\OrderItemStatusSupport;
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

        $orders = Order::query()
            ->where('vendor_id', $vendor->id)
            ->with(['customer', 'category', 'driver', 'orderItems'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('order_number', 'like', $term)
                        ->orWhere('item_title', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term))
                        ->orWhereHas('orderItems', function ($items) use ($term) {
                            $items->where('item_snapshot->title', 'like', $term);
                        });
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                VendorBookingListStatus::applyTabFilter($q, $request->string('status')->toString());
            })
            ->when($request->filled('item_status'), function ($q) use ($request) {
                $itemStatus = VendorBookingStatus::normalizeInput($request->string('item_status')->toString());
                $q->whereHas('orderItems', fn ($items) => $items->where('status', $itemStatus));
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
            'orderItems.portfolioItem.damageDeductions',
            'orderItems.driver',
            'refund',
            'dispute',
            'review.customer',
        ]);

        $hasLineItems = $booking->orderItems->isNotEmpty();
        $itemStatuses = $booking->orderItems
            ->where('status', '!=', OrderItem::STATUS_CANCELLED)
            ->pluck('status')
            ->unique()
            ->values();
        $itemsDiverged = $hasLineItems && $itemStatuses->count() > 1;

        $measurementSections = \App\Support\WebMeasurementForm::sections();
        $measurementValues = $this->measurementValuesFor($booking);

        return view('vendor.bookings.show', [
            'booking' => $booking,
            'quickActions' => $itemsDiverged ? [] : $this->quickActionsFor($booking),
            'manageableStatuses' => $itemsDiverged ? [$booking->status] : $this->manageableStatusesFor($booking),
            'itemsDiverged' => $itemsDiverged,
            'measurementSections' => $measurementSections,
            'measurementValues' => $measurementValues,
            'deliveryOtp' => $this->deliveryOtpFor($booking),
        ]);
    }

    protected function deliveryOtpFor(Order $booking): ?string
    {
        $booking->loadMissing('orderItems');

        $needsOtp = in_array($booking->status, ['in_progress', 're_intransit'], true)
            || $booking->orderItems->contains(
                fn (OrderItem $item) => in_array($item->statusForDisplay($booking), ['in_progress', 're_intransit'], true)
            );

        return $needsOtp ? $booking->ensureDeliveryOtp() : null;
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
                : [
                    ['label' => 'Mark Completed', 'status' => 'completed', 'variant' => 'success'],
                    ['label' => 'Mark Re-delivered', 'status' => 're_delivered', 'variant' => 'outline'],
                ],
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

        $data = $request->validate(VendorValidationRules::bookingReject());
        $reason = trim($data['reason']);

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
        $this->assertItemBelongs($booking, $item);

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
        $this->assertItemBelongs($booking, $item);

        $data = $request->validate(VendorValidationRules::bookingReject());

        try {
            $this->items->rejectItem($booking, $item, trim($data['reason']));
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Item rejected.');
    }

    public function updateItemStatus(Request $request, Order $booking, OrderItem $item): RedirectResponse
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);
        $this->assertItemBelongs($booking, $item);

        $data = $request->validate(VendorValidationRules::bookingItemStatus());
        $nextStatus = VendorBookingStatus::normalizeInput($data['status']);
        $requestedReturned = $nextStatus === 'returned';
        $nextStatus = OrderItemStatusSupport::normalizeVendorRequestedStatus($item, $nextStatus, $booking);

        if (! in_array($nextStatus, Order::STATUSES, true) && $nextStatus !== OrderItem::STATUS_CANCELLED) {
            return back()->with('error', 'Invalid item status.');
        }

        $wantsDamage = filled($data['damage_note'] ?? null)
            || array_key_exists('damage_amount', $data)
            || array_key_exists('damage_deduct_amount', $data)
            || array_key_exists('damage_deduct_percent', $data);

        try {
            if ($nextStatus === 'completed' && $wantsDamage) {
                if ($item->status !== 'returned') {
                    return back()->with('error', 'Damage with complete is only allowed when the item is Returned to Vendor.');
                }
                if (! OrderItemStatusSupport::isRentalItem($item, $booking)) {
                    return back()->with('error', 'Damage deduction on complete applies to rented dress/jewellery only.');
                }

                $this->applyDamageDeduction($booking, [
                    'item_id' => $item->id,
                    'damage_note' => $data['damage_note'] ?? $data['note'] ?? null,
                    'damage_amount' => $data['damage_amount'] ?? null,
                    'damage_deduct_amount' => $data['damage_deduct_amount'] ?? null,
                    'damage_deduct_percent' => $data['damage_deduct_percent'] ?? null,
                ]);
                $item = $item->fresh();
            } elseif ($wantsDamage && $nextStatus !== 'completed') {
                return back()->with('error', 'Pass damage fields only when completing a returned rental item.');
            }

            $this->items->updateItemStatus($booking->fresh(['orderItems']), $item->fresh(), $nextStatus);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = match (true) {
            $nextStatus === 'completed' && $wantsDamage => 'Item completed with damage deduction.',
            $nextStatus === 're_intransit' && $requestedReturned => 'Return In Transit started. Admin can assign a return driver.',
            $nextStatus === 're_intransit' => 'Return In Transit started.',
            default => 'Item status updated.',
        };

        return back()->with('success', $message);
    }

    public function updateItemDamage(Request $request, Order $booking, OrderItem $item): RedirectResponse
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);
        $this->assertItemBelongs($booking, $item);

        $data = $request->validate(VendorValidationRules::bookingDamage());
        $data['item_id'] = $item->id;

        try {
            $this->applyDamageDeduction($booking, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Item damage deduction updated.');
    }

    public function updateStatus(Request $request, Order $booking): RedirectResponse
    {
        abort_unless($booking->vendor_id === $this->vendor()->id, 403);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', VendorBookingStatus::acceptedInputStatuses())],
        ]);

        $nextStatus = VendorBookingStatus::normalizeInput($data['status']);

        if (! in_array($nextStatus, Order::STATUSES, true)) {
            return back()->with('error', 'Invalid booking status.');
        }

        $booking->loadMissing('orderItems');
        if ($booking->orderItems->isNotEmpty()) {
            $active = $booking->orderItems->where('status', '!=', OrderItem::STATUS_CANCELLED);
            if ($active->isNotEmpty()) {
                $first = $active->first();
                $nextStatus = OrderItemStatusSupport::normalizeVendorRequestedStatus($first, $nextStatus, $booking);
            }
        }

        try {
            $this->items->updateBookingStatus($booking, $nextStatus);
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
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Damage deduction updated.');
    }

    protected function assertItemBelongs(Order $booking, OrderItem $item): void
    {
        abort_unless((int) $item->order_id === (int) $booking->id, 404);
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
                    throw new InvalidArgumentException('Item does not belong to this booking.');
                }
            } else {
                $returned = $booking->orderItems->where('status', 'returned')->values();
                if ($returned->count() === 1) {
                    $item = $returned->first();
                } elseif ($returned->isEmpty()) {
                    throw new InvalidArgumentException(
                        'Damage deduction can only be recorded for returned items. Pass item_id.'
                    );
                } else {
                    throw new InvalidArgumentException(
                        'Pass item_id to record damage for a specific returned item.'
                    );
                }
            }

            if ($item->status !== 'returned') {
                throw new InvalidArgumentException(
                    'Damage deduction can only be recorded when this item is Returned to Vendor.'
                );
            }

            $resolved = OrderItem::resolveDamageFields(
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
            throw new InvalidArgumentException(
                'Damage deduction can only be recorded for returned bookings.'
            );
        }

        $resolved = OrderItem::resolveDamageFields(
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
            fn (OrderItem $item) => $item->hasDamageRecord()
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
                fn (OrderItem $item) => $item->damageDeduction()
            ),
            2
        );

        $booking->update([
            'damage_note' => $withDamage->pluck('damage_note')->filter()->implode('; ') ?: null,
            'damage_amount' => $totalDeduction > 0 ? $totalDeduction : null,
            'damage_deduct_percent' => $withDamage->every(fn (OrderItem $item) => $item->damage_deduct_percent !== null)
                ? round(($totalDeduction / max(0.01, $booking->subtotal())) * 100, 2)
                : null,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Checkout\VendorBookingItemService;
use App\Support\Api\VendorApiPresenter;
use App\Support\Api\VendorBookingListStatus;
use App\Support\Api\VendorBookingStatus;
use App\Support\AppliesListDateFilter;
use App\Support\OrderItemStatusSupport;
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

        $request->validate([
            'tab' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'item_status' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $itemStatuses = $this->normalizeStatusFilter($request->input('item_status'));

        // Include unpaid `new` checkouts so vendors see bookings as soon as the
        // customer places them. Accept/reject still require paymentConfirmed.
        $query = Order::query()
            ->where('vendor_id', $vendor->id)
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
                $tab = $request->string('tab')->toString();
                if (! VendorBookingListStatus::applyTabFilter($q, $tab)) {
                    $statuses = VendorBookingStatus::statusesForTab($tab);
                    if ($statuses !== null) {
                        $q->whereIn('status', $statuses);
                    }
                }
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $raw = strtolower(trim(str_replace('_', '-', $request->string('status')->toString())));
                if (VendorBookingListStatus::applyTabFilter($q, $raw)) {
                    return;
                }

                $statuses = VendorBookingStatus::statusesForTab($raw);

                if ($statuses !== null) {
                    $q->whereIn('status', $statuses);

                    return;
                }

                $status = VendorBookingStatus::normalizeInput($request->string('status')->toString());
                $q->where('status', $status);
            })
            ->when($itemStatuses !== null, function ($q) use ($itemStatuses) {
                $q->where(function ($q) use ($itemStatuses) {
                    $q->whereHas('orderItems', fn ($items) => $items->whereIn('status', $itemStatuses))
                        // Legacy bookings without line items: match order status.
                        ->orWhere(function ($legacy) use ($itemStatuses) {
                            $legacy->whereDoesntHave('orderItems')
                                ->whereIn('status', $itemStatuses);
                        });
                });
            });

        $bookings = $this->applyDateRange($query, $request)
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success(
            VendorApiPresenter::paginator(
                $bookings,
                fn (Order $order) => VendorApiPresenter::bookingListSummary($order, $itemStatuses)
            )
        );
    }

    public function show(Request $request, string $booking): JsonResponse
    {
        // Detail should open for any booking the vendor owns (same id as list/home).
        $order = $this->resolveOwnedBooking($request, $booking, requirePaymentConfirmed: false);

        $request->validate([
            'item_status' => ['nullable', 'string', 'max:50'],
        ]);
        $itemStatuses = $this->normalizeStatusFilter($request->input('item_status'));

        return $this->success(
            VendorApiPresenter::bookingDetail(
                $order->load([
                    'customer.measurements',
                    'vendor',
                    'category',
                    'driver',
                    'review.customer',
                    'orderItems.driver',
                    'checkoutOrder',
                    'refunds',
                ]),
                $itemStatuses
            )
        );
    }

    /**
     * Normalize API status aliases into DB status list (or null if empty/invalid).
     *
     * @return list<string>|null
     */
    protected function normalizeStatusFilter(mixed $raw): ?array
    {
        if ($raw === null || trim((string) $raw) === '') {
            return null;
        }

        $key = strtolower(trim((string) $raw));
        $fromTab = VendorBookingStatus::statusesForTab($key);
        if ($fromTab !== null) {
            return $fromTab;
        }

        $normalized = VendorBookingStatus::normalizeInput($key);
        if (in_array($normalized, Order::STATUSES, true) || in_array($normalized, OrderItem::STATUSES, true)) {
            return [$normalized];
        }

        return null;
    }

    public function accept(Request $request, string $booking): JsonResponse
    {
        $order = $this->resolveOwnedBooking($request, $booking, requirePaymentConfirmed: false);

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
        $order = $this->resolveOwnedBooking($request, $booking, requirePaymentConfirmed: false);

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
        $order = $this->resolveOwnedBooking($request, $booking, requirePaymentConfirmed: false);
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
        $order = $this->resolveOwnedBooking($request, $booking, requirePaymentConfirmed: false);
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

    public function updateItemStatus(Request $request, string $booking, string $item): JsonResponse
    {
        $order = $this->resolveOwnedBooking($request, $booking, requirePaymentConfirmed: false);
        $orderItem = $this->resolveOwnedItem($order, $item);

        $data = $this->validateVendor($request, VendorValidationRules::bookingItemStatus());

        $nextStatus = VendorBookingStatus::normalizeInput($data['status']);

        // Vendor "returned" on an active rental = start Return In Transit (admin assigns driver).
        // Final "Returned to Vendor" is only from re_intransit after pickup completes.
        $requestedReturned = $nextStatus === 'returned';
        if (
            $requestedReturned
            && OrderItemStatusSupport::isRentalItem($orderItem, $order)
            && in_array($orderItem->status, ['delivered', 'rental_active'], true)
        ) {
            $nextStatus = 're_intransit';
        }

        $damagePayload = $this->damagePayloadFromRequest($data);
        $wantsDamage = $damagePayload !== null;

        try {
            // Complete after return: allow note + damage amount on the same call.
            if ($nextStatus === 'completed' && $wantsDamage) {
                if ($orderItem->status !== 'returned') {
                    return $this->error(
                        'Damage with complete is only allowed when the item is Returned to Vendor.',
                        422
                    );
                }
                if (! OrderItemStatusSupport::isRentalItem($orderItem, $order)) {
                    return $this->error(
                        'Damage deduction on complete applies to rented dress/jewellery only.',
                        422
                    );
                }

                $this->applyDamageDeduction($order, [
                    'item_id' => $orderItem->id,
                    ...$damagePayload,
                ]);
                $orderItem = $orderItem->fresh();
            } elseif ($wantsDamage && $nextStatus !== 'completed') {
                return $this->error(
                    'Pass damage_note / damage_amount only when status is completed (after return).',
                    422
                );
            }

            $updated = $this->items->updateItemStatus($order->fresh(['orderItems']), $orderItem->fresh(), $nextStatus);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $freshItem = $updated->orderItems->firstWhere('id', $orderItem->id) ?? $orderItem->fresh();

        $message = match (true) {
            $nextStatus === 'completed' && $wantsDamage => 'Item completed with damage deduction.',
            $nextStatus === 'completed' => 'Item marked completed.',
            $nextStatus === 're_intransit' && $requestedReturned => 'Return In Transit started for rented product. Admin can assign a return driver.',
            $nextStatus === 're_intransit' => 'Return In Transit started for rented product (return to vendor). Admin can assign a driver.',
            $nextStatus === 'returned' => 'Rental product marked as returned to vendor.',
            default => 'Item status updated. Booking status recalculated from items.',
        };

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($updated),
            'item' => VendorApiPresenter::orderLineItemDetail($freshItem, $updated),
            'fulfillment_state' => VendorApiPresenter::bookingFulfillmentState($updated),
            'return_type' => in_array($nextStatus, ['returned', 're_intransit'], true)
                ? 'rental_product_return'
                : null,
        ], $message);
    }

    public function updateStatus(Request $request, string $booking): JsonResponse
    {
        $order = $this->resolveOwnedBooking($request, $booking, requirePaymentConfirmed: false);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(VendorBookingStatus::acceptedInputStatuses())],
        ]);

        $nextStatus = VendorBookingStatus::normalizeInput($data['status']);

        if (! in_array($nextStatus, Order::STATUSES, true)) {
            return $this->error('Invalid booking status.', 422);
        }

        try {
            // Applies status to all active items, then rolls booking status up from items.
            $updated = $this->items->updateBookingStatus($order, $nextStatus);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($updated->load([
                'customer',
                'category',
                'driver',
                'orderItems',
                'checkoutOrder',
            ])),
            'fulfillment_state' => VendorApiPresenter::bookingFulfillmentState($updated),
        ], 'Booking status updated from item statuses.');
    }

    public function updateDamage(Request $request, string $booking): JsonResponse
    {
        $order = $this->resolveOwnedBooking($request, $booking, requirePaymentConfirmed: false);
        $order->loadMissing('orderItems');

        $data = $this->validateVendor($request, VendorValidationRules::bookingDamage());

        try {
            $this->applyDamageDeduction($order, $data);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

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

    public function updateItemDamage(Request $request, string $booking, OrderItem $item): JsonResponse
    {
        $order = $this->resolveOwnedBooking($request, $booking, requirePaymentConfirmed: false);
        $order->loadMissing('orderItems');

        if ((int) $item->order_id !== (int) $order->id) {
            return $this->error('Item does not belong to this booking.', 404);
        }

        $data = $this->validateVendor($request, VendorValidationRules::bookingDamage());
        $data['item_id'] = $item->id;

        try {
            $this->applyDamageDeduction($order, $data);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'booking' => VendorApiPresenter::bookingDetail($order->fresh([
                'customer',
                'category',
                'driver',
                'orderItems',
                'checkoutOrder',
            ])),
            'item' => VendorApiPresenter::orderLineItemDetail($item->fresh(), $order->fresh(['orderItems'])),
        ], 'Item damage deduction updated.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{damage_note: ?string, damage_deduct_percent: float|int|string|null}|null
     */
    protected function damagePayloadFromRequest(array $data): ?array
    {
        $note = $data['damage_note'] ?? $data['note'] ?? null;
        $hasAmount = array_key_exists('damage_amount', $data) || array_key_exists('damage_deduct_amount', $data);
        $hasPercent = array_key_exists('damage_deduct_percent', $data);
        $hasNote = array_key_exists('damage_note', $data) || array_key_exists('note', $data);

        if (! $hasAmount && ! $hasPercent && ! $hasNote) {
            return null;
        }

        // Explicit null/empty complete-with-damage still counts as "wants damage" only if amount/percent/note present with value?
        // Treat presence of any damage field key as wanting damage update.
        $amount = $data['damage_amount'] ?? $data['damage_deduct_amount'] ?? null;
        $percent = $data['damage_deduct_percent'] ?? null;

        if ($amount === null && $percent === null && ($note === null || $note === '')) {
            // All empty — ignore (complete without damage).
            return null;
        }

        return [
            'damage_note' => filled($note) ? (string) $note : null,
            'damage_amount' => $amount,
            'damage_deduct_percent' => $percent,
        ];
    }

    /**
     * @param  array{
     *     item_id?: int|null,
     *     note?: string|null,
     *     damage_note?: string|null,
     *     damage_amount?: float|int|string|null,
     *     damage_deduct_amount?: float|int|string|null,
     *     damage_deduct_percent?: float|int|string|null
     * }  $data
     */
    protected function applyDamageDeduction(Order $order, array $data): void
    {
        $order->loadMissing('orderItems');
        $itemId = isset($data['item_id']) ? (int) $data['item_id'] : null;
        $note = $data['damage_note'] ?? $data['note'] ?? null;
        $amount = $data['damage_amount'] ?? $data['damage_deduct_amount'] ?? null;
        $percent = $data['damage_deduct_percent'] ?? null;

        if ($order->orderItems->isNotEmpty()) {
            $item = null;
            if ($itemId) {
                $item = $order->orderItems->firstWhere('id', $itemId);
                if (! $item) {
                    throw new InvalidArgumentException('Item does not belong to this booking.');
                }
            } else {
                $returned = $order->orderItems->where('status', 'returned')->values();
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
                $amount,
                $percent,
                'item line amount'
            );

            $item->update([
                'damage_note' => filled($note) ? (string) $note : null,
                'damage_amount' => $resolved['damage_amount'],
                'damage_deduct_percent' => $resolved['damage_deduct_percent'],
            ]);

            $this->syncBookingDamageFromItems($order->fresh(['orderItems']));

            return;
        }

        if ($order->status !== 'returned') {
            throw new InvalidArgumentException(
                'Damage deduction can only be recorded for returned bookings.'
            );
        }

        $resolved = OrderItem::resolveDamageFields(
            max(0.01, $order->subtotal()),
            $amount,
            $percent,
            'booking subtotal'
        );

        $order->update([
            'damage_note' => filled($note) ? (string) $note : null,
            'damage_amount' => $resolved['damage_amount'],
            'damage_deduct_percent' => $resolved['damage_deduct_percent'],
        ]);
    }

    protected function syncBookingDamageFromItems(Order $order): void
    {
        $order->loadMissing('orderItems');
        $withDamage = $order->orderItems->filter(
            fn (OrderItem $item) => $item->hasDamageRecord()
        );

        if ($withDamage->isEmpty()) {
            $order->update([
                'damage_note' => null,
                'damage_amount' => null,
                'damage_deduct_percent' => null,
            ]);

            return;
        }

        $totalDeduction = round(
            (float) $order->orderItems->sum(fn (OrderItem $item) => $item->damageDeduction()),
            2
        );

        $order->update([
            'damage_note' => $withDamage->pluck('damage_note')->filter()->implode('; ') ?: null,
            'damage_amount' => $totalDeduction > 0 ? $totalDeduction : null,
            // Booking-level percent is informational only when every damaged item used percent.
            'damage_deduct_percent' => $withDamage->every(fn (OrderItem $item) => $item->damage_deduct_percent !== null)
                ? round(
                    ($totalDeduction / max(0.01, $order->subtotal())) * 100,
                    2
                )
                : null,
        ]);
    }
}

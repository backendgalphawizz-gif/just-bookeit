<?php

namespace App\Services\Booking;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Checkout\VendorBookingItemService;
use App\Support\OrderItemStatusSupport;
use InvalidArgumentException;

/**
 * Customer / vendor lifecycle actions for the booking architecture diagram.
 * Status changes flow through items when line items exist; booking rolls up.
 */
class BookingLifecycleService
{
    public function __construct(
        protected VendorBookingItemService $items
    ) {}

    /**
     * User confirms they received the order (diagram: Receive Order).
     * Rentals → rental_active; designers stay delivered (ready for rework/complete).
     */
    public function confirmReceived(Order $order): Order
    {
        if ($order->status !== 'delivered') {
            throw new InvalidArgumentException('Only delivered bookings can be confirmed as received.');
        }

        if ($order->isRental()) {
            return $this->items->setActiveItemsStatus($order, 'rental_active');
        }

        return $order;
    }

    /**
     * Customer requests pickup so rented dress/jewellery is returned to the vendor.
     * This is product return — not a dispute / refund return.
     *
     * Only rented-dress / rented-jewellery items are moved to Return In Transit.
     * Fashion-designer items are never included.
     */
    public function requestReturn(Order $order, ?OrderItem $item = null): Order
    {
        $order->loadMissing(['orderItems', 'category']);

        if ($item) {
            if ((int) $item->order_id !== (int) $order->id) {
                throw new InvalidArgumentException('Item does not belong to this booking.');
            }

            return $this->requestProductReturnForItem($order, $item);
        }

        $rentalItems = $order->orderItems
            ->filter(fn (OrderItem $line) => $this->isRentalProductItem($line, $order))
            ->values();

        // Legacy booking with no line items: keep order-level rental return.
        if ($rentalItems->isEmpty()) {
            if (! $order->isRental()) {
                throw new InvalidArgumentException(
                    'Product return applies to rented dress or jewellery only. Raise a dispute for other issues.'
                );
            }

            if (! in_array($order->status, ['rental_active', 'delivered'], true)) {
                throw new InvalidArgumentException(
                    'Return pickup can only be requested while the rental is active (or delivered).'
                );
            }

            if ($order->status === 'delivered') {
                $order = $this->items->setActiveItemsStatus($order, 'rental_active');
            }

            return $this->items->setActiveItemsStatus($order, 're_intransit');
        }

        $eligible = $rentalItems->filter(
            fn (OrderItem $line) => in_array($line->status, ['delivered', 'rental_active'], true)
        );

        if ($eligible->isEmpty()) {
            throw new InvalidArgumentException(
                'No rented dress/jewellery items are ready for return pickup.'
            );
        }

        $updated = $order;
        foreach ($eligible->pluck('id') as $itemId) {
            $line = OrderItem::query()->find($itemId);
            if (! $line) {
                continue;
            }
            $updated = $this->items->updateItemStatus($updated, $line, 're_intransit');
        }

        return $updated;
    }

    /**
     * User requests rework (diagram: Need Rework) — designer fittings or rental issues.
     */
    public function requestRework(Order $order, ?string $reason = null): Order
    {
        if (! in_array($order->status, ['delivered', 'rental_active', 're_delivered'], true)) {
            throw new InvalidArgumentException('Rework can only be requested after delivery / during rental.');
        }

        if ($reason) {
            $order->customer_notes = trim(($order->customer_notes ? $order->customer_notes."\n" : '').'Rework: '.$reason);
            $order->save();
        }

        return $this->items->setActiveItemsStatus($order, 'rework');
    }

    /**
     * Vendor/admin marks booking completed after return + settlement (diagram: Completed).
     */
    public function markCompleted(Order $order): Order
    {
        if (! in_array($order->status, ['returned', 're_delivered', 'delivered'], true)) {
            throw new InvalidArgumentException('Booking can only be completed after return, re-delivery, or designer delivery.');
        }

        return $this->items->updateBookingStatus($order, 'completed');
    }

    /**
     * Final order status after driver completes a leg.
     */
    public function statusAfterDriverDeliver(Order $order): string
    {
        if ($order->status === 're_intransit') {
            return $order->isRental() ? 'returned' : 're_delivered';
        }

        return 'delivered';
    }

    /**
     * Apply driver delivery completion to items + booking rollup.
     * When a driver is provided, only that driver's assigned items are updated.
     */
    public function completeDriverDelivery(Order $order, ?\App\Models\Driver $driver = null): Order
    {
        $next = $this->statusAfterDriverDeliver($order);

        return $this->items->setActiveItemsStatus($order, $next, $driver);
    }

    protected function requestProductReturnForItem(Order $order, OrderItem $item): Order
    {
        if (! $this->isRentalProductItem($item, $order)) {
            throw new InvalidArgumentException(
                'Product return applies to rented dress or jewellery only. This is not a dispute — use Raise Dispute for complaints.'
            );
        }

        if (! in_array($item->status, ['delivered', 'rental_active'], true)) {
            throw new InvalidArgumentException(
                'Return pickup can only be requested when this rental item is delivered or rental active.'
            );
        }

        return $this->items->updateItemStatus($order, $item, 're_intransit');
    }

    protected function isRentalProductItem(OrderItem $item, Order $order): bool
    {
        if ($item->status === OrderItem::STATUS_CANCELLED) {
            return false;
        }

        return OrderItemStatusSupport::isRentalItem($item, $order);
    }
}

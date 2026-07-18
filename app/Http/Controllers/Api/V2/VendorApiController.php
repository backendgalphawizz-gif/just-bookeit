<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\ApiController;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Models\VendorPortfolioImage;
use App\Support\VendorValidationRules;
use Illuminate\Http\Request;

abstract class VendorApiController extends ApiController
{
    protected function vendor(Request $request): Vendor
    {
        /** @var Vendor $vendor */
        $vendor = $request->user();

        return $vendor;
    }

    protected function assertOwnsOrder(Order $order, Vendor $vendor, bool $requirePaymentConfirmed = true): void
    {
        if ($order->vendor_id !== $vendor->id) {
            abort(response()->json([
                'success' => false,
                'message' => 'This booking does not belong to your vendor account.',
            ], 403));
        }

        if ($requirePaymentConfirmed && ! $order->isPaymentConfirmed()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Booking not available until payment is confirmed.',
                'payment_status' => $order->payment_status,
            ], 404));
        }
    }

    /**
     * Resolve a vendor booking by order id, order_number, or checkout_order_id.
     */
    protected function resolveOwnedBooking(Request $request, string|int $booking, bool $requirePaymentConfirmed = true): Order
    {
        $vendor = $this->vendor($request);
        $key = trim((string) $booking);

        $order = null;

        if (ctype_digit($key)) {
            $id = (int) $key;

            // Prefer exact order id first (matches list `id`).
            $order = Order::query()
                ->where('vendor_id', $vendor->id)
                ->where('id', $id)
                ->first();

            if (! $order) {
                $order = Order::query()
                    ->where('vendor_id', $vendor->id)
                    ->where('checkout_order_id', $id)
                    ->orderByDesc('id')
                    ->first();
            }
        }

        if (! $order) {
            $order = Order::query()
                ->where('vendor_id', $vendor->id)
                ->where('order_number', $key)
                ->first();
        }

        if (! $order) {
            abort(response()->json([
                'success' => false,
                'message' => 'Booking not found for this vendor.',
            ], 404));
        }

        $this->assertOwnsOrder($order, $vendor, $requirePaymentConfirmed);

        return $order;
    }

    protected function resolveOwnedItem(Order $booking, string|int $item): OrderItem
    {
        $orderItem = $booking->orderItems()->whereKey($item)->first();

        if (! $orderItem) {
            abort(response()->json([
                'success' => false,
                'message' => 'Item not found on this booking.',
            ], 404));
        }

        return $orderItem;
    }

    protected function assertOwnsProduct(PortfolioItem $product, Vendor $vendor): void
    {
        abort_unless($product->vendor_id === $vendor->id, 403);
    }

    protected function assertOwnsChat(Conversation $chat, Vendor $vendor): void
    {
        abort_unless($chat->vendor_id === $vendor->id, 403);
    }

    protected function assertOwnsPortfolioImage(VendorPortfolioImage $image, Vendor $vendor): void
    {
        abort_unless($image->vendor_id === $vendor->id, 403);
    }

    /** @return array<string, mixed> */
    protected function validateVendor(Request $request, array $rules): array
    {
        return $request->validate($rules, VendorValidationRules::messages(), VendorValidationRules::attributes());
    }
}

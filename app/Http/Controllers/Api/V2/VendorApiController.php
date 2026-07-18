<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\ApiController;
use App\Models\Order;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Models\VendorPortfolioImage;
use App\Models\Conversation;
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

    protected function assertOwnsOrder(Order $order, Vendor $vendor): void
    {
        abort_unless($order->vendor_id === $vendor->id, 403, 'This booking does not belong to your vendor account.');
        abort_unless(
            $order->isPaymentConfirmed(),
            404,
            'Booking not available until payment is confirmed (payment_status must be success).'
        );
    }

    /**
     * Resolve a vendor booking by order id, order_number, or checkout_order_id.
     */
    protected function resolveOwnedBooking(Request $request, string|int $booking): Order
    {
        $vendor = $this->vendor($request);
        $key = trim((string) $booking);

        $order = Order::query()
            ->where('vendor_id', $vendor->id)
            ->where(function ($query) use ($key) {
                $query->where('order_number', $key);

                if (ctype_digit($key)) {
                    $id = (int) $key;
                    $query->orWhere('id', $id)
                        ->orWhere('checkout_order_id', $id);
                }
            })
            ->orderByDesc('id')
            ->first();

        if (! $order) {
            abort(404, 'Booking not found for this vendor.');
        }

        $this->assertOwnsOrder($order, $vendor);

        return $order;
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

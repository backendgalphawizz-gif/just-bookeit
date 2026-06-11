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
        abort_unless($order->vendor_id === $vendor->id, 403);
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

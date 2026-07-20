<?php

namespace App\Services\Customer;

use App\Models\CartItem;
use App\Models\Customer;
use App\Models\PortfolioItem;
use App\Services\Booking\BookingPricingService;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CartService
{
    public function itemsFor(Customer $customer): Collection
    {
        return CartItem::query()
            ->with(['portfolioItem.vendor', 'portfolioItem.category', 'portfolioItem.subcategory.parent', 'portfolioItem.variants', 'variant', 'vendor'])
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->get();
    }

    public function findForProduct(Customer $customer, int $portfolioItemId, ?int $variantId = null): ?CartItem
    {
        return CartItem::query()
            ->where('customer_id', $customer->id)
            ->where('portfolio_item_id', $portfolioItemId)
            ->when(
                $variantId,
                fn ($query) => $query->where('portfolio_item_variant_id', $variantId),
                fn ($query) => $query->whereNull('portfolio_item_variant_id'),
            )
            ->first();
    }

    /** @return array<string, mixed> */
    public function apiPayload(Customer $customer, array $options = []): array
    {
        $items = $this->itemsFor($customer);

        return [
            'summary' => $this->summary($customer, $options),
            'items' => $items
                ->map(fn (CartItem $item) => CustomerApiPresenter::cartItem($item))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function itemStatusForProduct(Customer $customer, int $portfolioItemId): array
    {
        $cartItem = $this->findForProduct($customer, $portfolioItemId);

        return [
            'portfolio_item_id' => $portfolioItemId,
            'in_cart' => $cartItem !== null,
            'cart_item_id' => $cartItem?->id,
            'quantity' => $cartItem?->quantity,
            'message' => $cartItem ? 'This product is already in your cart.' : null,
        ];
    }

    /** @return array<string, mixed> */
    public function summary(Customer $customer, array $options = []): array
    {
        $items = $this->itemsFor($customer);
        $shipmentRequired = (bool) ($options['shipment_required'] ?? true);
        $subtotal = round($items->sum(function (CartItem $cartItem) {
            return round($cartItem->unitDailyRate() * $cartItem->quantity, 2);
        }), 2);
        $advanceAmount = round($items->sum(function (CartItem $cartItem) {
            $cartItem->loadMissing('portfolioItem');
            $unitAdvance = (float) ($cartItem->portfolioItem?->advance_amount ?? 0);

            return round($unitAdvance * $cartItem->quantity, 2);
        }), 2);

        $vendors = $items
            ->groupBy('vendor_id')
            ->map(function (Collection $group) use ($shipmentRequired) {
                $vendor = $group->first()?->vendor;
                $vendorSubtotal = round($group->sum(function (CartItem $cartItem) {
                    return round($cartItem->unitDailyRate() * $cartItem->quantity, 2);
                }), 2);
                $vendorAdvance = round($group->sum(function (CartItem $cartItem) {
                    $cartItem->loadMissing('portfolioItem');
                    $unitAdvance = (float) ($cartItem->portfolioItem?->advance_amount ?? 0);

                    return round($unitAdvance * $cartItem->quantity, 2);
                }), 2);
                $deliveryFee = BookingPricingService::shippingFee($shipmentRequired);
                $taxPercent = BookingPricingService::gstPercent();
                $taxAmount = round($vendorSubtotal * ($taxPercent / 100), 2);
                $totalAmount = round($vendorSubtotal + $deliveryFee + $taxAmount, 2);

                return [
                    'vendor_id' => (int) $group->first()->vendor_id,
                    'vendor_name' => $vendor?->brand_name ?? $vendor?->shop_name,
                    'items_count' => $group->sum('quantity'),
                    'subtotal' => $vendorSubtotal,
                    'advance_amount' => $vendorAdvance,
                    'delivery_fee' => $deliveryFee,
                    'tax_percent' => $taxPercent,
                    'gst_percent' => $taxPercent,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'remaining_amount' => round(max(0, $totalAmount - $vendorAdvance), 2),
                ];
            })
            ->values()
            ->all();

        $deliveryFeeTotal = round(collect($vendors)->sum('delivery_fee'), 2);
        $taxPercent = BookingPricingService::gstPercent();
        $taxAmount = round($subtotal * ($taxPercent / 100), 2);
        $totalAmount = round($subtotal + $deliveryFeeTotal + $taxAmount, 2);

        return [
            'items_count' => $items->sum('quantity'),
            'unique_items_count' => $items->count(),
            'vendor_count' => count($vendors),
            'vendors' => $vendors,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFeeTotal,
            'delivery_fee_total' => $deliveryFeeTotal,
            'tax_percent' => $taxPercent,
            'gst_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'advance_amount' => $advanceAmount,
            'remaining_amount' => round(max(0, $totalAmount - $advanceAmount), 2),
            'total_amount' => $totalAmount,
            'grand_total' => $totalAmount,
            'subtotal_label' => '₹'.number_format($subtotal, 0),
            'currency' => 'INR',
            'shipment_required' => $shipmentRequired,
            'single_vendor_only' => false,
            'multi_vendor_enabled' => true,
        ];
    }

    public function add(Customer $customer, int $portfolioItemId, int $quantity = 1, ?int $variantId = null): CartItem
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $portfolioItem = PortfolioItem::query()
            ->with(['vendor', 'variants'])
            ->find($portfolioItemId);

        if (! $portfolioItem || ! $portfolioItem->isCatalogAvailable()) {
            throw new InvalidArgumentException('This product is not available.');
        }

        $variant = null;

        if ($variantId) {
            $variant = $portfolioItem->findVariant($variantId);

            if (! $variant) {
                throw new InvalidArgumentException('The selected variant is not available.');
            }
        } else {
            $variantId = null;
        }

        $existing = $this->findForProduct($customer, $portfolioItem->id, $variantId);

        if ($existing) {
            throw new InvalidArgumentException('This product variant is already in your cart.');
        }

        $cartItem = CartItem::query()->create([
            'customer_id' => $customer->id,
            'portfolio_item_id' => $portfolioItem->id,
            'portfolio_item_variant_id' => $variant?->id,
            'vendor_id' => $portfolioItem->vendor_id,
            'quantity' => $quantity,
        ]);

        return $cartItem->fresh(['portfolioItem.vendor', 'portfolioItem.category', 'portfolioItem.subcategory.parent', 'portfolioItem.variants', 'variant', 'vendor']);
    }

    public function remove(Customer $customer, CartItem $cartItem): void
    {
        if ($cartItem->customer_id !== $customer->id) {
            throw new InvalidArgumentException('Cart item not found.');
        }

        $cartItem->delete();
    }
}

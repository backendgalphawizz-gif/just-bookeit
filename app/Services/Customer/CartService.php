<?php

namespace App\Services\Customer;

use App\Models\CartItem;
use App\Models\Customer;
use App\Models\PortfolioItem;
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
    public function apiPayload(Customer $customer): array
    {
        $items = $this->itemsFor($customer);

        return [
            'summary' => $this->summary($customer),
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
    public function summary(Customer $customer): array
    {
        $items = $this->itemsFor($customer);
        $subtotal = $items->sum(function (CartItem $cartItem) {
            return round($cartItem->unitDailyRate() * $cartItem->quantity, 2);
        });

        $vendors = $items
            ->groupBy('vendor_id')
            ->map(function (Collection $group) {
                $vendor = $group->first()?->vendor;
                $vendorSubtotal = $group->sum(function (CartItem $cartItem) {
                    return round($cartItem->unitDailyRate() * $cartItem->quantity, 2);
                });

                return [
                    'vendor_id' => (int) $group->first()->vendor_id,
                    'vendor_name' => $vendor?->brand_name ?? $vendor?->shop_name,
                    'items_count' => $group->sum('quantity'),
                    'subtotal' => round($vendorSubtotal, 2),
                    'delivery_fee' => \App\Services\Booking\BookingPricingService::shippingFee(true),
                ];
            })
            ->values()
            ->all();

        $deliveryFeeTotal = round(collect($vendors)->sum('delivery_fee'), 2);

        return [
            'items_count' => $items->sum('quantity'),
            'unique_items_count' => $items->count(),
            'vendor_count' => count($vendors),
            'vendors' => $vendors,
            'subtotal' => round($subtotal, 2),
            'delivery_fee_total' => $deliveryFeeTotal,
            'subtotal_label' => '₹'.number_format($subtotal, 0),
            'currency' => 'INR',
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

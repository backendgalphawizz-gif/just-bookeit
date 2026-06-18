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
            ->with(['portfolioItem.vendor', 'portfolioItem.category', 'portfolioItem.subcategory.parent', 'vendor'])
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->get();
    }

    public function findForProduct(Customer $customer, int $portfolioItemId): ?CartItem
    {
        return CartItem::query()
            ->where('customer_id', $customer->id)
            ->where('portfolio_item_id', $portfolioItemId)
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
            $price = (float) ($cartItem->portfolioItem?->rentalPriceAmount() ?? 0);

            return round($price * $cartItem->quantity, 2);
        });

        $vendor = $items->first()?->vendor;

        return [
            'items_count' => $items->sum('quantity'),
            'unique_items_count' => $items->count(),
            'vendor_id' => $vendor?->id,
            'vendor_name' => $vendor?->brand_name ?? $vendor?->shop_name,
            'subtotal' => round($subtotal, 2),
            'subtotal_label' => '₹'.number_format($subtotal, 0),
            'currency' => 'INR',
            'single_vendor_only' => true,
        ];
    }

    public function add(Customer $customer, int $portfolioItemId, int $quantity = 1): CartItem
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $portfolioItem = PortfolioItem::query()
            ->with('vendor')
            ->find($portfolioItemId);

        if (! $portfolioItem || ! $portfolioItem->isCatalogAvailable()) {
            throw new InvalidArgumentException('This product is not available.');
        }

        $existingVendorId = CartItem::query()
            ->where('customer_id', $customer->id)
            ->value('vendor_id');

        if ($existingVendorId !== null && (int) $existingVendorId !== (int) $portfolioItem->vendor_id) {
            throw new InvalidArgumentException('Cart can only contain products from one vendor. Remove existing items before adding from another vendor.');
        }

        $existing = $this->findForProduct($customer, $portfolioItem->id);

        if ($existing) {
            throw new InvalidArgumentException('This product is already in your cart.');
        }

        $cartItem = CartItem::query()->create([
            'customer_id' => $customer->id,
            'portfolio_item_id' => $portfolioItem->id,
            'vendor_id' => $portfolioItem->vendor_id,
            'quantity' => $quantity,
        ]);

        return $cartItem->fresh(['portfolioItem.vendor', 'portfolioItem.category', 'portfolioItem.subcategory.parent', 'vendor']);
    }

    public function remove(Customer $customer, CartItem $cartItem): void
    {
        if ($cartItem->customer_id !== $customer->id) {
            throw new InvalidArgumentException('Cart item not found.');
        }

        $cartItem->delete();
    }
}

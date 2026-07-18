<?php

namespace App\Services\Checkout;

use App\Models\CartItem;
use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PortfolioItem;
use App\Services\Booking\BookingPricingService;
use App\Services\Customer\CartService;
use App\Services\Vendor\VendorWalletService;
use App\Support\BookingMeasurementSupport;
use App\Support\CheckoutItemPayloadSupport;
use App\Support\CodeGenerator;
use App\Support\OrderDispatchSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(
        protected CartService $cart,
        protected CheckoutRollupService $rollup
    ) {}

    /** @return array<string, mixed> */
    public function preview(Customer $customer, array $data): array
    {
        $cartItems = $this->cart->itemsFor($customer);

        if ($cartItems->isEmpty()) {
            throw new InvalidArgumentException('Your cart is empty.');
        }

        $vendorShipments = $this->normalizeVendorShipments($data['vendor_shipments'] ?? [], $cartItems);
        $lineOverrides = CheckoutItemPayloadSupport::normalizeMap($data['items'] ?? null);
        $groups = $this->buildVendorGroups($cartItems, $data, $vendorShipments, $lineOverrides);

        $amount = round($groups->sum('subtotal'), 2);
        $deliveryFee = round($groups->sum('delivery_fee'), 2);
        $taxAmount = round($groups->sum('tax_amount'), 2);
        $grandTotal = round($amount + $deliveryFee + $taxAmount, 2);

        return [
            'vendors' => $groups->values()->all(),
            'summary' => [
                'items_count' => $cartItems->sum('quantity'),
                'vendor_count' => $groups->count(),
                'amount' => $amount,
                'delivery_fee' => $deliveryFee,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'currency' => 'INR',
            ],
        ];
    }

    public function createFromCart(Customer $customer, array $data, ?Request $request = null): CheckoutOrder
    {
        $cartItems = $this->cart->itemsFor($customer);

        if ($cartItems->isEmpty()) {
            throw new InvalidArgumentException('Your cart is empty.');
        }

        $profileId = $data['measurement_profile_id'] ?? $data['measurement_id'] ?? null;
        $profile = BookingMeasurementSupport::resolveProfile($customer, $data);

        if ($profileId && ! $profile) {
            throw new InvalidArgumentException('The selected measurement profile was not found.');
        }

        $measurements = BookingMeasurementSupport::normalizeFromProfileSelection($data, $profile);

        $vendorShipments = $this->normalizeVendorShipments($data['vendor_shipments'] ?? [], $cartItems);
        $lineOverrides = CheckoutItemPayloadSupport::normalizeMap($data['items'] ?? null, $request);
        $groups = $this->buildVendorGroups($cartItems, $data, $vendorShipments, $lineOverrides);

        return DB::transaction(function () use ($customer, $data, $cartItems, $measurements, $groups) {
            $parentNumber = CodeGenerator::orderNumber();

            $checkout = CheckoutOrder::query()->create([
                'order_number' => $parentNumber,
                'customer_id' => $customer->id,
                'status' => 'new',
                'payment_status' => 'pending',
                'amount' => round($groups->sum('subtotal'), 2),
                'delivery_fee' => round($groups->sum('delivery_fee'), 2),
                'tax_amount' => round($groups->sum('tax_amount'), 2),
                'grand_total' => round($groups->sum('grand_total'), 2),
                'delivery_address' => $data['delivery_address'],
                'billing_address' => $data['billing_address'] ?? $data['delivery_address'],
                'city' => $data['city'] ?? $customer->city,
                'pincode' => $data['pincode'] ?? null,
                'rental_start_date' => $data['rental_start_date'] ?? null,
                'rental_end_date' => $data['rental_end_date'] ?? null,
                'customer_notes' => filled($data['customer_notes'] ?? null) ? trim($data['customer_notes']) : null,
                'measure_height_cm' => $measurements['measure_height_cm'],
                'measure_chest_cm' => $measurements['measure_chest_cm'],
                'measure_waist_cm' => $measurements['measure_waist_cm'],
                'measurement_type' => $measurements['measurement_type'],
                'measure_extra' => $measurements['measure_extra'],
            ]);

            $sequence = 1;

            foreach ($groups as $group) {
                $subNumber = CodeGenerator::subOrderNumber($parentNumber, $sequence);
                $firstItem = $group['items'][0]['portfolio_item'];
                $lineStarts = $group['items']->pluck('rental_start_date')->filter()->values();
                $lineEnds = $group['items']->pluck('rental_end_date')->filter()->values();

                $subOrder = Order::query()->create([
                    'checkout_order_id' => $checkout->id,
                    'order_number' => $subNumber,
                    'sub_order_number' => $subNumber,
                    'customer_id' => $customer->id,
                    'vendor_id' => $group['vendor_id'],
                    'category_id' => $firstItem->category_id,
                    'portfolio_item_id' => $firstItem->id,
                    'subcategory_id' => $firstItem->subcategory_id,
                    'order_type' => 'rental',
                    'item_title' => $group['items']->count() > 1
                        ? $group['vendor_name'].' — '.$group['items']->count().' items'
                        : $firstItem->title,
                    'item_description' => $firstItem->description,
                    'item_image_path' => $firstItem->image_url,
                    'quantity' => $group['items']->sum('quantity'),
                    'rental_start_date' => $lineStarts->min() ?? ($data['rental_start_date'] ?? null),
                    'rental_end_date' => $lineEnds->max() ?? ($data['rental_end_date'] ?? null),
                    'delivery_address' => $data['delivery_address'],
                    'billing_address' => $data['billing_address'] ?? $data['delivery_address'],
                    'city' => $data['city'] ?? $customer->city,
                    'pincode' => $data['pincode'] ?? null,
                    'amount' => $group['subtotal'],
                    'delivery_fee' => $group['delivery_fee'],
                    'tax_amount' => $group['tax_amount'],
                    'payment_status' => 'pending',
                    'status' => 'new',
                ]);

                OrderDispatchSupport::preparePickupAddress($subOrder);
                if (filled($subOrder->pickup_address)) {
                    $subOrder->saveQuietly();
                }

                foreach ($group['items'] as $line) {
                    /** @var PortfolioItem $portfolioItem */
                    $portfolioItem = $line['portfolio_item'];
                    $variant = $line['variant'] ?? null;
                    $override = $line['override'] ?? [];

                    OrderItem::query()->create([
                        'order_id' => $subOrder->id,
                        'portfolio_item_id' => $portfolioItem->id,
                        'vendor_id' => $group['vendor_id'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'line_amount' => $line['line_amount'],
                        'status' => OrderItem::STATUS_PENDING,
                        'item_snapshot' => array_merge([
                            'title' => $portfolioItem->title,
                            'image_url' => $variant?->image_path ?: $portfolioItem->image_url,
                            'category' => $portfolioItem->category?->name,
                            'category_slug' => $portfolioItem->category?->slug,
                            'service_type' => $override['service_type']
                                ?? $portfolioItem->category?->slug
                                ?? null,
                            'size' => $variant?->size ?? ($override['size'] ?? null),
                            'color' => $variant?->color ?? ($override['color'] ?? null),
                            'variant_id' => $variant?->id ?? ($override['portfolio_item_variant_id'] ?? null),
                        ], CheckoutItemPayloadSupport::itemSnapshotExtras($override, $data)),
                    ]);
                }

                $sequence++;
            }

            foreach ($cartItems as $cartItem) {
                $cartItem->delete();
            }

            if ($customer->city === null && ! empty($data['city'])) {
                $customer->update(['city' => $data['city']]);
            }

            $count = Order::query()->where('customer_id', $customer->id)->count();
            $customer->update(['total_orders' => $count]);

            return $checkout->fresh(['subOrders.orderItems', 'subOrders.vendor', 'subOrders.category']);
        });
    }

    public function markPaid(CheckoutOrder $checkout, string $paymentMethod): CheckoutOrder
    {
        if ($checkout->payment_status === 'success') {
            return $checkout;
        }

        return DB::transaction(function () use ($checkout, $paymentMethod) {
            $checkout->update([
                'payment_status' => 'success',
                'payment_method' => $paymentMethod,
                'paid_at' => now(),
                'status' => 'pending_acceptance',
            ]);

            $checkout->subOrders()->each(function (Order $subOrder) use ($paymentMethod) {
                $subOrder->update([
                    'payment_status' => 'success',
                    'payment_method' => $paymentMethod,
                    'paid_at' => now(),
                    'status' => $subOrder->status === 'new' ? 'pending_acceptance' : $subOrder->status,
                ]);

                app(VendorWalletService::class)->creditFromPayment($subOrder->fresh());
            });

            return $this->rollup->sync($checkout->fresh(['subOrders.orderItems', 'subOrders.vendor']));
        });
    }

    /**
     * @param  list<array<string, mixed>>  $vendorShipments
     * @return array<int, bool>
     */
    protected function normalizeVendorShipments(array $vendorShipments, Collection $cartItems): array
    {
        $map = [];

        foreach ($vendorShipments as $row) {
            if (! isset($row['vendor_id'])) {
                continue;
            }

            $map[(int) $row['vendor_id']] = (bool) ($row['shipment_required'] ?? true);
        }

        foreach ($cartItems->pluck('vendor_id')->unique() as $vendorId) {
            $map[(int) $vendorId] = $map[(int) $vendorId] ?? true;
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $checkoutData
     * @param  array<int, bool>  $vendorShipments
     * @param  array<string, array<string, mixed>>  $lineOverrides
     */
    protected function buildVendorGroups(
        Collection $cartItems,
        array $checkoutData,
        array $vendorShipments,
        array $lineOverrides = []
    ): Collection {
        return $cartItems
            ->groupBy('vendor_id')
            ->map(function (Collection $items, $vendorId) use ($checkoutData, $vendorShipments, $lineOverrides) {
                $vendor = $items->first()?->vendor;
                $lines = $items->map(function (CartItem $cartItem) use ($checkoutData, $lineOverrides) {
                    $portfolioItem = $cartItem->portfolioItem;
                    $cartItem->loadMissing('variant');

                    if (! $portfolioItem || ! $portfolioItem->isCatalogAvailable()) {
                        throw new InvalidArgumentException('A product in your cart is no longer available.');
                    }

                    $override = CheckoutItemPayloadSupport::resolveOverride($lineOverrides, $cartItem);
                    $rental = CheckoutItemPayloadSupport::rentalWindow($override, $checkoutData);

                    $pricing = BookingPricingService::forPortfolioItem($portfolioItem, [
                        'rental_days' => $rental['days'],
                        'shipment_required' => false,
                        'daily_rate' => $portfolioItem->dailyRateFor($cartItem->variant),
                    ]);

                    $lineSubtotal = round((float) $pricing['subtotal'] * $cartItem->quantity, 2);

                    return [
                        'cart_item' => $cartItem,
                        'portfolio_item' => $portfolioItem,
                        'variant' => $cartItem->variant,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => (float) $pricing['daily_rate'],
                        'line_amount' => $lineSubtotal,
                        'override' => $override,
                        'rental_start_date' => $rental['start'],
                        'rental_end_date' => $rental['end'],
                    ];
                });

                $subtotal = round($lines->sum('line_amount'), 2);
                $shipmentRequired = $vendorShipments[(int) $vendorId] ?? true;
                $deliveryFee = BookingPricingService::shippingFee($shipmentRequired);
                $taxAmount = round($subtotal * (BookingPricingService::gstPercent() / 100), 2);
                $grandTotal = round($subtotal + $deliveryFee + $taxAmount, 2);

                return [
                    'vendor_id' => (int) $vendorId,
                    'vendor_name' => $vendor?->brand_name ?? $vendor?->shop_name ?? 'Vendor',
                    'shipment_required' => $shipmentRequired,
                    'items' => $lines,
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $grandTotal,
                ];
            })
            ->values();
    }
}

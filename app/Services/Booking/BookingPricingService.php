<?php

namespace App\Services\Booking;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use Carbon\Carbon;

class BookingPricingService
{
    public const DEFAULT_SHIPPING = 450.0;

    public const DEFAULT_GST_PERCENT = 18.0;

    public static function rentalDays(?string $startDate, ?string $endDate): ?int
    {
        if (! $startDate || ! $endDate) {
            return null;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        return max(1, (int) $start->diffInDays($end) + 1);
    }

    /** Days used for amount calculation (1 when no rental window — e.g. fashion designer). */
    public static function billingDays(?string $startDate, ?string $endDate): int
    {
        return self::rentalDays($startDate, $endDate) ?? 1;
    }

    public static function forPortfolioItem(PortfolioItem $item, array $options = []): array
    {
        $item->loadMissing('category');
        $requiresRentalPeriod = (bool) ($options['requires_rental_period'] ?? $item->requiresRentalPeriod());
        $variant = $options['variant'] ?? null;
        $variantModel = $variant instanceof \App\Models\PortfolioItemVariant ? $variant : null;
        $dailyRate = (float) ($options['daily_rate'] ?? $item->dailyRateFor($variantModel));

        $rentalDays = array_key_exists('rental_days', $options)
            ? ($options['rental_days'] !== null ? max(1, (int) $options['rental_days']) : null)
            : null;

        $billingDays = max(1, (int) ($rentalDays ?? 1));
        $subtotal = round($dailyRate * $billingDays, 2);
        $shipping = (float) ($options['shipping_fee'] ?? self::shippingFee($options['shipment_required'] ?? true));
        $gstPercent = (float) ($options['gst_percent'] ?? self::gstPercent());
        $tax = round($subtotal * ($gstPercent / 100), 2);
        $total = round($subtotal + $shipping + $tax, 2);
        $advanceAmount = self::resolveAdvanceAmount($item, $options, $total);

        return [
            'daily_rate' => $dailyRate,
            'rental_days' => $requiresRentalPeriod ? $rentalDays : null,
            'billing_days' => $billingDays,
            'requires_rental_period' => $requiresRentalPeriod,
            'subtotal' => $subtotal,
            'shipping_fee' => $shipping,
            'tax_percent' => $gstPercent,
            'tax_amount' => $tax,
            'advance_amount' => $advanceAmount,
            'remaining_amount' => round(max(0, $total - $advanceAmount), 2),
            'total_amount' => $total,
            'currency' => (string) PlatformSetting::get('currency', 'INR'),
        ];
    }

    public static function fromOrder(Order $order): array
    {
        $order->loadMissing(['portfolioItem', 'orderItems.portfolioItem', 'category']);
        $total = $order->grandTotal();
        $rentalDays = $order->rentalDurationDays();
        $requiresRentalPeriod = $order->requiresRentalPeriod();

        if ($order->orderItems->isNotEmpty()) {
            $advanceAmount = round($order->orderItems->sum(fn (OrderItem $item) => $item->advanceAmount()), 2);
        } else {
            $advanceAmount = $order->portfolioItem?->advance_amount !== null
                ? round((float) $order->portfolioItem->advance_amount, 2)
                : 0.0;
        }

        return [
            'daily_rate' => $rentalDays
                ? round($order->subtotal() / max(1, $rentalDays), 2)
                : $order->subtotal(),
            'rental_days' => $rentalDays,
            'billing_days' => max(1, $rentalDays ?? 1),
            'requires_rental_period' => $requiresRentalPeriod,
            'subtotal' => $order->subtotal(),
            'shipping_fee' => (float) ($order->delivery_fee ?? 0),
            'tax_percent' => self::gstPercent(),
            'tax_amount' => (float) ($order->tax_amount ?? 0),
            'advance_amount' => $advanceAmount,
            'remaining_amount' => round(max(0, $total - $advanceAmount), 2),
            'total_amount' => $total,
            'currency' => (string) PlatformSetting::get('currency', 'INR'),
        ];
    }

    /** @param array<string, mixed> $options */
    protected static function resolveAdvanceAmount(PortfolioItem $item, array $options, float $total): float
    {
        if (array_key_exists('advance_amount', $options) && $options['advance_amount'] !== null) {
            return round(max(0, (float) $options['advance_amount']), 2);
        }

        $variant = $options['variant'] ?? null;

        return $item->advanceAmountFor($variant instanceof \App\Models\PortfolioItemVariant ? $variant : null);
    }

    public static function vendorPaymentSummary(Order $order, ?Vendor $vendor = null): array
    {
        $subtotal = $order->subtotal();
        $shippingFee = (float) ($order->delivery_fee ?? 0);
        $taxAmount = (float) ($order->tax_amount ?? 0);
        $damageDeduction = round((float) $order->damageDeduction(), 2);
        $totalAmount = $order->grandTotal();
        $commissionPercent = self::commissionPercent($vendor);
        $platformFee = round($subtotal * ($commissionPercent / 100), 2);
        $vendorNet = $order->vendor_net_amount !== null
            ? (float) $order->vendor_net_amount
            : max(0, round($totalAmount - $platformFee, 2));

        return [
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'damage_deduction' => $damageDeduction,
            'damage_note' => $order->damage_note,
            'damage_deduct_percent' => $order->damage_deduct_percent !== null
                ? (float) $order->damage_deduct_percent
                : null,
            'platform_fee' => $platformFee,
            'platform_fee_percent' => $commissionPercent,
            'tax_amount' => $taxAmount,
            'tax_percent' => self::gstPercent(),
            'total_amount' => $totalAmount,
            'vendor_net_amount' => $vendorNet,
            'currency' => (string) PlatformSetting::get('currency', 'INR'),
        ];
    }

    public static function commissionPercent(?Vendor $vendor = null): float
    {
        if ($vendor && $vendor->commission !== null) {
            return (float) $vendor->commission;
        }

        return (float) PlatformSetting::get('global_commission_percent', 10);
    }

    public static function shippingFee(bool $shipmentRequired = true): float
    {
        if (! $shipmentRequired) {
            return 0.0;
        }

        return (float) PlatformSetting::get('default_shipping_fee', self::DEFAULT_SHIPPING);
    }

    public static function gstPercent(): float
    {
        return (float) PlatformSetting::get('default_gst_percent', self::DEFAULT_GST_PERCENT);
    }
}

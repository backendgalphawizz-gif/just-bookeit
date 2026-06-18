<?php

namespace App\Services\Booking;

use App\Models\Order;
use App\Models\PlatformSetting;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use Carbon\Carbon;

class BookingPricingService
{
    public const DEFAULT_SHIPPING = 450.0;

    public const DEFAULT_GST_PERCENT = 18.0;

    public static function rentalDays(?string $startDate, ?string $endDate): int
    {
        if (! $startDate || ! $endDate) {
            return 1;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        return max(1, (int) $start->diffInDays($end) + 1);
    }

    public static function forPortfolioItem(PortfolioItem $item, array $options = []): array
    {
        $dailyRate = (float) ($options['daily_rate'] ?? $item->rentalPriceAmount());
        $rentalDays = max(1, (int) ($options['rental_days'] ?? 1));
        $subtotal = round($dailyRate * $rentalDays, 2);
        $shipping = (float) ($options['shipping_fee'] ?? self::shippingFee($options['shipment_required'] ?? true));
        $gstPercent = (float) ($options['gst_percent'] ?? self::gstPercent());
        $tax = round($subtotal * ($gstPercent / 100), 2);
        $total = round($subtotal + $shipping + $tax, 2);

        return [
            'daily_rate' => $dailyRate,
            'rental_days' => $rentalDays,
            'subtotal' => $subtotal,
            'shipping_fee' => $shipping,
            'tax_percent' => $gstPercent,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'currency' => (string) PlatformSetting::get('currency', 'INR'),
        ];
    }

    public static function fromOrder(Order $order): array
    {
        return [
            'daily_rate' => $order->rentalDurationDays() ? round($order->subtotal() / max(1, $order->rentalDurationDays()), 2) : $order->subtotal(),
            'rental_days' => $order->rentalDurationDays() ?? 1,
            'subtotal' => $order->subtotal(),
            'shipping_fee' => (float) ($order->delivery_fee ?? 0),
            'tax_percent' => self::gstPercent(),
            'tax_amount' => (float) ($order->tax_amount ?? 0),
            'total_amount' => $order->grandTotal(),
            'currency' => (string) PlatformSetting::get('currency', 'INR'),
        ];
    }

    public static function vendorPaymentSummary(Order $order, ?Vendor $vendor = null): array
    {
        $subtotal = $order->subtotal();
        $shippingFee = (float) ($order->delivery_fee ?? 0);
        $taxAmount = (float) ($order->tax_amount ?? 0);
        $totalAmount = $order->grandTotal();
        $commissionPercent = self::commissionPercent($vendor);
        $platformFee = round($subtotal * ($commissionPercent / 100), 2);
        $vendorNet = $order->vendor_net_amount !== null
            ? (float) $order->vendor_net_amount
            : max(0, round($totalAmount - $platformFee, 2));

        return [
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
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

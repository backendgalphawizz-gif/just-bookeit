<?php

namespace App\Services\Booking;

use App\Models\Order;
use App\Models\PlatformSetting;
use App\Models\PortfolioItem;

class BookingPricingService
{
    public const DEFAULT_SHIPPING = 450.0;

    public const DEFAULT_GST_PERCENT = 18.0;

    public static function forPortfolioItem(PortfolioItem $item, array $options = []): array
    {
        $subtotal = (float) ($options['subtotal'] ?? $item->rentalPriceAmount());
        $shipping = (float) ($options['shipping_fee'] ?? self::shippingFee($options['shipment_required'] ?? true));
        $gstPercent = (float) ($options['gst_percent'] ?? self::gstPercent());
        $tax = round($subtotal * ($gstPercent / 100), 2);
        $total = round($subtotal + $shipping + $tax, 2);

        return [
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
            'subtotal' => $order->subtotal(),
            'shipping_fee' => (float) ($order->delivery_fee ?? 0),
            'tax_percent' => self::gstPercent(),
            'tax_amount' => (float) ($order->tax_amount ?? 0),
            'total_amount' => $order->grandTotal(),
            'currency' => (string) PlatformSetting::get('currency', 'INR'),
        ];
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

<?php

namespace App\Support\Api;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Models\VendorPortfolioImage;
use App\Models\VendorWalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VendorApiPresenter
{
    public static function paginator(LengthAwarePaginator $paginator, callable $mapper): array
    {
        return CustomerApiPresenter::paginator($paginator, $mapper);
    }

    public static function vendorSummary(Vendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'vendor_code' => $vendor->vendor_code,
            'name' => $vendor->owner_name,
            'brand_name' => $vendor->brand_name,
            'shop_name' => $vendor->shop_name,
            'mobile' => $vendor->mobile,
            'email' => $vendor->email,
            'city' => $vendor->city,
            'status' => $vendor->status,
            'is_available' => (bool) $vendor->is_listing_active,
            'profile_image_url' => $vendor->profileImageUrl(),
            'shop_logo_url' => $vendor->shopLogoUrl(),
            'rating' => (float) $vendor->rating,
        ];
    }

    public static function vendorAccount(Vendor $vendor): array
    {
        return [
            ...self::vendorSummary($vendor),
            'bio' => $vendor->bio,
            'cover_image_url' => $vendor->coverImageUrl(),
            'service_types' => $vendor->service_types,
            'business' => self::vendorBusiness($vendor),
            'bank' => self::vendorBank($vendor),
        ];
    }

    /** @return array<string, mixed> */
    public static function vendorBusiness(Vendor $vendor): array
    {
        return [
            'shop_name' => $vendor->shop_name,
            'brand_name' => $vendor->brand_name,
            'service_types' => $vendor->service_types
                ? array_values(array_filter(array_map('trim', explode(',', $vendor->service_types))))
                : [],
            'business_mobile' => $vendor->business_mobile,
            'business_email' => $vendor->business_email,
            'gst_number' => $vendor->gst_number,
            'address' => $vendor->address,
            'country' => $vendor->country,
            'state' => $vendor->state,
            'city' => $vendor->city,
            'pincode' => $vendor->pincode,
        ];
    }

    /** @return array<string, mixed> */
    public static function vendorBank(Vendor $vendor): array
    {
        return [
            'account_name' => $vendor->account_name,
            'account_number' => $vendor->account_number,
            'bank_name' => $vendor->bank_name,
            'ifsc_code' => $vendor->ifsc_code,
            'account_type' => $vendor->account_type,
        ];
    }

    public static function orderStats(array $stats): array
    {
        return [
            'total_orders' => [
                'today' => $stats['total_orders_today'],
                'ytd' => $stats['total_orders_ytd'],
            ],
            'completed' => [
                'today' => $stats['completed_today'],
                'ytd' => $stats['completed_ytd'],
            ],
            'new_orders' => [
                'today' => $stats['new_today'],
            ],
            'in_progress' => [
                'today' => $stats['in_progress_today'],
            ],
        ];
    }

    public static function earningsSummary(array $stats): array
    {
        return [
            'month_label' => now()->format('F Y'),
            'this_month' => round($stats['earnings_month'], 2),
            'ytd' => round($stats['earnings_ytd'], 2),
            'currency' => 'INR',
            'last_updated_at' => now()->format('M d, Y g:i A'),
        ];
    }

    public static function scheduleItem(Order $order): array
    {
        $order->loadMissing(['customer', 'category']);

        $statusLabel = match ($order->status) {
            'in_transit' => 'OUT FOR DELIVERY',
            'accepted', 'in_progress' => 'PENDING PICKUP',
            'delivered' => 'DELIVERED',
            default => strtoupper($order->statusLabel()),
        };

        $dateLabel = $order->rental_start_date?->format('d M')
            ?? $order->rental_end_date?->format('d M')
            ?? $order->updated_at?->format('d M');

        return [
            'id' => $order->id,
            'title' => $order->itemDisplayName(),
            'order_number' => $order->order_number,
            'item_number' => $order->id,
            'status' => $order->status,
            'status_label' => $statusLabel,
            'schedule_date' => $dateLabel,
            'summary' => trim(implode(' • ', array_filter([
                $dateLabel,
                'Order #'.$order->order_number,
                'Item #'.$order->id,
            ]))),
            'customer_name' => $order->customer?->name,
        ];
    }

    public static function bookingSummary(Order $order): array
    {
        $order->loadMissing(['customer', 'category']);

        return [
            'id' => $order->id,
            'booking_id' => $order->order_number,
            'product_name' => $order->itemDisplayName(),
            'product_image_url' => $order->itemImageUrl(),
            'customer_name' => $order->customer?->name,
            'amount' => (float) $order->amount,
            'amount_label' => '₹'.number_format((float) $order->amount, 0),
            'category' => $order->category?->name,
            'order_type' => $order->order_type === 'rental' ? 'Rental' : 'Sale',
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'payment_status' => $order->payment_status,
            'booking_date' => $order->created_at?->format('M d, Y'),
            'rental_start_date' => $order->rental_start_date?->format('d M'),
            'rental_end_date' => $order->rental_end_date?->format('d M'),
            'rental_period' => $order->rental_start_date && $order->rental_end_date
                ? $order->rental_start_date->format('d M').' - '.$order->rental_end_date->format('d M')
                : null,
            'can_accept' => in_array($order->status, ['new', 'pending_acceptance'], true),
            'can_reject' => in_array($order->status, ['new', 'pending_acceptance'], true),
        ];
    }

    public static function bookingDetail(Order $order): array
    {
        $order->loadMissing(['customer', 'category', 'driver']);

        return [
            ...self::bookingSummary($order),
            'delivery_address' => $order->delivery_address,
            'pickup_address' => $order->pickup_address,
            'city' => $order->city,
            'pincode' => $order->pincode,
            'customer_notes' => $order->customer_notes,
            'reference_image_urls' => $order->referenceImageUrls(),
            'measurements' => [
                'height_cm' => $order->measure_height_cm,
                'chest_cm' => $order->measure_chest_cm,
                'waist_cm' => $order->measure_waist_cm,
            ],
            'tracking_steps' => $order->trackBookingSteps(),
            'driver' => $order->driver ? [
                'id' => $order->driver->id,
                'name' => $order->driver->name,
                'mobile' => $order->driver->mobile,
            ] : null,
        ];
    }

    public static function productSummary(PortfolioItem $item): array
    {
        $item->loadMissing(['category', 'vendor', 'variants', 'damageDeductions']);

        return [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'image_url' => $item->displayImageUrl() ? url($item->displayImageUrl()) : null,
            'gallery_image_urls' => collect($item->galleryImageUrls())
                ->map(fn ($path) => str_starts_with($path, 'http') ? $path : url($path))
                ->values()
                ->all(),
            'price_per_day' => (float) ($item->price_per_day ?? $item->rentalPriceAmount()),
            'advance_amount' => $item->advance_amount !== null ? (float) $item->advance_amount : null,
            'price_label' => $item->rentalPriceLabel(),
            'audience' => $item->audience,
            'variants' => $item->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'size' => $variant->size,
                'color' => $variant->color,
                'price' => (float) $variant->price,
                'image_url' => $variant->imageUrl(),
            ])->values()->all(),
            'damage_deductions' => $item->damageDeductions->map(fn ($rule) => [
                'id' => $rule->id,
                'damage_type' => $rule->damage_type,
                'percent' => (float) $rule->percent,
            ])->values()->all(),
            'rating' => (float) ($item->vendor?->rating ?? 0),
            'brand_name' => strtoupper($item->vendor?->brand_name ?? ''),
            'category' => $item->category ? CustomerApiPresenter::category($item->category) : null,
            'category_type' => $item->category?->slug,
            'status' => $item->status,
            'rejection_reason' => $item->rejection_reason,
            'updated_at' => $item->updated_at?->format('M d, Y'),
        ];
    }

    public static function productDetail(PortfolioItem $item): array
    {
        $item->loadMissing(['category', 'vendor', 'images', 'variants', 'damageDeductions']);

        return self::productSummary($item);
    }

    public static function chatSummary(Conversation $conversation): array
    {
        $conversation->loadMissing(['customer', 'latestMessage']);
        $customer = $conversation->customer;
        $latest = $conversation->latestMessage;
        $unread = $conversation->unreadCountForVendor();

        return [
            'id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'customer_name' => $customer?->name,
            'customer_image_url' => $customer?->profileImageUrl(),
            'is_online' => true,
            'last_message' => $latest?->body,
            'last_message_at' => $latest?->created_at?->toIso8601String(),
            'time_label' => $latest?->created_at?->diffForHumans(),
            'has_unread' => $unread > 0,
            'unread_count' => $unread,
        ];
    }

    public static function chatDetail(Conversation $conversation): array
    {
        $conversation->loadMissing(['customer', 'latestMessage']);

        return [
            ...self::chatSummary($conversation),
            'customer' => [
                'id' => $conversation->customer?->id,
                'name' => $conversation->customer?->name,
                'mobile' => $conversation->customer?->mobile,
                'email' => $conversation->customer?->email,
                'profile_image_url' => $conversation->customer?->profileImageUrl(),
            ],
        ];
    }

    public static function chatMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'body' => $message->body,
            'attachment_url' => $message->attachmentUrl(),
            'is_mine' => $message->sender_type === ChatMessage::SENDER_VENDOR,
            'is_read' => $message->read_at !== null,
            'sent_at' => $message->created_at?->format('g:i A'),
            'sent_at_iso' => $message->created_at?->toIso8601String(),
            'date_label' => $message->created_at?->isToday() ? 'TODAY' : $message->created_at?->format('M d, Y'),
        ];
    }

    public static function walletTransaction(VendorWalletTransaction $transaction): array
    {
        $transaction->loadMissing(['order.customer']);

        return [
            'id' => $transaction->id,
            'transaction_id' => 'TXN-'.$transaction->id,
            'customer_name' => $transaction->order?->customer?->name,
            'booking_id' => $transaction->order?->order_number,
            'amount' => (float) $transaction->amount,
            'amount_label' => '₹'.number_format((float) $transaction->amount, 0),
            'type' => strtoupper($transaction->direction),
            'direction' => $transaction->direction,
            'wallet' => $transaction->wallet,
            'wallet_label' => $transaction->walletLabel(),
            'description' => $transaction->description ?? $transaction->typeLabel(),
            'created_at' => $transaction->created_at?->format('M d, Y, g:i A'),
            'created_at_iso' => $transaction->created_at?->toIso8601String(),
        ];
    }

    public static function portfolioImage(VendorPortfolioImage $image): array
    {
        return [
            'id' => $image->id,
            'audience' => $image->audience,
            'image_url' => $image->imageUrl(),
            'sort_order' => $image->sort_order,
        ];
    }

    public static function promoBanner(?object $banner): ?array
    {
        if (! $banner) {
            return null;
        }

        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'image_url' => $banner->image_url ? url($banner->image_url) : null,
            'link_url' => $banner->link_url,
        ];
    }
}

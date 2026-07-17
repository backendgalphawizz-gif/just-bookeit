<?php

namespace App\Support\Api;

use App\Models\Banner;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\CheckoutOrder;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerMeasurement;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReview;
use App\Models\Refund;
use App\Models\PortfolioItem;
use App\Models\PortfolioItemVariant;
use App\Models\SupportTicket;
use App\Models\VendorPortfolioImage;
use App\Models\Vendor;
use App\Services\Booking\BookingPricingService;
use App\Support\BookingMeasurementSupport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CustomerApiPresenter
{
    public static function fullUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url('/storage/'.ltrim(str_replace('\\', '/', $path), '/'));
    }

    public static function paginator(LengthAwarePaginator $paginator, callable $mapper): array
    {
        return [
            'items' => collect($paginator->items())->map($mapper)->values()->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }

    public static function banner(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'subtitle' => $banner->subtitle,
            'redirect_url' => $banner->redirect_url,
            'image_url' => self::fullUrl($banner->image_path) ?? $banner->image_url,
        ];
    }

    public static function category(Category $category, bool $includeSubcategories = false): array
    {
        $payload = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'type' => $category->type,
            'parent_id' => $category->parent_id,
            'image_url' => $category->imageUrl(),
        ];

        if ($category->isSub()) {
            $payload['service_category_id'] = $category->service_category_id;

            if ($category->relationLoaded('serviceCategory') && $category->serviceCategory) {
                $payload['service_type'] = $category->serviceCategory->slug;
                $payload['service_category'] = [
                    'id' => $category->serviceCategory->id,
                    'name' => $category->serviceCategory->name,
                    'slug' => $category->serviceCategory->slug,
                ];
            }
        }

        if ($includeSubcategories && $category->relationLoaded('subcategories')) {
            $payload['subcategories'] = $category->subcategories
                ->map(fn (Category $sub) => self::category($sub))
                ->values()
                ->all();
        }

        return $payload;
    }

    public static function designerSummary(Vendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'vendor_code' => $vendor->vendor_code,
            'brand_name' => $vendor->brand_name,
            'shop_name' => $vendor->shop_name,
            'city' => $vendor->city,
            'rating' => (float) $vendor->rating,
            'profile_image_url' => $vendor->profileImageUrl(),
            'shop_logo_url' => $vendor->shopLogoUrl(),
        ];
    }

    public static function customerProfile(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'customer_code' => $customer->customer_code,
            'name' => $customer->name,
            'mobile' => $customer->mobile,
            'mobile_no' => $customer->mobile,
            'email' => $customer->email,
            'address' => $customer->address,
            'city' => $customer->city,
            'state' => $customer->state,
            'country' => $customer->country,
            'pincode' => $customer->pincode,
            'profile_image_url' => $customer->profileImageUrl(),
            'status' => $customer->status,
            'is_verified' => (bool) $customer->is_verified,
            'is_guest' => (bool) $customer->is_guest,
        ];
    }

    public static function cartItem(CartItem $cartItem): array
    {
        $cartItem->loadMissing(['portfolioItem.vendor', 'portfolioItem.category', 'portfolioItem.subcategory.parent', 'portfolioItem.variants', 'variant', 'vendor']);
        $item = $cartItem->portfolioItem;

        return [
            'id' => $cartItem->id,
            'quantity' => $cartItem->quantity,
            'vendor_id' => $cartItem->vendor_id,
            'portfolio_item_id' => $cartItem->portfolio_item_id,
            'portfolio_item_variant_id' => $cartItem->portfolio_item_variant_id,
            'variant' => $cartItem->variant ? self::catalogVariant($cartItem->variant) : null,
            'product' => $item ? self::catalogItem($item) : null,
            'vendor' => $cartItem->vendor ? self::designerSummary($cartItem->vendor) : null,
            'line_total' => round($cartItem->unitDailyRate() * $cartItem->quantity, 2),
            'added_at' => $cartItem->created_at?->toIso8601String(),
        ];
    }

    public static function designerDetail(Vendor $vendor, ?Collection $portfolio = null): array
    {
        $vendor->loadMissing(['shopImages', 'portfolioImages']);

        $products = ($portfolio ?? collect())->map(fn (PortfolioItem $item) => self::catalogItem($item))->values()->all();
        $profilePortfolio = $vendor->portfolioImages
            ->sortBy(['sort_order', 'id'])
            ->map(fn (VendorPortfolioImage $image) => self::profilePortfolioImage($image))
            ->values()
            ->all();
        $reviews = $vendor->reviews()
            ->with('customer')
            ->latest('id')
            ->limit(20)
            ->get();

        return [
            ...self::designerSummary($vendor),
            'owner_name' => $vendor->owner_name,
            'bio' => $vendor->bio,
            'cover_image_url' => $vendor->coverImageUrl(),
            'coverImage' => $vendor->coverImageUrl(),
            'is_available' => (bool) $vendor->is_listing_active,
            'contact' => [
                'mobile' => $vendor->mobile,
                'email' => $vendor->email,
                'business_mobile' => $vendor->business_mobile,
                'business_email' => $vendor->business_email,
            ],
            'service_types' => $vendor->selectedServiceTypes(),
            'service_type' => $vendor->serviceType(),
            'address' => self::vendorAddress($vendor),
            'shop_image_urls' => $vendor->shopImageUrls(),
            'review_summary' => self::reviewSummaryForVendor($vendor),
            'review_count' => (int) $vendor->reviews()->count(),
            'reviews' => $reviews->map(fn (OrderReview $review) => self::orderReview($review))->values()->all(),
            'products' => $products,
            'profile_portfolio' => $profilePortfolio,
            'portfolio' => $profilePortfolio,
        ];
    }

    public static function profilePortfolioImage(VendorPortfolioImage $image): array
    {
        $imageUrl = $image->imageUrl();

        return [
            'id' => $image->id,
            'audience' => $image->audience,
            'image_url' => $imageUrl ? url($imageUrl) : null,
            'sort_order' => $image->sort_order,
        ];
    }

    /** @return array<string, mixed> */
    public static function vendorAddress(Vendor $vendor): array
    {
        return [
            'line' => $vendor->address,
            'city' => $vendor->city,
            'state' => $vendor->state,
            'country' => $vendor->country,
            'pincode' => $vendor->pincode,
            'full_address' => trim(implode(', ', array_filter([
                $vendor->address,
                $vendor->city,
                $vendor->state,
                $vendor->pincode,
                $vendor->country,
            ]))),
        ];
    }

    /** @return array<string, mixed> */
    public static function reviewSummaryForVendor(Vendor $vendor): array
    {
        $totalReviews = (int) $vendor->reviews()->count();
        $averageRating = $totalReviews > 0
            ? round((float) $vendor->reviews()->avg('rating'), 1)
            : (float) $vendor->rating;

        return [
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews,
        ];
    }

    public static function orderReview(OrderReview $review): array
    {
        $review->loadMissing(['customer', 'order']);

        return [
            'id' => $review->id,
            'order_id' => $review->order_id,
            'booking_id' => $review->order?->order_number,
            'customer_name' => $review->customer?->name,
            'profile_image_url' => $review->customer?->profileImageUrl(),
            'customer_profile_image_url' => $review->customer?->profileImageUrl(),
            'rating' => (float) $review->rating,
            'comment' => $review->comment,
            'reviewed_at' => $review->created_at?->format('M d, Y'),
            'reviewed_at_iso' => $review->created_at?->toIso8601String(),
        ];
    }

    public static function notification(NotificationLog $log, ?string $recipientType = null, ?int $recipientId = null): array
    {
        $readState = [
            'is_read' => false,
            'read_at' => null,
            'read_at_iso' => null,
        ];

        if ($recipientType && $recipientId) {
            $read = $log->relationLoaded('reads')
                ? $log->reads->first()
                : $log->reads()
                    ->where('recipient_type', $recipientType)
                    ->where('recipient_id', $recipientId)
                    ->first();

            $readState = [
                'is_read' => $read !== null && $read->read_at !== null,
                'read_at' => $read?->read_at?->format('M d, Y, g:i A'),
                'read_at_iso' => $read?->read_at?->toIso8601String(),
            ];
        }

        return [
            'id' => $log->id,
            'title' => $log->title,
            'message' => $log->message,
            'channel' => $log->channel,
            'sent_at' => $log->sent_at?->format('M d, Y, g:i A'),
            'sent_at_iso' => $log->sent_at?->toIso8601String(),
            ...$readState,
        ];
    }

    public static function catalogItem(PortfolioItem $item): array
    {
        $item->loadMissing(['vendor', 'category', 'subcategory.parent', 'subcategory.serviceCategory', 'variants', 'images']);

        $mainCategory = $item->subcategory?->parent;
        $variants = $item->variants
            ->map(fn (PortfolioItemVariant $variant) => self::catalogVariant($variant))
            ->values();

        $absoluteUrl = static function (?string $path): ?string {
            if (! $path) {
                return null;
            }

            return str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
                ? $path
                : url($path);
        };

        $galleryMediaUrls = collect($item->galleryMediaUrls())
            ->map(fn ($path) => $absoluteUrl($path))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $videoUrls = collect($item->galleryVideoUrls())
            ->map(fn ($path) => $absoluteUrl($path))
            ->filter()
            ->values()
            ->all();

        return [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'image_url' => $item->displayImageUrl() ? url($item->displayImageUrl()) : null,
            'gallery_image_urls' => $galleryMediaUrls,
            'video_urls' => $videoUrls,
            'gallery_videos' => $item->images
                ->filter(fn ($media) => $media->isVideo())
                ->map(fn ($media) => [
                    'id' => $media->id,
                    'url' => $absoluteUrl($media->mediaUrl()),
                    'media_type' => 'video',
                ])
                ->values()
                ->all(),
            'price' => $item->rentalPriceAmount(),
            'price_label' => $item->rentalPriceLabel(),
            'rating' => (float) ($item->vendor?->rating ?? 0),
            'audience' => $item->audience,
            'service' => $item->category ? self::category($item->category) : null,
            'category' => $mainCategory ? self::category($mainCategory) : null,
            'subcategory' => $item->subcategory ? self::category($item->subcategory) : null,
            'variants' => $variants->all(),
            'sizes' => $variants->pluck('size')->filter()->unique()->values()->all(),
            'colors' => $variants->pluck('color')->filter()->unique()->values()->all(),
            'designer' => $item->vendor ? self::designerSummary($item->vendor) : null,
            'is_available' => $item->isCatalogAvailable(),
        ];
    }

    public static function catalogVariant(PortfolioItemVariant $variant): array
    {
        $imageUrl = $variant->imageUrl();

        return [
            'id' => $variant->id,
            'size' => $variant->size,
            'color' => $variant->color,
            'price' => (float) $variant->price,
            'image_url' => $imageUrl ? url($imageUrl) : null,
        ];
    }

    public static function catalogDetail(PortfolioItem $item, ?Collection $related = null): array
    {
        $item->loadMissing(['vendor.reviews']);

        $reviewSummary = $item->vendor
            ? self::reviewSummaryForVendor($item->vendor)
            : ['average_rating' => 0.0, 'total_reviews' => 0];

        $reviews = $item->vendor
            ? $item->vendor->reviews()->with('customer')->latest('id')->limit(10)->get()
                ->map(fn (OrderReview $review) => self::orderReview($review))->values()->all()
            : self::placeholderReviews();

        return [
            ...self::catalogItem($item),
            'review_summary' => $reviewSummary,
            'reviews' => $reviews,
            'related_items' => ($related ?? collect())->map(fn (PortfolioItem $relatedItem) => self::catalogItem($relatedItem))->values()->all(),
        ];
    }

    public static function addressFromOrder(Order $order, string $label = 'Home'): array
    {
        return [
            'id' => 'order-'.$order->id,
            'label' => $label,
            'name' => $order->customer?->name,
            'pincode' => $order->pincode,
            'city' => $order->city,
            'line' => $order->delivery_address,
            'full_address' => trim(implode(', ', array_filter([
                $order->delivery_address,
                $order->city,
                $order->pincode,
            ]))),
        ];
    }

    public static function customerAddress(Customer $customer): ?array
    {
        $address = $customer->relationLoaded('addresses')
            ? ($customer->addresses->firstWhere('is_default', true) ?? $customer->addresses->first())
            : $customer->defaultAddress();

        if ($address) {
            return self::savedAddress($address);
        }

        if (! $customer->city) {
            return null;
        }

        return [
            'id' => 'customer-default',
            'label' => 'Home',
            'name' => $customer->name,
            'mobile_number' => $customer->mobile,
            'pincode' => null,
            'city' => $customer->city,
            'state' => null,
            'line' => $customer->city,
            'full_address' => $customer->city,
            'is_default' => true,
        ];
    }

    public static function savedAddress(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'name' => $address->name,
            'mobile_number' => $address->mobile_number,
            'country' => $address->country,
            'house_no' => $address->house_no,
            'road_area' => $address->road_area,
            'pincode' => $address->pincode,
            'city' => $address->city,
            'state' => $address->state,
            'line' => $address->address_line,
            'full_address' => $address->fullAddress(),
            'is_default' => (bool) $address->is_default,
        ];
    }

    public static function measurementSummary(CustomerMeasurement $profile): array
    {
        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'measurement_type' => $profile->measurement_type,
            'updated_at' => $profile->updated_at?->format('M d, Y'),
            'height_cm' => $profile->height_cm,
            'chest_cm' => $profile->chest_cm,
            'waist_cm' => $profile->waist_cm,
        ];
    }

    public static function measurementDetail(CustomerMeasurement $profile): array
    {
        return [
            ...self::measurementSummary($profile),
            ...$profile->apiMeasurementFields(),
            'extra_measurements' => $profile->extra_measurements ?? [],
        ];
    }

    public static function chatSummary(Conversation $conversation): array
    {
        $conversation->loadMissing(['vendor', 'latestMessage']);
        $vendor = $conversation->vendor;
        $latest = $conversation->latestMessage;
        $unread = $conversation->unreadCountForCustomer();

        return [
            'id' => $conversation->id,
            'vendor_id' => $conversation->vendor_id,
            'designer_name' => $vendor?->brand_name ?? $vendor?->shop_name,
            'designer_image_url' => $vendor?->profileImageUrl() ?? $vendor?->shopLogoUrl(),
            'is_online' => $vendor?->status === 'active',
            'last_message' => $latest?->body,
            'last_message_at' => $latest?->created_at?->toIso8601String(),
            'time_label' => $latest?->created_at?->diffForHumans(),
            'has_unread' => $unread > 0,
            'unread_count' => $unread,
        ];
    }

    public static function chatMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'body' => $message->body,
            'attachment_url' => $message->attachmentUrl(),
            'attachment_type' => $message->attachmentType(),
            'is_mine' => $message->isFromCustomer(),
            'is_read' => $message->read_at !== null,
            'sent_at' => $message->created_at?->format('g:i A'),
            'sent_at_iso' => $message->created_at?->toIso8601String(),
            'date_label' => $message->created_at?->isToday() ? 'TODAY' : $message->created_at?->format('M d, Y'),
        ];
    }

    public static function supportTicket(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'email' => $ticket->email,
            'description' => $ticket->description,
            'status' => $ticket->status,
            'status_label' => $ticket->statusLabel(),
            'admin_reply' => $ticket->admin_reply,
            'created_at' => $ticket->created_at?->format('d M Y'),
            'created_at_iso' => $ticket->created_at?->toIso8601String(),
        ];
    }

    public static function bookingSummary(Order $order): array
    {
        $order->loadMissing(['vendor', 'category', 'customer']);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'booking_id' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'payment_status' => $order->payment_status,
            'order_type' => $order->order_type,
            'item_title' => $order->itemDisplayName(),
            'item_image_url' => $order->itemImageUrl(),
            'size' => $order->size,
            'amount' => (float) $order->amount,
            'total_amount' => $order->grandTotal(),
            'booked_at' => $order->created_at?->format('d M Y, g:i A'),
            'designer' => $order->vendor ? self::designerSummary($order->vendor) : null,
            'address' => $order->delivery_address,
            'city' => $order->city,
            'can_cancel' => in_array($order->status, ['new', 'pending_acceptance'], true),
        ];
    }

    public static function bookingDetail(Order $order): array
    {
        $order->loadMissing(['category', 'dispute', 'review', 'orderItems', 'refund.histories']);

        return [
            ...self::bookingSummary($order),
            'is_sub_order' => $order->checkout_order_id !== null,
            'checkout_order_id' => $order->checkout_order_id,
            'sub_order_number' => $order->sub_order_number ?? $order->order_number,
            'billing_address' => $order->billing_address,
            'delivery_address' => $order->delivery_address,
            'pickup_address' => $order->pickup_address,
            'cancellation_reason' => $order->cancellation_reason,
            'customer_notes' => $order->customer_notes,
            'reference_image_urls' => $order->referenceImageUrls(),
            'rental_start_date' => $order->rental_start_date?->format('Y-m-d'),
            'rental_end_date' => $order->rental_end_date?->format('Y-m-d'),
            'measurements' => $order->checkout_order_id
                ? null
                : BookingMeasurementSupport::orderMeasurements($order),
            'line_items' => $order->orderItems->isNotEmpty()
                ? $order->orderItems->map(fn (OrderItem $item) => self::orderLineItem($item))->all()
                : null,
            'payment_summary' => BookingPricingService::fromOrder($order),
            'tracking_steps' => $order->trackBookingSteps(),
            'delivery_otp' => $order->ensureDeliveryOtp(),
            'category' => $order->category ? self::category($order->category) : null,
            'dispute' => $order->dispute ? [
                'id' => $order->dispute->id,
                'subject' => $order->dispute->subject,
                'status' => $order->dispute->status,
                'chat_open' => $order->dispute->isChatOpen(),
            ] : null,
            'can_raise_dispute' => ! $order->dispute,
            'dispute_subject_options' => \App\Models\Dispute::subjectOptionsForCategory($order->category),
            'can_review' => $order->status === 'delivered' && ! $order->review,
            'review' => $order->review ? self::orderReview($order->review) : null,
            'refund' => $order->refund ? self::refund($order->refund) : null,
        ];
    }

    public static function checkoutOrderSummary(CheckoutOrder $checkout): array
    {
        $checkout->loadMissing(['subOrders.vendor']);

        return [
            'id' => $checkout->id,
            'type' => 'checkout_order',
            'order_number' => $checkout->order_number,
            'booking_id' => $checkout->order_number,
            'status' => $checkout->status,
            'payment_status' => $checkout->payment_status,
            'amount' => (float) $checkout->amount,
            'delivery_fee' => (float) $checkout->delivery_fee,
            'tax_amount' => (float) $checkout->tax_amount,
            'grand_total' => (float) $checkout->grand_total,
            'amount_refunded' => (float) $checkout->amount_refunded,
            'vendor_count' => $checkout->subOrders->count(),
            'sub_orders' => $checkout->subOrders->map(fn (Order $sub) => self::subOrderSummary($sub))->all(),
            'booked_at' => $checkout->created_at?->format('d M Y, g:i A'),
            'address' => $checkout->delivery_address,
            'city' => $checkout->city,
            'can_cancel' => $checkout->payment_status === 'success'
                && $checkout->subOrders->every(fn (Order $sub) => in_array($sub->status, ['new', 'pending_acceptance'], true)),
        ];
    }

    public static function checkoutOrderDetail(CheckoutOrder $checkout): array
    {
        $checkout->loadMissing(['subOrders.vendor', 'subOrders.category', 'subOrders.driver', 'subOrders.orderItems', 'subOrders.review', 'subOrders.refund.histories', 'refunds.histories']);

        return [
            ...self::checkoutOrderSummary($checkout),
            'billing_address' => $checkout->billing_address,
            'delivery_address' => $checkout->delivery_address,
            'rental_start_date' => $checkout->rental_start_date?->format('Y-m-d'),
            'rental_end_date' => $checkout->rental_end_date?->format('Y-m-d'),
            'customer_notes' => $checkout->customer_notes,
            'measurements' => BookingMeasurementSupport::checkoutMeasurements($checkout),
            'payment_method' => $checkout->payment_method,
            'paid_at' => $checkout->paid_at?->toIso8601String(),
            'sub_orders' => $checkout->subOrders->map(fn (Order $sub) => self::subOrderDetail($sub))->all(),
            'refunds' => $checkout->refunds->map(fn (Refund $refund) => self::refund($refund))->all(),
        ];
    }

    public static function subOrderSummary(Order $subOrder): array
    {
        $subOrder->loadMissing(['vendor', 'category']);

        return [
            'id' => $subOrder->id,
            'sub_order_number' => $subOrder->sub_order_number ?? $subOrder->order_number,
            'status' => $subOrder->status,
            'status_label' => $subOrder->statusLabel(),
            'payment_status' => $subOrder->payment_status,
            'item_title' => $subOrder->itemDisplayName(),
            'item_image_url' => $subOrder->itemImageUrl(),
            'amount' => (float) $subOrder->amount,
            'delivery_fee' => (float) $subOrder->delivery_fee,
            'total_amount' => $subOrder->grandTotal(),
            'designer' => $subOrder->vendor ? self::designerSummary($subOrder->vendor) : null,
            'can_cancel' => in_array($subOrder->status, ['new', 'pending_acceptance'], true),
        ];
    }

    public static function subOrderDetail(Order $subOrder): array
    {
        return self::bookingDetail($subOrder);
    }

    /** @return array<string, mixed> */
    public static function orderLineItem(OrderItem $item): array
    {
        return [
            'id' => $item->id,
            'portfolio_item_id' => $item->portfolio_item_id,
            'title' => $item->title(),
            'image_url' => $item->displayImageUrl(),
            'category' => $item->categoryName(),
            'size' => $item->size(),
            'color' => $item->color(),
            'variant_id' => $item->variantId(),
            'variant_label' => $item->variantLabel(),
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'line_amount' => (float) $item->line_amount,
            'status' => $item->status,
            'status_label' => $item->statusLabel(),
            'cancellation_reason' => $item->cancellation_reason,
        ];
    }

    public static function refund(Refund $refund): array
    {
        $refund->loadMissing('histories');

        return [
            'id' => $refund->id,
            'order_id' => $refund->order_id,
            'checkout_order_id' => $refund->checkout_order_id,
            'amount' => (float) $refund->amount,
            'reason' => $refund->reason,
            'status' => $refund->status,
            'source' => $refund->source,
            'auto_processed' => (bool) $refund->auto_processed,
            'processed_at' => $refund->processed_at?->toIso8601String(),
            'histories' => $refund->histories->map(fn ($history) => [
                'id' => $history->id,
                'status' => $history->status,
                'note' => $history->note,
                'meta' => $history->meta,
                'created_at' => $history->created_at?->toIso8601String(),
            ])->all(),
        ];
    }

    public static function bookingPreview(PortfolioItem $item, ?Customer $customer = null, array $options = []): array
    {
        $item->loadMissing(['vendor', 'category']);
        $pricing = BookingPricingService::forPortfolioItem($item, $options);

        $defaultAddress = null;
        if ($customer) {
            $defaultAddress = self::customerAddress($customer);

            if (! $defaultAddress) {
                $latestOrder = $customer->orders()->latest('id')->first();
                $defaultAddress = $latestOrder ? self::addressFromOrder($latestOrder) : null;
            }
        }

        return [
            'item' => self::catalogItem($item),
            'designer' => $item->vendor ? self::designerSummary($item->vendor) : null,
            'default_address' => $defaultAddress,
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
            'measurement_types' => ['women', 'men', 'kid'],
            'max_reference_images' => 5,
            'payment_summary' => $pricing,
            'shipment_required' => (bool) ($options['shipment_required'] ?? true),
            'cart' => $options['cart'] ?? null,
            'cart_item_status' => $options['cart_item_status'] ?? null,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function placeholderReviews(): array
    {
        return [
            [
                'id' => 1,
                'user_name' => 'Veronika',
                'rating' => 4.5,
                'comment' => 'Beautiful craftsmanship and perfect fitting. Highly recommended!',
            ],
        ];
    }
}

<?php

namespace App\Support\Api;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\CustomerMeasurement;
use App\Models\Order;
use App\Models\OrderReview;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Models\VendorPortfolioImage;
use App\Models\VendorWalletTransaction;
use App\Services\Booking\BookingPricingService;
use App\Support\BookingMeasurementSupport;
use App\Support\ChatDateTime;
use App\Support\OrderDispatchSupport;
use App\Support\ProductOptionCatalog;
use App\Support\Api\VendorBookingStatus;
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
            'cover_image_url' => $vendor->coverImageUrl(),
            'coverImage' => $vendor->coverImageUrl(),
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
            'in_progress', 're_intransit' => match ($order->driver_delivery_status) {
                Order::DRIVER_STATUS_OUT_FOR_DELIVERY => 'OUT FOR DELIVERY',
                Order::DRIVER_STATUS_PICKED_UP => 'PICKED UP',
                default => $order->status === 're_intransit' ? 'RE-IN TRANSIT' : 'IN PROGRESS',
            },
            'accepted' => 'PENDING PICKUP',
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
        $order->loadMissing(['customer', 'category', 'orderItems', 'checkoutOrder']);
        $rentedPeriod = self::bookingRentedPeriod($order);
        $items = $order->orderItems;
        $itemsCount = $items->isNotEmpty() ? $items->count() : 1;
        $pendingItems = $items->isNotEmpty()
            ? $items->where('status', \App\Models\OrderItem::STATUS_PENDING)->count()
            : (in_array($order->status, ['new', 'pending_acceptance'], true) ? 1 : 0);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'booking_id' => $order->order_number,
            'checkout_order_id' => $order->checkout_order_id,
            'checkout_order_number' => $order->checkoutOrder?->order_number,
            'product_name' => $order->itemDisplayName(),
            'item_title' => $order->itemDisplayName(),
            'product_image_url' => $order->itemImageUrl(),
            'item_image_url' => $order->itemImageUrl(),
            'customer_name' => $order->customer?->name,
            'amount' => (float) $order->amount,
            'amount_label' => '₹'.number_format((float) $order->amount, 0),
            'total_amount' => $order->grandTotal(),
            'total_amount_label' => '₹'.number_format($order->grandTotal(), 0),
            'category' => $order->category?->name,
            'order_type' => $order->order_type === 'rental' ? 'Rental' : 'Sale',
            'order_type_raw' => $order->order_type,
            'status' => VendorBookingStatus::toApi($order->status),
            'status_raw' => $order->status,
            'status_label' => $order->statusLabel(),
            'payment_status' => $order->payment_status,
            ...self::bookingScheduleFields($order),
            'size' => $order->size,
            'color' => $order->color,
            'quantity' => (int) ($order->quantity ?? 1),
            'items_count' => $itemsCount,
            'pending_items_count' => $pendingItems,
            'rental_start_date' => $rentedPeriod['start_date'] ?? null,
            'rental_end_date' => $rentedPeriod['end_date'] ?? null,
            'rental_start_date_label' => $rentedPeriod['start_date_label'] ?? null,
            'rental_end_date_label' => $rentedPeriod['end_date_label'] ?? null,
            'rented_period' => $rentedPeriod,
            'rented_period_label' => $rentedPeriod['label'] ?? null,
            'rental_period' => $rentedPeriod['label'] ?? null,
            'cancellation_reason' => $order->cancellation_reason,
            'reject_reason' => $order->cancellation_reason,
            'can_accept' => in_array($order->status, ['new', 'pending_acceptance'], true) || $pendingItems > 0,
            'can_reject' => in_array($order->status, ['new', 'pending_acceptance'], true) || $pendingItems > 0,
            'line_items' => $items->isNotEmpty()
                ? $items->map(fn ($item) => self::orderLineItem($item))->values()->all()
                : [self::syntheticLineItemSummary($order)],
        ];
    }

    /**
     * List-friendly synthetic line item for legacy/website orders without order_items rows.
     *
     * @return array<string, mixed>
     */
    protected static function syntheticLineItemSummary(Order $order): array
    {
        $itemStatus = in_array($order->status, ['new', 'pending_acceptance'], true)
            ? \App\Models\OrderItem::STATUS_PENDING
            : (in_array($order->status, ['cancelled', 'refunded'], true)
                ? \App\Models\OrderItem::STATUS_CANCELLED
                : \App\Models\OrderItem::STATUS_ACCEPTED);

        $canRespond = in_array($order->status, ['new', 'pending_acceptance'], true);
        $rentedPeriod = self::bookingRentedPeriod($order);
        $notes = $order->customer_notes;
        $referenceUrls = $order->referenceImageUrls();

        return [
            'id' => null,
            'portfolio_item_id' => $order->portfolio_item_id,
            'title' => $order->itemDisplayName(),
            'image_url' => $order->itemImageUrl(),
            'category' => $order->category?->name,
            'size' => $order->size,
            'color' => $order->color,
            'variant_id' => null,
            'variant_label' => collect([$order->size, $order->color])->filter()->implode(' · ') ?: null,
            'quantity' => (int) ($order->quantity ?? 1),
            'unit_price' => $order->rentalDurationDays()
                ? round((float) $order->amount / max(1, $order->rentalDurationDays()), 2)
                : (float) $order->amount,
            'unit_price_label' => '₹'.number_format(
                $order->rentalDurationDays()
                    ? round((float) $order->amount / max(1, $order->rentalDurationDays()), 2)
                    : (float) $order->amount,
                0
            ).($order->rentalDurationDays() ? '/day' : ''),
            'line_amount' => (float) $order->amount,
            'line_amount_label' => '₹'.number_format((float) $order->amount, 0),
            'status' => $itemStatus,
            'status_label' => match ($itemStatus) {
                \App\Models\OrderItem::STATUS_PENDING => 'Pending acceptance',
                \App\Models\OrderItem::STATUS_CANCELLED => 'Cancelled',
                default => 'Accepted',
            },
            'cancellation_reason' => $order->cancellation_reason,
            'can_accept' => $canRespond,
            'can_reject' => $canRespond,
            'responded_at' => null,
            'rental_start_date' => $rentedPeriod['start_date'] ?? null,
            'rental_end_date' => $rentedPeriod['end_date'] ?? null,
            'rental_start_date_label' => isset($rentedPeriod['start_date'])
                ? \Carbon\Carbon::parse($rentedPeriod['start_date'])->format('jS M, Y')
                : null,
            'rental_end_date_label' => isset($rentedPeriod['end_date'])
                ? \Carbon\Carbon::parse($rentedPeriod['end_date'])->format('jS M, Y')
                : null,
            'rental_duration_days' => $rentedPeriod['duration_days'] ?? $order->rentalDurationDays(),
            'rental_period_label' => $rentedPeriod['label'] ?? null,
            'customer_notes' => $notes,
            'custom_notes' => $notes,
            'service_type' => null,
            'reference_image_urls' => $referenceUrls,
            'measurement_profile_id' => null,
            'measurements' => null,
        ];
    }

    public static function bookingDetail(Order $order): array
    {
        $order->loadMissing([
            'customer.measurements',
            'category',
            'driver',
            'vendor',
            'review.customer',
            'orderItems',
            'checkoutOrder',
            'refunds',
        ]);

        $orderItems = $order->orderItems;
        $lineItems = $orderItems->isNotEmpty()
            ? $orderItems->map(fn ($item) => self::orderLineItemDetail($item, $order))->values()->all()
            : [self::syntheticLineItemDetail($order)];
        $isMultiItem = $orderItems->count() > 1;
        $itemsPendingAcceptance = $orderItems->where('status', \App\Models\OrderItem::STATUS_PENDING)->count();
        $itemsAccepted = $orderItems->where('status', \App\Models\OrderItem::STATUS_ACCEPTED)->count();
        $itemsCancelled = $orderItems->where('status', \App\Models\OrderItem::STATUS_CANCELLED)->count();

        return [
            ...self::bookingSummary($order),
            'is_multi_item' => $isMultiItem,
            'line_items_count' => max(1, $orderItems->count()),
            'items_status_breakdown' => [
                'pending' => $itemsPendingAcceptance,
                'accepted' => $itemsAccepted,
                'cancelled' => $itemsCancelled,
            ],
            'product' => self::bookingProduct($order),
            'category_detail' => $order->category ? CustomerApiPresenter::category($order->category) : null,
            'customer' => self::bookingCustomer($order),
            'billing_address' => $order->billing_address,
            'delivery_address' => $order->delivery_address,
            'shipping_address' => self::bookingShippingAddress($order),
            'pickup_address' => $order->pickup_address,
            'city' => $order->city,
            'pincode' => $order->pincode,
            'customer_notes' => $order->customer_notes,
            'custom_notes' => $order->customer_notes,
            'item_description' => $order->item_description,
            'reference_image_urls' => $order->referenceImageUrls(),
            'reference_images' => self::referenceImagesPayload($order->referenceImageUrls()),
            'event_date' => $order->event_date?->format('Y-m-d'),
            'event_date_label' => $order->event_date?->format('jS M Y'),
            'security_deposit' => $order->security_deposit !== null ? (float) $order->security_deposit : null,
            'advance_amount' => $order->security_deposit !== null ? (float) $order->security_deposit : null,
            'measurements' => self::orderMeasurements($order),
            'payment_summary' => self::bookingPaymentSummaryPayload($order),
            'damage' => self::bookingDamage($order),
            'tracking_steps' => $order->trackBookingSteps(),
            'rental_tracking' => $order->isRental() ? $order->rentalTrackingSummary() : null,
            'delivery_otp' => self::bookingDeliveryOtp($order),
            'driver' => $order->driver ? [
                'id' => $order->driver->id,
                'name' => $order->driver->name,
                'mobile' => $order->driver->mobile,
                'delivery_status' => $order->driver_delivery_status,
                'assigned_at' => $order->driver_assigned_at?->toIso8601String(),
                'assigned_at_label' => $order->driver_assigned_at?->format('d M Y, g:i A'),
                'pickup_at' => $order->driver_pickup_at?->toIso8601String(),
                'pickup_at_label' => $order->driver_pickup_at?->format('d M Y, g:i A'),
                'delivered_at' => $order->driver_delivered_at?->toIso8601String(),
                'delivered_at_label' => $order->driver_delivered_at?->format('d M Y, g:i A'),
            ] : null,
            'customer_review' => self::bookingCustomerReview($order),
            'review' => self::bookingCustomerReview($order),
            'order_items' => $lineItems,
            'line_items' => $lineItems,
            'items' => $lineItems,
            'checkout' => $order->checkoutOrder ? [
                'id' => $order->checkoutOrder->id,
                'order_number' => $order->checkoutOrder->order_number,
                'status' => $order->checkoutOrder->status,
                'payment_status' => $order->checkoutOrder->payment_status,
                'grand_total' => (float) $order->checkoutOrder->grand_total,
            ] : null,
            'allowed_next_statuses' => collect(OrderDispatchSupport::allowedNextStatuses($order))
                ->map(fn (string $status) => VendorBookingStatus::toApi($status))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    public static function orderLineItem(\App\Models\OrderItem $item): array
    {
        $rentalStart = $item->rentalStartDate();
        $rentalEnd = $item->rentalEndDate();
        $rentalDays = $item->rentalDurationDays();

        $startCarbon = $rentalStart ? \Carbon\Carbon::parse($rentalStart) : null;
        $endCarbon = $rentalEnd ? \Carbon\Carbon::parse($rentalEnd) : null;
        $rentalLabel = match (true) {
            $startCarbon && $endCarbon => $startCarbon->format('jS M').' – '.$endCarbon->format('jS M, Y'),
            $startCarbon !== null => 'From '.$startCarbon->format('jS M, Y'),
            $endCarbon !== null => 'Until '.$endCarbon->format('jS M, Y'),
            default => null,
        };

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
            'unit_price_label' => '₹'.number_format((float) $item->unit_price, 0).'/day',
            'line_amount' => (float) $item->line_amount,
            'line_amount_label' => '₹'.number_format((float) $item->line_amount, 0),
            'status' => $item->status,
            'status_label' => $item->statusLabel(),
            'cancellation_reason' => $item->cancellation_reason,
            'can_accept' => $item->canAccept(),
            'can_reject' => $item->canReject(),
            'responded_at' => $item->responded_at?->toIso8601String(),
            'rental_start_date' => $rentalStart,
            'rental_end_date' => $rentalEnd,
            'rental_start_date_label' => $startCarbon?->format('jS M, Y'),
            'rental_end_date_label' => $endCarbon?->format('jS M, Y'),
            'rental_duration_days' => $rentalDays,
            'rental_period_label' => $rentalLabel,
            'customer_notes' => $item->customerNotes(),
            'custom_notes' => $item->customerNotes(),
            'service_type' => $item->serviceType(),
            'reference_image_urls' => $item->referenceImageUrls(),
            'measurement_profile_id' => $item->measurementProfileId(),
            'measurements' => self::orderItemMeasurements($item),
        ];
    }

    /**
     * Full item-detail payload for vendor booking detail screen.
     *
     * @return array<string, mixed>
     */
    public static function orderLineItemDetail(\App\Models\OrderItem $item, ?Order $order = null): array
    {
        $order ??= $item->order ?? $item->order()->with('customer')->first();
        $order?->loadMissing('customer');

        $rentalStart = $item->rentalStartDate();
        $rentalEnd = $item->rentalEndDate();
        $rentalDays = $item->rentalDurationDays();

        $startCarbon = $rentalStart ? \Carbon\Carbon::parse($rentalStart) : null;
        $endCarbon = $rentalEnd ? \Carbon\Carbon::parse($rentalEnd) : null;
        $rentalLabel = match (true) {
            $startCarbon && $endCarbon => $startCarbon->format('jS M').' – '.$endCarbon->format('jS M, Y'),
            $startCarbon !== null => 'From '.$startCarbon->format('jS M, Y'),
            $endCarbon !== null => 'Until '.$endCarbon->format('jS M, Y'),
            default => null,
        };

        $notes = $item->customerNotes() ?: $order?->customer_notes;
        $referenceUrls = $item->referenceImageUrls();
        if ($referenceUrls === [] && $order) {
            $referenceUrls = $order->referenceImageUrls();
        }

        $shippingAddress = $order ? self::bookingShippingAddress($order) : null;
        $location = $shippingAddress['full_address']
            ?? collect([$order?->city, $order?->pincode])->filter()->implode(', ')
            ?: null;

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
            'unit_price_label' => '₹'.number_format((float) $item->unit_price, 0).'/day',
            'line_amount' => (float) $item->line_amount,
            'line_amount_label' => '₹'.number_format((float) $item->line_amount, 0),
            'status' => $item->status,
            'status_label' => $item->statusLabel(),
            'cancellation_reason' => $item->cancellation_reason,
            'can_accept' => $item->canAccept(),
            'can_reject' => $item->canReject(),
            'responded_at' => $item->responded_at?->toIso8601String(),
            'rental_start_date' => $rentalStart,
            'rental_end_date' => $rentalEnd,
            'rental_start_date_label' => $startCarbon?->format('jS M, Y'),
            'rental_end_date_label' => $endCarbon?->format('jS M, Y'),
            'rental_duration_days' => $rentalDays,
            'rental_period_label' => $rentalLabel,
            'customer_notes' => $notes,
            'custom_notes' => $notes,
            'service_type' => $item->serviceType(),
            'reference_image_urls' => $referenceUrls,
            'reference_images' => self::referenceImagesPayload($referenceUrls),
            'measurement_profile_id' => $item->measurementProfileId(),
            'measurements' => self::orderItemMeasurements($item),
            'customer' => $order ? [
                ...self::bookingCustomer($order),
                'location' => $location,
                'city' => $order->city,
                'pincode' => $order->pincode,
                'address' => $order->delivery_address,
                'chat_label' => $order->customer?->name
                    ? 'Chat with '.$order->customer->name
                    : 'Chat with customer',
            ] : null,
            'location' => $location,
            'shipping_address' => $shippingAddress,
            'payment_summary' => self::itemPaymentSummaryPayload($item, $order),
        ];
    }

    /**
     * Legacy bookings without order_items rows still need an item-detail card.
     *
     * @return array<string, mixed>
     */
    protected static function syntheticLineItemDetail(Order $order): array
    {
        $notes = $order->customer_notes;
        $referenceUrls = $order->referenceImageUrls();
        $shippingAddress = self::bookingShippingAddress($order);
        $location = $shippingAddress['full_address']
            ?? collect([$order->city, $order->pincode])->filter()->implode(', ')
            ?: null;

        return [
            'id' => null,
            'portfolio_item_id' => $order->portfolio_item_id,
            'title' => $order->itemDisplayName(),
            'image_url' => $order->itemImageUrl(),
            'category' => $order->category?->name,
            'size' => $order->size,
            'color' => $order->color,
            'variant_id' => null,
            'variant_label' => collect([$order->size, $order->color])->filter()->implode(' · ') ?: null,
            'quantity' => (int) ($order->quantity ?? 1),
            'unit_price' => (float) $order->amount,
            'unit_price_label' => '₹'.number_format((float) $order->amount, 0),
            'line_amount' => (float) $order->amount,
            'line_amount_label' => '₹'.number_format((float) $order->amount, 0),
            'status' => in_array($order->status, ['new', 'pending_acceptance'], true)
                ? \App\Models\OrderItem::STATUS_PENDING
                : (in_array($order->status, ['cancelled', 'refunded'], true)
                    ? \App\Models\OrderItem::STATUS_CANCELLED
                    : \App\Models\OrderItem::STATUS_ACCEPTED),
            'status_label' => $order->statusLabel(),
            'cancellation_reason' => $order->cancellation_reason,
            'can_accept' => in_array($order->status, ['new', 'pending_acceptance'], true),
            'can_reject' => in_array($order->status, ['new', 'pending_acceptance'], true),
            'responded_at' => null,
            'rental_start_date' => $order->rental_start_date?->format('Y-m-d'),
            'rental_end_date' => $order->rental_end_date?->format('Y-m-d'),
            'rental_start_date_label' => $order->rental_start_date?->format('jS M, Y'),
            'rental_end_date_label' => $order->rental_end_date?->format('jS M, Y'),
            'rental_duration_days' => $order->rentalDurationDays(),
            'rental_period_label' => self::bookingRentedPeriod($order)['label'] ?? null,
            'customer_notes' => $notes,
            'custom_notes' => $notes,
            'service_type' => null,
            'reference_image_urls' => $referenceUrls,
            'reference_images' => self::referenceImagesPayload($referenceUrls),
            'measurement_profile_id' => null,
            'measurements' => self::orderMeasurements($order),
            'customer' => [
                ...self::bookingCustomer($order),
                'location' => $location,
                'city' => $order->city,
                'pincode' => $order->pincode,
                'address' => $order->delivery_address,
                'chat_label' => $order->customer?->name
                    ? 'Chat with '.$order->customer->name
                    : 'Chat with customer',
            ],
            'location' => $location,
            'shipping_address' => $shippingAddress,
            'payment_summary' => self::bookingPaymentSummaryPayload($order),
        ];
    }

    /**
     * @param  list<string>  $urls
     * @return list<array{url: string, label: string}>
     */
    protected static function referenceImagesPayload(array $urls): array
    {
        return collect($urls)
            ->filter()
            ->values()
            ->map(fn (string $url, int $index) => [
                'url' => $url,
                'label' => 'Reference image '.($index + 1),
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    protected static function bookingPaymentSummaryPayload(Order $order): array
    {
        $summary = BookingPricingService::vendorPaymentSummary($order, $order->vendor);
        $advance = $order->security_deposit !== null ? (float) $order->security_deposit : 0.0;

        return [
            ...$summary,
            'advance_amount' => $advance,
            'shipping_and_handling' => $summary['shipping_fee'],
            'subtotal_label' => '₹'.number_format((float) $summary['subtotal'], 0),
            'advance_amount_label' => '₹'.number_format($advance, 0),
            'shipping_fee_label' => '₹'.number_format((float) $summary['shipping_fee'], 0),
            'shipping_and_handling_label' => '₹'.number_format((float) $summary['shipping_fee'], 0),
            'tax_amount_label' => '₹'.number_format((float) $summary['tax_amount'], 0),
            'tax_label' => 'Tax (GST '.(int) $summary['tax_percent'].'%)',
            'total_amount_label' => '₹'.number_format((float) $summary['total_amount'], 0),
        ];
    }

    /** @return array<string, mixed> */
    protected static function itemPaymentSummaryPayload(\App\Models\OrderItem $item, ?Order $order): array
    {
        $subtotal = round((float) $item->line_amount, 2);
        $gstPercent = BookingPricingService::gstPercent();

        if (! $order) {
            $taxAmount = round($subtotal * ($gstPercent / 100), 2);

            return [
                'subtotal' => $subtotal,
                'advance_amount' => 0.0,
                'shipping_fee' => 0.0,
                'shipping_and_handling' => 0.0,
                'tax_percent' => $gstPercent,
                'tax_amount' => $taxAmount,
                'total_amount' => round($subtotal + $taxAmount, 2),
                'currency' => (string) \App\Models\PlatformSetting::get('currency', 'INR'),
                'subtotal_label' => '₹'.number_format($subtotal, 0),
                'advance_amount_label' => '₹0',
                'shipping_fee_label' => '₹0',
                'shipping_and_handling_label' => '₹0',
                'tax_amount_label' => '₹'.number_format($taxAmount, 0),
                'tax_label' => 'Tax (GST '.(int) $gstPercent.'%)',
                'total_amount_label' => '₹'.number_format($subtotal + $taxAmount, 0),
            ];
        }

        $order->loadMissing('orderItems');
        $active = $order->orderItems->where('status', '!=', \App\Models\OrderItem::STATUS_CANCELLED);
        $activeSubtotal = max(0.01, (float) $active->sum(fn ($row) => (float) $row->line_amount));
        $share = $subtotal / $activeSubtotal;

        // Single-item booking: show full order fees. Multi-item: prorate shipping/advance/tax.
        $isSingle = $active->count() <= 1;
        $shipping = $isSingle
            ? (float) ($order->delivery_fee ?? 0)
            : round((float) ($order->delivery_fee ?? 0) * $share, 2);
        $advance = $isSingle
            ? (float) ($order->security_deposit ?? 0)
            : round((float) ($order->security_deposit ?? 0) * $share, 2);
        $taxAmount = $isSingle
            ? (float) ($order->tax_amount ?? round($subtotal * ($gstPercent / 100), 2))
            : round($subtotal * ($gstPercent / 100), 2);
        $totalAmount = round($subtotal + $shipping + $taxAmount, 2);

        return [
            'subtotal' => $subtotal,
            'advance_amount' => $advance,
            'shipping_fee' => $shipping,
            'shipping_and_handling' => $shipping,
            'tax_percent' => $gstPercent,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'currency' => (string) \App\Models\PlatformSetting::get('currency', 'INR'),
            'subtotal_label' => '₹'.number_format($subtotal, 0),
            'advance_amount_label' => '₹'.number_format($advance, 0),
            'shipping_fee_label' => '₹'.number_format($shipping, 0),
            'shipping_and_handling_label' => '₹'.number_format($shipping, 0),
            'tax_amount_label' => '₹'.number_format($taxAmount, 0),
            'tax_label' => 'Tax (GST '.(int) $gstPercent.'%)',
            'total_amount_label' => '₹'.number_format($totalAmount, 0),
        ];
    }

    /** @return array<string, mixed>|null */
    protected static function orderItemMeasurements(\App\Models\OrderItem $item): ?array
    {
        $profileId = $item->measurementProfileId();
        if (! $profileId) {
            return null;
        }

        $order = $item->order ?? $item->order()->first();
        $customer = $order?->customer;
        $profile = $customer?->measurements()->whereKey($profileId)->first();

        if (! $profile) {
            return null;
        }

        $detail = CustomerApiPresenter::measurementDetail($profile);
        unset($detail['id']);

        return $detail;
    }

    /** @return array<string, string|null> */
    protected static function bookingScheduleFields(Order $order): array
    {
        return [
            'booking_date' => $order->created_at?->format('M d, Y g:i A'),
            'booking_date_label' => $order->created_at?->format('d M Y, g:i A'),
            'booked_at' => $order->created_at?->toIso8601String(),
            'booked_at_label' => $order->created_at?->format('d M Y, g:i A'),
            'updated_at' => $order->updated_at?->toIso8601String(),
            'updated_at_label' => $order->updated_at?->format('d M Y, g:i A'),
            'paid_at' => $order->paid_at?->toIso8601String(),
            'paid_at_label' => $order->paid_at?->format('d M Y, g:i A'),
        ];
    }

    /** @return array<string, mixed> */
    protected static function bookingProduct(Order $order): array
    {
        return [
            'title' => $order->itemDisplayName(),
            'description' => $order->item_description,
            'image_url' => $order->itemImageUrl(),
            'size' => $order->size,
            'color' => $order->color,
            'quantity' => (int) ($order->quantity ?? 1),
            'price' => (float) $order->amount,
            'price_label' => '₹'.number_format((float) $order->amount, 0),
        ];
    }

    /** @return array<string, mixed> */
    protected static function bookingShippingAddress(Order $order): array
    {
        $parts = array_values(array_filter([
            $order->delivery_address,
            $order->city,
            $order->pincode,
        ], fn ($value) => filled($value)));

        return [
            'name' => $order->customer?->name,
            'line' => $order->delivery_address,
            'city' => $order->city,
            'pincode' => $order->pincode,
            'full_address' => $parts !== [] ? implode(', ', $parts) : null,
        ];
    }

    /** @return array<string, mixed> */
    protected static function bookingDamage(Order $order): array
    {
        return [
            'note' => $order->damage_note,
            'deduct_percent' => $order->damage_deduct_percent !== null
                ? (float) $order->damage_deduct_percent
                : null,
            'deduction_amount' => $order->damageDeduction(),
        ];
    }

    protected static function bookingDeliveryOtp(Order $order): ?string
    {
        if (! in_array($order->status, ['accepted', 'in_progress', 're_intransit', 'delivered', 're_delivered'], true)) {
            return null;
        }

        return $order->ensureDeliveryOtp();
    }

    /** @return array<string, mixed>|null */
    protected static function bookingRentedPeriod(Order $order): ?array
    {
        if (! $order->isRental()) {
            return null;
        }

        $start = $order->rental_start_date;
        $end = $order->rental_end_date;
        $returnDue = $order->return_due_date ?? $end;

        if (! $start && ! $end) {
            return null;
        }

        $startLabel = $start?->format('jS M');
        $endLabel = $end?->format('jS M');

        $label = match (true) {
            $start && $end => $startLabel.' to '.$endLabel,
            $start !== null => 'From '.$startLabel,
            default => 'Until '.$endLabel,
        };

        return [
            'start_date' => $start?->format('Y-m-d'),
            'end_date' => $end?->format('Y-m-d'),
            'start_date_label' => $startLabel,
            'end_date_label' => $endLabel,
            'label' => $label,
            'duration_days' => $order->rentalDurationDays(),
            'return_due_date' => $returnDue?->format('Y-m-d'),
            'return_due_date_label' => $returnDue?->format('jS M'),
            'started' => $order->hasRentalPeriodStarted(),
            'phase' => $order->rentalTrackingPhase(),
            'phase_label' => $order->rentalPhaseLabel(),
        ];
    }

    /** @return array<string, mixed> */
    public static function bookingCustomer(Order $order): array
    {
        $customer = $order->customer;

        return [
            'id' => $customer?->id,
            'name' => $customer?->name,
            'email' => $customer?->email,
            'mobile' => $customer?->mobile,
            'profile_image_url' => $customer?->profileImageUrl(),
        ];
    }

    /** @return array<string, mixed> */
    public static function orderMeasurements(Order $order): array
    {
        $measurements = BookingMeasurementSupport::orderMeasurements($order);
        $profile = $order->customer?->measurements()->latest('id')->first();

        if (! $profile) {
            return $measurements;
        }

        $profileFields = CustomerApiPresenter::measurementDetail($profile);

        foreach ($profileFields as $key => $value) {
            if (in_array($key, ['id', 'name', 'updated_at', 'extra_measurements'], true)) {
                continue;
            }

            if (($measurements[$key] ?? null) === null && $value !== null && $value !== '') {
                $measurements[$key] = $value;
            }
        }

        if ($measurements['measurement_type'] === null && ! empty($profile->measurement_type)) {
            $measurements['measurement_type'] = $profile->measurement_type;
        }

        return $measurements;
    }

    protected static function parseOrderMeasurementType(Order $order): ?string
    {
        return BookingMeasurementSupport::parseMeasurementTypeFromNotes($order);
    }

    /** @return array<string, mixed>|null */
    public static function bookingCustomerReview(Order $order): ?array
    {
        $order->loadMissing(['review.customer', 'customer']);

        if ($order->review) {
            return CustomerApiPresenter::orderReview($order->review);
        }

        return null;
    }

    public static function productSummary(PortfolioItem $item): array
    {
        $item->loadMissing(['category', 'subcategory.parent', 'subcategory.serviceCategory', 'vendor', 'images', 'variants', 'damageDeductions']);
        $isFashionDesigner = $item->category?->slug === 'fashion-designer';
        $mainCategory = $item->subcategory?->parent;

        $absoluteUrl = static function (?string $path): ?string {
            if (! $path) {
                return null;
            }

            return str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
                ? $path
                : url($path);
        };

        // Images and videos are returned separately for mobile clients.
        $galleryImageUrls = collect($item->galleryImageUrls())
            ->map(fn ($path) => $absoluteUrl($path))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $videoUrls = collect($item->galleryVideoUrls())
            ->map(fn ($path) => $absoluteUrl($path))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'image_url' => $absoluteUrl($item->displayImageUrl()),
            'gallery_image_urls' => $galleryImageUrls,
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
            'price_per_day' => (float) ($item->price_per_day ?? $item->rentalPriceAmount()),
            'advance_amount' => $item->advance_amount !== null ? (float) $item->advance_amount : null,
            'price_label' => $isFashionDesigner
                ? '₹'.number_format((float) ($item->price_per_day ?? $item->rentalPriceAmount()), 0)
                : $item->rentalPriceLabel(),
            'audience' => $item->audience,
            'variants' => $item->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'size' => $variant->size,
                'color' => $variant->color,
                'color_code' => ProductOptionCatalog::hexForName($variant->color),
                'price' => (float) $variant->price,
                'advance_amount' => $variant->advance_amount !== null ? (float) $variant->advance_amount : null,
                'quantity' => $variant->quantity !== null ? (int) $variant->quantity : null,
                'image_url' => $variant->imageUrl(),
            ])->values()->all(),
            'damage_deductions' => $item->damageDeductions->map(fn ($rule) => [
                'id' => $rule->id,
                'damage_type' => $rule->damage_type,
                'percent' => (float) $rule->percent,
            ])->values()->all(),
            ...self::productReviewPayload($item),
            'brand_name' => strtoupper($item->vendor?->brand_name ?? ''),
            'service' => $item->category ? CustomerApiPresenter::category($item->category) : null,
            'category' => $mainCategory ? CustomerApiPresenter::category($mainCategory) : null,
            'subcategory' => $item->subcategory ? CustomerApiPresenter::category($item->subcategory) : null,
            'category_type' => $item->category?->slug,
            'status' => $item->status,
            'approval_status' => $item->status,
            'is_listing_active' => (bool) ($item->is_listing_active ?? true),
            'is_available' => $item->isCatalogAvailable(),
            'availability_status' => $item->isCatalogAvailable() ? 'available' : 'unavailable',
            'rejection_reason' => $item->rejection_reason,
            'updated_at' => $item->updated_at?->format('M d, Y'),
        ];
    }

    public static function productIsAvailable(PortfolioItem $item): bool
    {
        return $item->isCatalogAvailable();
    }

    public static function productDetail(PortfolioItem $item): array
    {
        $item->loadMissing(['category', 'subcategory.parent', 'subcategory.serviceCategory', 'vendor', 'images', 'variants', 'damageDeductions']);

        return [
            ...self::productSummary($item),
        ];
    }

    /** @return array<string, mixed> */
    protected static function productReviewPayload(PortfolioItem $item): array
    {
        $item->loadMissing('vendor.reviews.customer');

        $vendor = $item->vendor;
        $reviewSummary = $vendor
            ? CustomerApiPresenter::reviewSummaryForVendor($vendor)
            : ['average_rating' => 0.0, 'total_reviews' => 0];

        $reviews = $vendor
            ? $vendor->reviews()->with('customer')->latest('id')->limit(10)->get()
                ->map(fn (OrderReview $review) => CustomerApiPresenter::orderReview($review))->values()->all()
            : [];

        return [
            'rating' => (float) ($reviewSummary['average_rating'] ?? 0),
            'reviews' => $reviews,
            'review_summary' => $reviewSummary,
        ];
    }

    public static function chatSummary(Conversation $conversation): array
    {
        $conversation->loadMissing(['customer', 'latestMessage']);
        $customer = $conversation->customer;
        $latest = $conversation->latestMessage;
        $unread = $conversation->unreadCountForVendor();
        $isOnline = $customer
            ? app(\App\Services\ChatPresenceService::class)->customerOnline((int) $customer->id)
            : false;

        return [
            'id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'customer_name' => $customer?->name,
            'customer_image_url' => $customer?->profileImageUrl(),
            'is_online' => $isOnline,
            'online_status' => $isOnline ? 'online' : 'offline',
            'last_message' => $latest?->body,
            'last_message_at' => $latest?->created_at?->toIso8601String(),
            'time_label' => ChatDateTime::relative($latest?->created_at),
            'has_unread' => $unread > 0,
            'unread_count' => $unread,
            'has_chat' => true,
            'can_start' => false,
        ];
    }

    /** @return array<string, mixed> */
    public static function chatStartableCustomer(Customer $customer): array
    {
        $isOnline = app(\App\Services\ChatPresenceService::class)->customerOnline((int) $customer->id);

        return [
            'id' => null,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_image_url' => $customer->profileImageUrl(),
            'customer_mobile' => $customer->mobile,
            'is_online' => $isOnline,
            'online_status' => $isOnline ? 'online' : 'offline',
            'last_message' => null,
            'last_message_at' => null,
            'time_label' => null,
            'has_unread' => false,
            'unread_count' => 0,
            'has_chat' => false,
            'can_start' => true,
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
            'attachment_type' => $message->attachmentType(),
            'attachment_name' => $message->attachmentDisplayName(),
            'is_mine' => $message->sender_type === ChatMessage::SENDER_VENDOR,
            'is_read' => $message->read_at !== null,
            'sent_at' => ChatDateTime::clock($message->created_at),
            'sent_at_iso' => $message->created_at?->toIso8601String(),
            'date_label' => ChatDateTime::dateLabel($message->created_at),
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

@extends('web.layouts.app')

@section('title', 'Booking '.$order->order_number)

@section('content')
@php
    $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=400&q=80';
    $paymentSummary = $paymentSummary ?? [];
    $checkoutOrder = $checkoutOrder ?? null;
    $focusedItem = $focusedItem ?? null;

    $bookingTypeLabel = function ($category = null, ?string $fallback = null): string {
        $slug = strtolower((string) ($category?->slug ?? ''));
        $name = strtolower((string) ($category?->name ?? ''));
        $haystack = $slug.' '.$name.' '.strtolower((string) $fallback);

        if (str_contains($haystack, 'jewellery') || str_contains($haystack, 'jewelry')) {
            return 'Rented Jewellery';
        }
        if (str_contains($haystack, 'dress') || str_contains($haystack, 'rental')) {
            return 'Rented Dress';
        }
        if (str_contains($haystack, 'fashion') || str_contains($haystack, 'designer')) {
            return 'Designing';
        }

        return $category?->name ?: ($fallback ?: 'Booking');
    };

    $displayTitle = $focusedItem?->title() ?: $order->itemDisplayName();
    $displayImage = $focusedItem?->displayImageUrl() ?: $order->itemImageUrl() ?: $fallbackImg;
    $displayPrice = $focusedItem?->line_amount ?? $order->amount;
    $displayColor = $focusedItem?->color() ?: $order->color;
    $displaySize = $focusedItem?->size() ?: $order->size;
    $typeLabel = $bookingTypeLabel(
        $focusedItem?->portfolioItem?->category ?? $order->category,
        $order->orderTypeLabel()
    );
    $statusClass = \App\Support\WebBookingStatus::badgeClass(
        $focusedItem ? $focusedItem->statusForDisplay($order) : $order->status
    );
    $statusLabel = $focusedItem ? $focusedItem->statusLabel() : $order->statusLabel();
    $backUrl = $checkoutOrder
        ? route('web.bookings.checkout.show', $checkoutOrder)
        : route('web.bookings.index');
    $backLabel = $checkoutOrder ? 'Back to Booking Detail' : 'Back to Profile';

    $order->loadMissing(['orderItems.review', 'reviews']);
    $canReviewItem = $focusedItem
        && in_array($focusedItem->status, \App\Models\OrderReview::reviewableStatuses(), true)
        && ! $focusedItem->review;
    $canReviewBooking = ! $focusedItem
        && $order->orderItems->isEmpty()
        && \App\Support\WebBookingStatus::bookingCanReview($order);
    $showRateReview = $canReviewItem || $canReviewBooking;

    $rentalStart = $order->rental_start_date;
    $rentalEnd = $order->rental_end_date;
    $referenceImages = $order->referenceImageUrls();
    $customerNotes = $order->customer_notes;
    $specs = collect([$displayColor, $displaySize])->filter()->implode(' | ');
@endphp

<div class="jbw-container jbw-figma-booking-page jbw-figma-booking-page--item">
    <nav class="jbw-figma-back-nav">
        <a href="{{ $backUrl }}" class="jbw-figma-back-link">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
            {{ $backLabel }}
        </a>
    </nav>

    <header class="jbw-figma-booking-head">
        <h1 class="jbw-figma-booking-title">Booking Detail</h1>
        <p class="jbw-figma-booking-meta">
            #{{ $focusedItem ? 'ITEM-'.$focusedItem->id : $order->order_number }}
            <span aria-hidden="true">·</span>
            {{ $order->created_at->format('M d, Y') }}
            <span aria-hidden="true">·</span>
            {{ $order->created_at->format('H:i') }}
        </p>
    </header>

    <div class="jbw-figma-stepper-card">
        @include('web.partials.booking-status-stepper', [
            'order' => $order,
            'orderItem' => $focusedItem,
        ])
    </div>

    <div class="jbw-figma-booking-layout">
        <div class="jbw-figma-booking-main">
            <article class="jbw-figma-product-card">
                <div class="jbw-figma-product-card-head">
                    <span class="jbw-figma-item-tag">{{ $typeLabel }}</span>
                    <span class="jbw-figma-item-status jbw-figma-item-status--{{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
                <div class="jbw-figma-product-card-body">
                    <img src="{{ $displayImage }}" alt="{{ $displayTitle }}" class="jbw-figma-product-img">
                    <div class="jbw-figma-product-info">
                        <h2 class="jbw-figma-product-title">{{ $displayTitle }}</h2>
                        @if ($specs)
                            <p class="jbw-figma-item-specs">{{ $specs }}</p>
                        @endif
                        <p class="jbw-figma-item-price">₹{{ number_format($displayPrice, 0) }}</p>
                        @if ($order->isRental() && $rentalStart)
                            <div class="jbw-figma-rental-block">
                                <span class="jbw-figma-rental-icon" aria-hidden="true">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </span>
                                <div>
                                    <span class="jbw-figma-rental-label">Rental Period</span>
                                    <p class="jbw-figma-rental-range">{{ $rentalStart->format('d M') }} - {{ $rentalEnd?->format('d M') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </article>

            @if ($order->vendor)
                <article class="jbw-figma-seller-card">
                    <a href="{{ route('web.chat.start', $order->vendor) }}" class="jbw-figma-video-btn" title="Video call" aria-label="Start video call">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17 10.5V7a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h12a1 1 0 001-1v-3.5l4 4v-11l-4 4z"/></svg>
                    </a>
                    <div class="jbw-figma-seller-head">
                        @php
                            $vendorImg = $order->vendor->profileImageUrl() ?? $order->vendor->shopLogoUrl();
                        @endphp
                        @if ($vendorImg)
                            <img src="{{ $vendorImg }}" alt="" class="jbw-figma-seller-avatar">
                        @else
                            <span class="jbw-figma-seller-avatar jbw-figma-seller-avatar--fallback">{{ mb_substr($order->vendor->brand_name, 0, 1) }}</span>
                        @endif
                        <div class="jbw-figma-seller-info">
                            <h3 class="jbw-figma-seller-name">{{ $order->vendor->brand_name }}</h3>
                            <p class="jbw-figma-seller-meta">
                                <span class="starcolor">★</span> {{ number_format($order->vendor->rating, 1) }}
                            </p>
                            @if ($order->vendor->city)
                                <p class="jbw-figma-seller-location">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 10-14 0c0 6.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                    {{ $order->vendor->city }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <p class="jbw-figma-seller-note">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Video calling is limited to a duration of 5 minute per session.
                    </p>
                    <a href="{{ route('web.chat.start', $order->vendor) }}" class="jbw-btn jbw-btn--outline jbw-btn--block jbw-figma-chat-btn">
                        Chat with {{ $order->vendor->brand_name }}
                    </a>
                </article>
            @endif

            @if ($customerNotes)
                <section class="jbw-figma-booking-section">
                    <h2 class="jbw-figma-section-title">Additional Notes</h2>
                    <div class="jbw-figma-notes-box jbw-figma-notes-box--accent">{{ $customerNotes }}</div>
                </section>
            @endif

            @if (count($referenceImages) > 0)
                <section class="jbw-figma-booking-section">
                    <h2 class="jbw-figma-section-title">Reference Images</h2>
                    <div class="jbw-figma-ref-grid">
                        @foreach ($referenceImages as $url)
                            <a href="{{ $url }}" class="jbw-figma-ref-item" target="_blank" rel="noopener">
                                <img src="{{ $url }}" alt="Reference image">
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <div id="jbw-rate-review">
                @include('web.partials.booking-item-actions', [
                    'order' => $order,
                    'orderItem' => $focusedItem,
                ])
            </div>
        </div>

        <aside class="jbw-figma-booking-aside">
            @include('web.partials.booking-figma-payment-summary', [
                'context' => $checkoutOrder ? 'checkout' : 'order',
                'model' => $checkoutOrder ?? $order,
                'paymentSummary' => $paymentSummary,
                'showPayButton' => ! $checkoutOrder && ($paymentSummary['can_pay'] ?? false),
                'payUrl' => ($paymentSummary['can_pay'] ?? false) && ! $checkoutOrder
                    ? route('web.bookings.payment', $order)
                    : null,
                'showRateReview' => $showRateReview,
            ])
        </aside>
    </div>
</div>
@endsection

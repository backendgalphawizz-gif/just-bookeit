@extends('web.layouts.app')

@section('title', 'Booking '.$checkoutOrder->order_number)

@section('content')
@php
    $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80';
    $paymentSummary = $paymentSummary ?? [];

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

    $bookingLines = [];
    foreach ($checkoutOrder->subOrders as $subOrder) {
        if ($subOrder->orderItems->isNotEmpty()) {
            foreach ($subOrder->orderItems as $orderItem) {
                $bookingLines[] = ['subOrder' => $subOrder, 'orderItem' => $orderItem];
            }
        } else {
            $bookingLines[] = ['subOrder' => $subOrder, 'orderItem' => null];
        }
    }
@endphp

<div class="jbw-container jbw-figma-booking-page">
    <nav class="jbw-figma-back-nav">
        <a href="{{ route('web.bookings.index') }}" class="jbw-figma-back-link">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
            Back to Profile
        </a>
    </nav>

    <header class="jbw-figma-booking-head">
        <h1 class="jbw-figma-booking-title">Booking Detail</h1>
        <p class="jbw-figma-booking-meta">
            #{{ $checkoutOrder->order_number }}
            <span aria-hidden="true">·</span>
            {{ $checkoutOrder->created_at->format('M d, Y') }}
            <span aria-hidden="true">·</span>
            {{ $checkoutOrder->created_at->format('H:i') }}
        </p>
    </header>

    <div class="jbw-figma-booking-layout">
        <div class="jbw-figma-booking-main">
            <section class="jbw-figma-booking-section">
                <h2 class="jbw-figma-section-title">Shipping Address</h2>
                <div class="jbw-figma-address-card">
                    <span class="jbw-figma-address-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 21s7-4.5 7-11a7 7 0 10-14 0c0 6.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    </span>
                    <div class="jbw-figma-address-body">
                        <p class="jbw-figma-address-name">{{ $checkoutOrder->customer?->name ?? auth('customer')->user()?->name }}</p>
                        <p class="jbw-figma-address-lines">
                            {{ $checkoutOrder->delivery_address }}{{ $checkoutOrder->city ? ', '.$checkoutOrder->city : '' }}{{ $checkoutOrder->pincode ? ' '.$checkoutOrder->pincode : '' }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="jbw-figma-booking-section">
                <h2 class="jbw-figma-section-title">Items In This Booking</h2>
                <div class="jbw-figma-item-list">
                    @foreach ($bookingLines as $line)
                        @php
                            $subOrder = $line['subOrder'];
                            $orderItem = $line['orderItem'];
                            $statusClass = \App\Support\WebBookingStatus::badgeClass(
                                $orderItem ? $orderItem->statusForDisplay($subOrder) : $subOrder->status
                            );
                            $statusLabel = $orderItem
                                ? $orderItem->statusLabel()
                                : $subOrder->statusLabel();
                            $detailUrl = route('web.bookings.show', $subOrder).($orderItem ? '?item='.$orderItem->id : '');
                            $typeLabel = $bookingTypeLabel(
                                $orderItem?->portfolioItem?->category ?? $subOrder->category,
                                $subOrder->orderTypeLabel()
                            );
                            $specs = $orderItem
                                ? collect([$orderItem->color(), $orderItem->size()])->filter()->implode(' | ')
                                : collect([$subOrder->color, $subOrder->size])->filter()->implode(' | ');
                        @endphp
                        <article class="jbw-figma-item-card">
                            <span class="jbw-figma-item-status jbw-figma-item-status--{{ $statusClass }}">{{ $statusLabel }}</span>
                            <div class="jbw-figma-item-card-body">
                                <img
                                    src="{{ $orderItem?->displayImageUrl() ?: $subOrder->itemImageUrl() ?: $fallbackImg }}"
                                    alt=""
                                    class="jbw-figma-item-img"
                                >
                                <div class="jbw-figma-item-info">
                                    <div class="jbw-figma-item-title-row">
                                        <h3 class="jbw-figma-item-title">{{ $orderItem?->title() ?: $subOrder->itemDisplayName() }}</h3>
                                        <span class="jbw-figma-item-tag">{{ $typeLabel }}</span>
                                    </div>
                                    @if ($specs)
                                        <p class="jbw-figma-item-specs">{{ $specs }}</p>
                                    @endif
                                    <p class="jbw-figma-item-price">₹{{ number_format($orderItem?->line_amount ?? $subOrder->amount, 0) }}</p>
                                </div>
                            </div>
                            <div class="jbw-figma-item-card-foot">
                                <a href="{{ $detailUrl }}" class="jbw-figma-item-link">
                                    View Detail
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            @if ($checkoutOrder->customer_notes)
                <section class="jbw-figma-booking-section">
                    <h2 class="jbw-figma-section-title">Additional Notes</h2>
                    <div class="jbw-figma-notes-box">{{ $checkoutOrder->customer_notes }}</div>
                </section>
            @endif
        </div>

        <aside class="jbw-figma-booking-aside">
            @include('web.partials.booking-figma-payment-summary', [
                'context' => 'checkout',
                'model' => $checkoutOrder,
                'paymentSummary' => $paymentSummary,
                'showPayButton' => false,
            ])
        </aside>
    </div>
</div>
@endsection

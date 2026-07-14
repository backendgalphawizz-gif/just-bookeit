@extends('web.layouts.profile')

@section('title', 'Order '.$checkoutOrder->order_number)
@section('page_title', 'Booking History')
@section('page_subtitle', 'View and manage your past and upcoming dress rentals.')

@section('content')
@php
    $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80';
    $itemCount = $checkoutOrder->subOrders->sum(fn ($o) => max(1, $o->orderItems->count()));
    $statusClass = match ($checkoutOrder->status) {
        'new', 'pending_acceptance' => 'new',
        'processing', 'partially_delivered' => 'in_progress',
        'completed' => 'delivered',
        'cancelled', 'refunded', 'partially_cancelled' => 'cancelled',
        default => 'default',
    };
    $paymentClass = match ($checkoutOrder->payment_status) {
        'success' => 'paid',
        'failed' => 'failed',
        default => 'pending',
    };
@endphp

<div class="jbw-card jbw-order-detail">
    <header class="jbw-order-detail-header">
        <a href="{{ route('web.bookings.index') }}" class="jbw-breadcrumb-link jbw-order-detail-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            Back to bookings
        </a>
        <div class="jbw-order-detail-head-row">
            <div>
                <h1 class="jbw-order-detail-id">Order #{{ $checkoutOrder->order_number }}</h1>
                <p class="jbw-order-detail-meta">
                    Placed {{ $checkoutOrder->created_at->format('M d, Y · h:i A') }}
                    · {{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }}
                    · {{ $checkoutOrder->subOrders->count() }} vendor{{ $checkoutOrder->subOrders->count() === 1 ? '' : 's' }}
                </p>
            </div>
            <div class="jbw-order-detail-badges">
                <span class="jbw-status jbw-status--{{ $statusClass }}">{{ $checkoutOrder->statusLabel() }}</span>
                <span class="jbw-booking-pay-badge jbw-booking-pay-badge--{{ $paymentClass }}">
                    Payment {{ ucfirst(str_replace('_', ' ', $checkoutOrder->payment_status)) }}
                </span>
            </div>
        </div>
    </header>

    @if ($checkoutOrder->payment_status === 'pending')
        <div class="jbw-order-pay-banner">
            <p>Complete payment to confirm this order.</p>
            <a href="{{ route('web.checkout.payment', $checkoutOrder) }}" class="jbw-btn jbw-btn--primary jbw-btn--sm">Pay now</a>
        </div>
    @endif

    <div class="jbw-order-detail-layout">
        <div class="jbw-order-detail-main">
            <section class="jbw-order-info-grid">
                @if ($checkoutOrder->rental_start_date && $checkoutOrder->rental_end_date)
                    <div class="jbw-order-info-tile">
                        <span class="jbw-order-info-label">Rental period</span>
                        <strong>{{ $checkoutOrder->rental_start_date->format('d M') }} – {{ $checkoutOrder->rental_end_date->format('d M, Y') }}</strong>
                        <span class="jbw-order-info-sub">{{ $checkoutOrder->rental_start_date->diffInDays($checkoutOrder->rental_end_date) + 1 }} day{{ ($checkoutOrder->rental_start_date->diffInDays($checkoutOrder->rental_end_date) + 1) === 1 ? '' : 's' }}</span>
                    </div>
                @endif
                <div class="jbw-order-info-tile jbw-order-info-tile--wide">
                    <span class="jbw-order-info-label">Delivery address</span>
                    <strong>{{ $checkoutOrder->delivery_address }}</strong>
                    @if ($checkoutOrder->city)
                        <span class="jbw-order-info-sub">{{ $checkoutOrder->city }}@if($checkoutOrder->pincode) · {{ $checkoutOrder->pincode }}@endif</span>
                    @endif
                </div>
            </section>

            <section class="jbw-order-vendors">
                <h2 class="jbw-order-section-title">Items &amp; tracking</h2>

                @foreach ($checkoutOrder->subOrders as $subOrder)
                    @php
                        $subStatusClass = match($subOrder->status) {
                            'new', 'pending_acceptance' => 'new',
                            'in_progress', 'accepted' => 'in_progress',
                            'delivered' => 'delivered',
                            'cancelled', 'refunded' => 'cancelled',
                            default => 'default',
                        };
                    @endphp
                    <article class="jbw-order-vendor-card">
                        <header class="jbw-order-vendor-card-head">
                            <div class="jbw-order-vendor-identity">
                                @if ($subOrder->vendor?->profileImageUrl() || $subOrder->vendor?->shopLogoUrl())
                                    <img src="{{ $subOrder->vendor->profileImageUrl() ?: $subOrder->vendor->shopLogoUrl() }}" alt="" class="jbw-order-vendor-avatar">
                                @else
                                    <span class="jbw-order-vendor-avatar jbw-order-vendor-avatar--fallback">{{ mb_substr($subOrder->vendor?->brand_name ?? 'V', 0, 1) }}</span>
                                @endif
                                <div>
                                    <h3 class="jbw-order-vendor-name">{{ $subOrder->vendor?->brand_name ?? 'Designer' }}</h3>
                                    <p class="jbw-order-vendor-sub">
                                        {{ max(1, $subOrder->orderItems->count()) }} item{{ $subOrder->orderItems->count() === 1 ? '' : 's' }}
                                        · ₹{{ number_format($subOrder->grandTotal(), 0) }} total
                                    </p>
                                </div>
                            </div>
                            <span class="jbw-status jbw-status--{{ $subStatusClass }}">{{ $subOrder->statusLabel() }}</span>
                        </header>

                        <div class="jbw-order-lines">
                            @if ($subOrder->orderItems->isNotEmpty())
                                @foreach ($subOrder->orderItems as $orderItem)
                                    @include('web.partials.order-item-block', [
                                        'image' => $orderItem->displayImageUrl(),
                                        'fallback' => $fallbackImg,
                                        'title' => $orderItem->title(),
                                        'category' => $orderItem->categoryName(),
                                        'variantLabel' => $orderItem->variantLabel(),
                                        'quantity' => $orderItem->quantity,
                                        'unitPrice' => '₹'.number_format($orderItem->unit_price, 0).'/day',
                                        'lineTotal' => '₹'.number_format($orderItem->line_amount, 0),
                                        'order' => $subOrder,
                                    ])
                                @endforeach
                            @else
                                @include('web.partials.order-item-block', [
                                    'image' => $subOrder->itemImageUrl(),
                                    'fallback' => $fallbackImg,
                                    'title' => $subOrder->itemDisplayName(),
                                    'category' => $subOrder->category?->name,
                                    'variantLabel' => collect([$subOrder->size, $subOrder->color])->filter()->implode(' · ') ?: null,
                                    'quantity' => $subOrder->quantity ?? 1,
                                    'lineTotal' => '₹'.number_format($subOrder->amount, 0),
                                    'order' => $subOrder,
                                ])
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>

            @if ($checkoutOrder->customer_notes)
                <section class="jbw-order-notes">
                    <h2 class="jbw-order-section-title">Your notes</h2>
                    <p>{{ $checkoutOrder->customer_notes }}</p>
                </section>
            @endif

            @if ($checkoutOrder->refunds->isNotEmpty())
                <section class="jbw-order-notes">
                    <h2 class="jbw-order-section-title">Refunds</h2>
                    @foreach ($checkoutOrder->refunds as $refund)
                        <div class="jbw-order-refund-row">
                            <strong>₹{{ number_format($refund->amount, 0) }}</strong>
                            <span>{{ ucfirst($refund->status) }}</span>
                            @if ($refund->reason)
                                <p>{{ $refund->reason }}</p>
                            @endif
                        </div>
                    @endforeach
                </section>
            @endif
        </div>

        <aside class="jbw-order-detail-aside">
            <div class="jbw-overview-card jbw-overview-card--accent">
                <p class="jbw-overview-label">Payment summary</p>
                <div class="jbw-payment-lines">
                    <div><span>Subtotal</span><span>₹{{ number_format($checkoutOrder->amount, 0) }}</span></div>
                    <div><span>Delivery</span><span>₹{{ number_format($checkoutOrder->delivery_fee, 0) }}</span></div>
                    <div><span>GST</span><span>₹{{ number_format($checkoutOrder->tax_amount, 0) }}</span></div>
                </div>
                <div class="jbw-payment-total">
                    <span>Total paid</span>
                    <strong>₹{{ number_format($checkoutOrder->grand_total, 0) }}</strong>
                </div>
                @if ((float) $checkoutOrder->amount_refunded > 0)
                    <p class="jbw-order-refund-note">Refunded: ₹{{ number_format($checkoutOrder->amount_refunded, 0) }}</p>
                @endif
            </div>
        </aside>
    </div>
</div>
@endsection

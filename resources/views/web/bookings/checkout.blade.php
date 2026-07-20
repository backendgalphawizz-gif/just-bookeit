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
        'advance_paid' => 'pending',
        'failed' => 'failed',
        default => 'pending',
    };
    $paymentSummary = $paymentSummary ?? [];
@endphp

<div class="jbw-order-detail">
    <div class="jbw-order-hero jbw-order-hero--{{ $paymentClass }}">
        <a href="{{ route('web.bookings.index') }}" class="jbw-order-hero-back">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
            Back to bookings
        </a>
        <div class="jbw-order-hero-row">
            <div class="jbw-order-hero-main">
                <span class="jbw-order-hero-tag">Order</span>
                <h1 class="jbw-order-hero-id">#{{ $checkoutOrder->order_number }}</h1>
                <p class="jbw-order-hero-meta">
                    <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Placed {{ $checkoutOrder->created_at->format('M d, Y') }} · {{ $checkoutOrder->created_at->format('h:i A') }}</span>
                    <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg> {{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }} from {{ $checkoutOrder->subOrders->count() }} designer{{ $checkoutOrder->subOrders->count() === 1 ? '' : 's' }}</span>
                </p>
            </div>
            <div class="jbw-order-hero-badges">
                <span class="jbw-order-hero-badge jbw-order-hero-badge--status jbw-order-hero-badge--{{ $statusClass }}">{{ $checkoutOrder->statusLabel() }}</span>
                <span class="jbw-order-hero-badge jbw-order-hero-badge--pay jbw-order-hero-badge--pay-{{ $paymentClass }}">
                    @if ($paymentClass === 'paid')
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    @elseif ($paymentClass === 'pending')
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    @else
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    @endif
                    Payment {{ ucfirst(str_replace('_', ' ', $checkoutOrder->payment_status)) }}
                </span>
            </div>
        </div>
    </div>

    @if (($paymentSummary['can_pay'] ?? false))
        <div class="jbw-order-pay-banner">
            <div class="jbw-order-pay-banner-msg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <p>
                    @if (($paymentSummary['payment_phase'] ?? '') === 'remaining_due')
                        <strong>Remaining payment due.</strong> Advance is paid — settle the balance to complete payment.
                    @elseif (($paymentSummary['payment_phase'] ?? '') === 'advance_due')
                        <strong>Advance payment pending.</strong> Pay the advance to confirm this order.
                    @elseif (($paymentSummary['payment_phase'] ?? '') === 'advance_paid_waiting')
                        <strong>Advance paid.</strong> Remaining balance will be due when the booking is completed.
                    @else
                        <strong>Payment pending.</strong> Complete payment to confirm this order.
                    @endif
                </p>
            </div>
            <a href="{{ route('web.checkout.payment', $checkoutOrder) }}" class="jbw-btn jbw-btn--primary jbw-btn--sm">{{ $paymentSummary['pay_label'] }}</a>
        </div>
    @endif

    <div class="jbw-order-detail-layout">
        <div class="jbw-order-detail-main">
            <section class="jbw-order-info-grid">
                @if ($checkoutOrder->rental_start_date && $checkoutOrder->rental_end_date)
                    <div class="jbw-order-info-tile">
                        <span class="jbw-order-info-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </span>
                        <div class="jbw-order-info-body">
                            <span class="jbw-order-info-label">Rental period</span>
                            <strong>{{ $checkoutOrder->rental_start_date->format('d M') }} – {{ $checkoutOrder->rental_end_date->format('d M, Y') }}</strong>
                            <span class="jbw-order-info-sub">{{ $checkoutOrder->rental_start_date->diffInDays($checkoutOrder->rental_end_date) + 1 }} day{{ ($checkoutOrder->rental_start_date->diffInDays($checkoutOrder->rental_end_date) + 1) === 1 ? '' : 's' }}</span>
                        </div>
                    </div>
                @endif
                <div class="jbw-order-info-tile jbw-order-info-tile--wide">
                    <span class="jbw-order-info-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </span>
                    <div class="jbw-order-info-body">
                        <span class="jbw-order-info-label">Delivery address</span>
                        <strong>{{ $checkoutOrder->delivery_address }}</strong>
                        @if ($checkoutOrder->city)
                            <span class="jbw-order-info-sub">{{ $checkoutOrder->city }}@if($checkoutOrder->pincode) · {{ $checkoutOrder->pincode }}@endif</span>
                        @endif
                    </div>
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
            <div class="jbw-overview-card jbw-overview-card--accent jbw-order-summary-card">
                <p class="jbw-overview-label">Payment summary</p>
                <div class="jbw-payment-lines">
                    <div><span>Subtotal</span><span>₹{{ number_format($checkoutOrder->amount, 0) }}</span></div>
                    <div><span>Delivery</span><span>₹{{ number_format($checkoutOrder->delivery_fee, 0) }}</span></div>
                    <div><span>GST</span><span>₹{{ number_format($checkoutOrder->tax_amount, 0) }}</span></div>
                    @if (($paymentSummary['advance_amount'] ?? 0) > 0)
                        <div><span>Advance</span><span>₹{{ number_format($paymentSummary['advance_amount'], 0) }}</span></div>
                    @endif
                    @if (($paymentSummary['amount_paid'] ?? 0) > 0)
                        <div><span>Paid so far</span><span>₹{{ number_format($paymentSummary['amount_paid'], 0) }}</span></div>
                    @endif
                    @if (($paymentSummary['remaining_amount'] ?? 0) > 0 && ($paymentSummary['payment_phase'] ?? '') === 'remaining_due')
                        <div><span>Remaining</span><span>₹{{ number_format($paymentSummary['remaining_amount'], 0) }}</span></div>
                    @endif
                </div>
                <div class="jbw-payment-total">
                    <span>
                        @if ($checkoutOrder->payment_status === 'success')
                            Total paid
                        @elseif (($paymentSummary['can_pay'] ?? false))
                            Pay now
                        @else
                            Booking total
                        @endif
                    </span>
                    <strong>₹{{ number_format(($paymentSummary['can_pay'] ?? false) ? $paymentSummary['payable_now'] : $checkoutOrder->grand_total, 0) }}</strong>
                </div>
                @if ((float) $checkoutOrder->amount_refunded > 0)
                    <p class="jbw-order-refund-note">Refunded: ₹{{ number_format($checkoutOrder->amount_refunded, 0) }}</p>
                @endif
                @if ($checkoutOrder->payment_status === 'success')
                    <div class="jbw-order-paid-check" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Payment received</span>
                    </div>
                @elseif (($paymentSummary['can_pay'] ?? false))
                    <a href="{{ route('web.checkout.payment', $checkoutOrder) }}" class="jbw-btn jbw-btn--primary jbw-btn--block" style="margin-top:1rem">{{ $paymentSummary['pay_label'] }}</a>
                @endif
            </div>
        </aside>
    </div>
</div>
@endsection

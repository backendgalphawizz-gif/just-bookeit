@extends('web.layouts.app')

@section('title', 'Complete payment')

@section('content')
@php $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80'; @endphp

<div class="jbw-container jbw-page-shell jbw-payment-page">
    <!-- <nav class="jbw-breadcrumb" style="margin-bottom:0.5rem">
        <a href="{{ route('web.bookings.checkout.show', $checkoutOrder) }}" class="jbw-breadcrumb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            Back to order
        </a>
    </nav> -->

    <div class="jbw-page-head" style="padding-top:0;margin-bottom:0.75rem">
        <h1 class="jbw-page-title">Complete payment</h1>
        <p class="jbw-page-subtitle">Order #{{ $checkoutOrder->order_number }} · {{ $checkoutOrder->subOrders->count() }} vendor{{ $checkoutOrder->subOrders->count() === 1 ? '' : 's' }}</p>
    </div>

    <div class="jbw-payment-layout">
        <div class="jbw-payment-main">
            <section class="jbw-overview-card" style="margin-bottom: 10px;">
                <p class="jbw-overview-label">Order items</p>
                @foreach ($checkoutOrder->subOrders as $subOrder)
                    <div class="jbw-checkout-vendor-block">
                        <p class="jbw-checkout-vendor-name">{{ $subOrder->vendor?->brand_name ?? 'Designer' }}</p>
                        <div class="jbw-line-item-list">
                            @forelse ($subOrder->orderItems as $orderItem)
                                @include('web.partials.line-item-row', [
                                    'image' => $orderItem->displayImageUrl(),
                                    'fallback' => $fallbackImg,
                                    'title' => $orderItem->title(),
                                    'category' => $orderItem->categoryName(),
                                    'variantLabel' => $orderItem->variantLabel(),
                                    'quantity' => $orderItem->quantity,
                                    'unitPrice' => '₹'.number_format($orderItem->unit_price, 0).' / day',
                                    'lineTotal' => '₹'.number_format($orderItem->line_amount, 0),
                                    'compact' => true,
                                ])
                            @empty
                                @include('web.partials.line-item-row', [
                                    'image' => $subOrder->itemImageUrl(),
                                    'fallback' => $fallbackImg,
                                    'title' => $subOrder->itemDisplayName(),
                                    'category' => $subOrder->category?->name,
                                    'variantLabel' => collect([$subOrder->size, $subOrder->color])->filter()->implode(' · ') ?: null,
                                    'quantity' => $subOrder->quantity ?? 1,
                                    'lineTotal' => '₹'.number_format($subOrder->amount, 0),
                                    'compact' => true,
                                ])
                            @endforelse
                        </div>
                    </div>
                @endforeach

                @if ($checkoutOrder->rental_start_date && $checkoutOrder->rental_end_date)
                    <div class="jbw-payment-rental-dates">
                        <span>Rental period</span>
                        <strong>{{ $checkoutOrder->rental_start_date->format('d M') }} – {{ $checkoutOrder->rental_end_date->format('d M, Y') }}</strong>
                    </div>
                @endif
            </section>

            <form id="jbw-payment-form" method="POST" action="{{ route('web.checkout.payment.pay', $checkoutOrder) }}" class="jbw-overview-card">
                @csrf
                <input type="hidden" name="razorpay_payment_id" value="">
                <input type="hidden" name="razorpay_order_id" value="">
                <input type="hidden" name="razorpay_signature" value="">
                <p class="jbw-overview-label">Payment method</p>
                <div class="jbw-payment-methods">
                    @foreach ($paymentMethods as $method)
                        <label class="jbw-payment-method">
                            <input type="radio" name="payment_method" value="{{ $method['id'] }}" @checked(old('payment_method', $paymentMethods[0]['id'] ?? '') === $method['id']) required>
                            <span>{{ $method['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('payment_method')<p class="jbw-field-error">{{ $message }}</p>@enderror
                <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--block jbw-payment-submit">
                    {{ $pricing['pay_label'] ?? ('Pay ₹'.number_format($pricing['payable_now'] ?? $checkoutOrder->grand_total, 0)) }}
                </button>
                <p class="jbw-payment-secure-note">
                    @if (! empty($razorpayEnabled))
                        Secured by Razorpay. Test mode — use Razorpay test cards / UPI.
                    @else
                        Secure demo payment — no real charge is made.
                    @endif
                </p>
            </form>
        </div>

        <aside class="jbw-payment-sidebar">
            <div class="jbw-overview-card jbw-overview-card--accent">
                <p class="jbw-overview-label">Amount due</p>
                <div class="jbw-payment-lines">
                    <div><span>Subtotal</span><span>₹{{ number_format($checkoutOrder->amount, 0) }}</span></div>
                    <div><span>Delivery</span><span>₹{{ number_format($checkoutOrder->delivery_fee, 0) }}</span></div>
                    <div><span>GST</span><span>₹{{ number_format($checkoutOrder->tax_amount, 0) }}</span></div>
                    <div><span>Booking total</span><span>₹{{ number_format($checkoutOrder->grand_total, 0) }}</span></div>
                    @if (($pricing['advance_amount'] ?? 0) > 0)
                        <div><span>Advance</span><span>₹{{ number_format($pricing['advance_amount'], 0) }}</span></div>
                    @endif
                    @if (($pricing['amount_paid'] ?? 0) > 0)
                        <div><span>Already paid</span><span>₹{{ number_format($pricing['amount_paid'], 0) }}</span></div>
                    @endif
                </div>
                <div class="jbw-payment-total">
                    <span>{{ ($pricing['payment_phase'] ?? '') === 'remaining_due' ? 'Pay remaining' : (($pricing['payment_phase'] ?? '') === 'advance_due' ? 'Pay advance' : 'Pay now') }}</span>
                    <strong>₹{{ number_format($pricing['payable_now'] ?? $checkoutOrder->grand_total, 0) }}</strong>
                </div>
                @if ($checkoutOrder->delivery_address)
                    <div class="jbw-payment-address">
                        <p class="jbw-overview-label" style="margin-top:1.25rem">Deliver to</p>
                        <p style="margin:0;font-size:0.875rem;line-height:1.5">{{ $checkoutOrder->delivery_address }}</p>
                        @if ($checkoutOrder->city)
                            <p style="margin:0.25rem 0 0;font-size:0.8125rem;color:var(--c-muted)">{{ $checkoutOrder->city }}@if($checkoutOrder->pincode) · {{ $checkoutOrder->pincode }}@endif</p>
                        @endif
                    </div>
                @endif
            </div>
        </aside>
    </div>
</div>
@push('scripts')
@include('web.partials.razorpay-checkout', ['razorpayOptions' => $razorpayOptions ?? null])
@endpush
@endsection

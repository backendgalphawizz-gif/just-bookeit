@extends('web.layouts.app')

@section('title', 'Complete payment')

@section('content')
@php
    $payableNow = (float) ($pricing['payable_now'] ?? $pricing['total_amount'] ?? 0);
    $isAdvance = ($pricing['payment_phase'] ?? '') === 'advance_due';
    $isRemaining = ($pricing['payment_phase'] ?? '') === 'remaining_due';
@endphp
<div class="jbw-container" style="max-width:40rem">
    <p style="margin-bottom:1rem"><a href="{{ route('web.bookings.show', $order) }}" class="jbw-back-link">← Booking detail</a></p>

    <div class="jbw-page-head">
        <h1 class="jbw-page-title">{{ $isRemaining ? 'Pay remaining amount' : ($isAdvance ? 'Pay advance' : 'Complete payment') }}</h1>
        <p class="jbw-page-subtitle">Booking #{{ $order->order_number }}</p>
    </div>
<div class="jbw-booking-layout">

<div>
    <div class="jbw-overview-card" style="margin-bottom:1rem">
        <p class="jbw-overview-label">Order summary</p>
        <p style="margin:0;font-weight:700">{{ $order->itemDisplayName() }}</p>
        <p class="jbw-booking-product-meta">{{ $order->vendor?->brand_name }}</p>
        @if ($order->rental_start_date)
            <p class="jbw-booking-product-meta">
                Rental: {{ $order->rental_start_date->format('M d') }} – {{ $order->rental_end_date?->format('M d, Y') }}
                ({{ $order->rentalDurationDays() }} days)
            </p>
        @endif
    </div>



    <div class="jbw-overview-card" style="margin-bottom:1.25rem">
        <p class="jbw-overview-label">Amount due</p>
        <div class="jbw-payment-lines">
            <div><span>Rental ({{ $pricing['rental_days'] ?? $order->rentalDurationDays() ?? 1 }} days)</span><span>₹{{ number_format($pricing['subtotal'], 0) }}</span></div>
            <div><span>Delivery</span><span>₹{{ number_format($pricing['shipping_fee'], 0) }}</span></div>
            <div><span>GST</span><span>₹{{ number_format($pricing['tax_amount'], 0) }}</span></div>
            <div><span>Booking total</span><span>₹{{ number_format($pricing['total_amount'], 0) }}</span></div>
            @if (($pricing['advance_amount'] ?? 0) > 0)
                <div><span>Advance required</span><span>₹{{ number_format($pricing['advance_amount'], 0) }}</span></div>
            @endif
            @if (($pricing['amount_paid'] ?? 0) > 0)
                <div><span>Already paid</span><span>₹{{ number_format($pricing['amount_paid'], 0) }}</span></div>
            @endif
            @if (($pricing['remaining_amount'] ?? 0) > 0 && $isRemaining)
                <div><span>Remaining after advance</span><span>₹{{ number_format($pricing['remaining_amount'], 0) }}</span></div>
            @endif
        </div>
        <div class="jbw-payment-total">
            <span>{{ $isAdvance ? 'Pay advance now' : ($isRemaining ? 'Pay remaining now' : 'Pay now') }}</span>
            <strong>₹{{ number_format($payableNow, 0) }}</strong>
        </div>
        @if ($isAdvance)
            <p style="margin:0.75rem 0 0;font-size:0.8125rem;color:var(--c-muted)">
                Only the advance is collected now. Remaining balance is due when the booking is completed.
            </p>
        @endif
    </div>
</div>

    <form method="POST" action="{{ route('web.bookings.payment.pay', $order) }}" class="jbw-overview-card">
        @csrf
        <p class="jbw-overview-label">Payment method</p>
        <div style="display:grid;gap:0.5rem;margin-bottom:1.25rem">
            @foreach ($paymentMethods as $index => $method)
                <label style="display:flex;align-items:center;gap:0.65rem;padding:0.75rem 1rem;border:1px solid var(--jbw-border,#e2e8f0);border-radius:10px;cursor:pointer">
                    <input type="radio" name="payment_method" value="{{ $method['id'] }}" @checked(old('payment_method', $paymentMethods[0]['id'] ?? '') === $method['id']) required>
                    <span style="font-weight:600">{{ $method['label'] }}</span>
                </label>
            @endforeach
        </div>
        @error('payment_method')<p class="jbw-field-error">{{ $message }}</p>@enderror
        <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--block" style="border-radius:10px;padding:0.9375rem">
            {{ $pricing['pay_label'] ?? ('Pay ₹'.number_format($payableNow, 0)) }}
        </button>
        <p style="text-align:center;font-size:0.75rem;color:var(--c-muted);margin:0.75rem 0 0">
            Secure demo payment — no real charge is made.
        </p>
    </form>
</div>
</div>
@endsection

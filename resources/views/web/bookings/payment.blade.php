@extends('web.layouts.app')

@section('title', 'Complete payment')

@section('content')
<div class="jbw-container" style="max-width:40rem">
    <p style="margin-bottom:1rem"><a href="{{ route('web.bookings.show', $order) }}" class="jbw-back-link">← Booking detail</a></p>

    <div class="jbw-page-head">
        <h1 class="jbw-page-title">Complete payment</h1>
        <p class="jbw-page-subtitle">Booking #{{ $order->order_number }}</p>
    </div>

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
        </div>
        <div class="jbw-payment-total"><span>Total</span><strong>₹{{ number_format($pricing['total_amount'], 0) }}</strong></div>
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
            Pay ₹{{ number_format($pricing['total_amount'], 0) }}
        </button>
        <p style="text-align:center;font-size:0.75rem;color:var(--c-muted);margin:0.75rem 0 0">
            Secure demo payment — no real charge is made.
        </p>
    </form>
</div>
@endsection

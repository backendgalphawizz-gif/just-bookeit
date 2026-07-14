@extends('web.layouts.app')

@section('title', 'Order '.$checkoutOrder->order_number)

@section('content')
<div class="jbw-container">
    <div class="jbw-page-head">
        <h1 class="jbw-page-title">Order #{{ $checkoutOrder->order_number }}</h1>
        <p class="jbw-page-subtitle">
            {{ \App\Models\CheckoutOrder::statusLabelFor($checkoutOrder->status) }}
            · Payment {{ ucfirst(str_replace('_', ' ', $checkoutOrder->payment_status)) }}
        </p>
    </div>

    @if ($checkoutOrder->payment_status === 'pending')
        <div class="jbw-overview-card" style="margin-bottom:1rem;border-color:var(--c-primary)">
            <p style="margin:0 0 0.75rem">Payment is pending for this checkout.</p>
            <a href="{{ route('web.checkout.payment', $checkoutOrder) }}" class="jbw-btn jbw-btn--primary">Pay now</a>
        </div>
    @endif

    <div class="jbw-booking-layout">
        <div class="jbw-booking-main">
            <div class="jbw-overview-card">
                <p class="jbw-overview-label">Delivery</p>
                <p style="margin:0">{{ $checkoutOrder->delivery_address }}</p>
                @if ($checkoutOrder->city)<p style="margin:0.35rem 0 0;color:var(--c-muted)">{{ $checkoutOrder->city }}@if($checkoutOrder->pincode) · {{ $checkoutOrder->pincode }}@endif</p>@endif
            </div>

            @php $measurements = \App\Support\BookingMeasurementSupport::checkoutMeasurements($checkoutOrder); @endphp
            @if ($measurements['measurement_type'] || $measurements['height_cm'])
                <div class="jbw-overview-card">
                    <p class="jbw-overview-label">Measurements</p>
                    <p style="margin:0;font-size:0.875rem;color:var(--c-muted)">Saved once for this multi-vendor order.</p>
                </div>
            @endif

            <div class="jbw-overview-card">
                <p class="jbw-overview-label">Sub-orders by vendor</p>
                @foreach ($checkoutOrder->subOrders as $subOrder)
                    <div style="padding:0.85rem 0;border-bottom:1px solid var(--jbw-border,#e2e8f0)">
                        <p style="margin:0;font-weight:700">{{ $subOrder->vendor?->brand_name }}</p>
                        <p style="margin:0.25rem 0 0">{{ $subOrder->itemDisplayName() }}</p>
                        <p style="margin:0.35rem 0 0;font-size:0.8125rem;color:var(--c-muted)">
                            {{ $subOrder->sub_order_number ?? $subOrder->order_number }}
                            · {{ $subOrder->statusLabel() }}
                            · ₹{{ number_format($subOrder->grandTotal(), 0) }}
                        </p>
                        <a href="{{ route('web.bookings.show', $subOrder) }}" style="font-size:0.8125rem;font-weight:700;color:var(--c-primary)">Track sub-order</a>
                    </div>
                @endforeach
            </div>

            @if ($checkoutOrder->refunds->isNotEmpty())
                <div class="jbw-overview-card">
                    <p class="jbw-overview-label">Refunds</p>
                    @foreach ($checkoutOrder->refunds as $refund)
                        <div style="padding:0.5rem 0;border-bottom:1px solid var(--jbw-border,#e2e8f0)">
                            <p style="margin:0;font-weight:600">₹{{ number_format($refund->amount, 0) }} — {{ ucfirst($refund->status) }}</p>
                            <p style="margin:0.25rem 0 0;font-size:0.8125rem;color:var(--c-muted)">{{ $refund->reason }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="jbw-booking-sidebar">
            <div class="jbw-overview-card jbw-overview-card--accent">
                <p class="jbw-overview-label">Payment summary</p>
                <div class="jbw-payment-lines">
                    <div><span>Subtotal</span><span>₹{{ number_format($checkoutOrder->amount, 0) }}</span></div>
                    <div><span>Delivery</span><span>₹{{ number_format($checkoutOrder->delivery_fee, 0) }}</span></div>
                    <div><span>GST</span><span>₹{{ number_format($checkoutOrder->tax_amount, 0) }}</span></div>
                </div>
                <div class="jbw-payment-total"><span>Total</span><strong>₹{{ number_format($checkoutOrder->grand_total, 0) }}</strong></div>
                @if ((float) $checkoutOrder->amount_refunded > 0)
                    <p style="margin:0.75rem 0 0;font-size:0.8125rem;color:#b45309">Refunded: ₹{{ number_format($checkoutOrder->amount_refunded, 0) }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

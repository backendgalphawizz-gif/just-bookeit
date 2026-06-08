@extends('vendor.layouts.app')

@section('title', 'Booking '.$booking->order_number)

@section('content')
<a href="{{ route('vendor.bookings.index') }}" class="vp-back-link">← Back to bookings</a>

<div class="vp-page-head">
    <div>
        <h1 class="vp-page-title">#{{ $booking->order_number }}</h1>
        <p class="vp-page-sub">{{ $booking->itemDisplayName() }} · {{ $booking->statusLabel() }}</p>
    </div>
    @php
        $badge = match($booking->status) {
            'new','pending_acceptance' => 'new',
            'in_transit' => 'transit',
            'delivered' => 'done',
            default => 'accepted',
        };
    @endphp
    <span class="vp-badge vp-badge--{{ $badge }}">{{ $booking->statusLabel() }}</span>
</div>

<div class="vp-card vp-card-pad" style="margin-bottom:1.25rem;">
    <div class="vp-detail-grid">
        <div class="vp-detail-item">
            <div class="vp-stat-label">Customer</div>
            <strong>{{ $booking->customer?->name }}</strong><br>
            <span style="font-size:.85rem;color:var(--vp-muted);">{{ $booking->customer?->mobile }}</span>
        </div>
        <div class="vp-detail-item">
            <div class="vp-stat-label">Amount</div>
            <strong style="font-size:1.25rem;">₹{{ number_format($booking->grandTotal(), 0) }}</strong>
        </div>
        <div class="vp-detail-item">
            <div class="vp-stat-label">Payment</div>
            <strong>{{ ucfirst($booking->payment_status) }}</strong>
        </div>
        <div class="vp-detail-item">
            <div class="vp-stat-label">Delivery Address</div>
            <span style="font-size:.9rem;">{{ $booking->delivery_address }}</span>
        </div>
    </div>
    @if ($booking->customer_notes)
        <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--vp-border);">
            <div class="vp-stat-label">Customer Notes</div>
            <p style="margin:.35rem 0 0;font-size:.9rem;">{{ $booking->customer_notes }}</p>
        </div>
    @endif
</div>

@if (in_array($booking->status, ['new','pending_acceptance']))
    <div class="vp-actions" style="margin-bottom:1.25rem;">
        <form method="POST" action="{{ route('vendor.bookings.accept', $booking) }}">@csrf
            <button type="submit" class="vp-btn vp-btn--primary">Accept Booking</button>
        </form>
        <form method="POST" action="{{ route('vendor.bookings.reject', $booking) }}"
              data-vp-confirm="This booking will be rejected."
              data-vp-confirm-title="Reject booking?"
              data-vp-confirm-label="Reject"
              data-vp-confirm-variant="error">@csrf
            <button type="submit" class="vp-btn vp-btn--danger">Reject</button>
        </form>
    </div>
@endif

<div class="vp-card vp-card-pad">
    <form method="POST" action="{{ route('vendor.bookings.status', $booking) }}">
        @csrf
        <label class="vp-label">Update status</label>
        <div class="vp-actions">
            <select name="status" class="vp-select" style="max-width:240px;">
                @foreach (\App\Models\Order::STATUSES as $status)
                    <option value="{{ $status }}" @selected($booking->status === $status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="vp-btn vp-btn--outline">Update</button>
        </div>
    </form>
</div>
@endsection

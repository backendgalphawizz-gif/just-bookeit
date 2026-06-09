@extends('vendor.layouts.app')

@section('title', 'Bookings')

@section('content')
<div class="vp-page-head">
    <div>
        <h1 class="vp-page-title">Bookings</h1>
        <p class="vp-page-sub">Manage and track all customer orders</p>
    </div>
</div>

@push('filter_actions')
    <x-vendor.export-dropdown module="bookings" :params="['search', 'status', 'from', 'to']" />
@endpush

<form method="GET" class="vp-filters">
    <div class="vp-filters-grid">
        <div class="vp-filters-field vp-filters-field--wide">
            <label class="vp-label" for="booking-search">Search</label>
            <input type="text" id="booking-search" name="search" value="{{ request('search') }}" class="vp-input" placeholder="Order #, customer, item...">
        </div>
        <div class="vp-filters-field">
            <label class="vp-label" for="booking-status">Status</label>
            <select id="booking-status" name="status" class="vp-select">
                <option value="">All</option>
                @foreach (['new' => 'New', 'accepted' => 'Accepted', 'in_transit' => 'In Progress', 'delivered' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @include('vendor.partials.date-filter')
        @include('vendor.partials.filters-end', ['resetUrl' => route('vendor.bookings.index')])
    </div>
</form>

<div class="vp-card">
    <div class="vp-card-count">{{ $orders->total() }} bookings</div>
    <div class="vp-table-wrap">
        <table class="vp-table">
            <thead>
                <tr>
                    <th>Booking Info</th>
                    <th>Customer</th>
                    <th>Service Type</th>
                    <th>Date &amp; Total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>
                            <div class="vp-table-product">
                                @if ($order->itemImageUrl())
                                    <img src="{{ url($order->itemImageUrl()) }}" alt="" class="vp-thumb panel-lightbox-trigger">
                                @else
                                    <span class="vp-thumb"></span>
                                @endif
                                <div>
                                    <strong>{{ $order->itemDisplayName() }}</strong><br>
                                    <span style="font-size:.78rem;color:var(--vp-muted);">#{{ $order->order_number }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong>{{ $order->customer?->name ?? '—' }}</strong><br>
                            <span style="font-size:.78rem;color:var(--vp-muted);">{{ $order->customer?->mobile }}</span>
                        </td>
                        <td>
                            {{ $order->category?->name ?? 'Rental' }}<br>
                            <span style="font-size:.78rem;color:var(--vp-muted);">
                                @if ($order->isRental() && $order->rental_start_date && $order->rental_end_date)
                                    {{ $order->rental_start_date->format('jS M') }} to {{ $order->rental_end_date->format('jS M') }}
                                @elseif (! $order->isRental())
                                    Purchase
                                @endif
                            </span>
                        </td>
                        <td>
                            <strong>₹{{ number_format($order->grandTotal(), 0) }}</strong><br>
                            <span style="font-size:.78rem;color:var(--vp-muted);">{{ $order->created_at?->format('M d, Y g:i A') }}</span>
                        </td>
                        <td>
                            @php
                                $badge = match($order->status) {
                                    'new','pending_acceptance' => 'new',
                                    'in_transit' => 'transit',
                                    'delivered' => 'done',
                                    default => 'accepted',
                                };
                            @endphp
                            <span class="vp-badge vp-badge--{{ $badge }}">{{ $order->statusLabel() }}</span>
                        </td>
                        <td>
                            <div class="vp-actions">
                                @if (in_array($order->status, ['new','pending_acceptance']))
                                    <form method="POST" action="{{ route('vendor.bookings.accept', $order) }}">@csrf
                                        <button type="submit" class="vp-btn vp-btn--primary vp-btn--sm">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('vendor.bookings.reject', $order) }}"
                                          data-vp-confirm="This booking will be rejected."
                                          data-vp-confirm-title="Reject booking?"
                                          data-vp-confirm-label="Reject"
                                          data-vp-confirm-variant="error">@csrf
                                        <button type="submit" class="vp-btn vp-btn--danger vp-btn--sm">Reject</button>
                                    </form>
                                @endif
                                <a href="{{ route('vendor.bookings.show', $order) }}" class="vp-btn vp-btn--outline vp-btn--sm">View</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="vp-empty">No bookings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($orders->hasPages())
        <div class="vp-card-pad">{{ $orders->links('vendor.pagination.default') }}</div>
    @endif
</div>
@endsection

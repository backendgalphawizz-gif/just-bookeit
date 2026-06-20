@extends('vendor.layouts.app')

@section('title', 'Bookings')

@section('content')
@php
    $statusOptions = [
        '' => 'All statuses',
        'new' => 'New',
        'accepted' => 'Accepted',
        'in_progress' => 'In progress',
        'delivered' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    $activeFilters = collect([
        'Search' => request('search'),
        'Status' => request('status') ? ($statusOptions[request('status')] ?? request('status')) : null,
        'From' => request('from'),
        'To' => request('to'),
    ])->filter(fn ($value) => filled($value));
@endphp

<div class="vp-page-head">
    <div>
        <h1 class="vp-page-title">Bookings</h1>
        <p class="vp-page-sub">Manage and track all customer orders</p>
    </div>
</div>

@push('filter_actions')
    <x-vendor.export-dropdown module="bookings" :params="['search', 'status', 'from', 'to']" />
@endpush

<form method="GET" class="vp-filters vp-card" style="padding: 1rem;">
    <div class="vp-filters-grid">
        <div class="vp-filters-field vp-filters-field--wide">
            <label class="vp-label" for="booking-search">Search</label>
            <input
                type="search"
                id="booking-search"
                name="search"
                value="{{ request('search') }}"
                class="vp-input"
                placeholder="Order #, customer, item..."
                autocomplete="off"
            >
        </div>
        <div class="vp-filters-field">
            <label class="vp-label" for="booking-status">Status</label>
            <select id="booking-status" name="status" class="vp-select">
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @include('vendor.partials.date-filter')
        @include('vendor.partials.filters-end', ['resetUrl' => route('vendor.bookings.index')])
    </div>

    @if ($activeFilters->isNotEmpty())
        <div class="vp-filters-active">
            <span class="vp-filters-active__label">Active:</span>
            @foreach ($activeFilters as $label => $value)
                <span class="vp-filter-chip">{{ $label }}: {{ $value }}</span>
            @endforeach
            <a href="{{ route('vendor.bookings.index') }}" class="vp-filter-chip vp-filter-chip--clear">Clear all</a>
        </div>
    @endif
</form>

<div class="vp-card" style="margin-top: 1rem;">
    <div class="vp-card-head">
        <h3>All bookings</h3>
        <span class="vp-card-count-inline">{{ $orders->total() }} {{ Str::plural('booking', $orders->total()) }}</span>
    </div>
    <div class="vp-table-wrap">
        <table class="vp-table">
            <thead>
                <tr>
                    <th>Booking info</th>
                    <th>Customer</th>
                    <th>Service type</th>
                    <th>Date &amp; total</th>
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
                                    <span class="vp-table-meta">#{{ $order->order_number }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong>{{ $order->customer?->name ?? '—' }}</strong><br>
                            <span class="vp-table-meta">{{ $order->customer?->mobile }}</span>
                        </td>
                        <td>
                            {{ $order->category?->name ?? 'Rental' }}<br>
                            <span class="vp-table-meta">
                                @if ($order->isRental() && $order->rental_start_date && $order->rental_end_date)
                                    {{ $order->rental_start_date->format('j M') }} – {{ $order->rental_end_date->format('j M') }}
                                @elseif (! $order->isRental())
                                    Purchase
                                @endif
                            </span>
                        </td>
                        <td>
                            <strong>₹{{ number_format($order->grandTotal(), 0) }}</strong><br>
                            <span class="vp-table-meta">{{ $order->created_at?->format('M d, Y g:i A') }}</span>
                        </td>
                        <td>
                            @php
                                $badge = match($order->status) {
                                    'new','pending_acceptance' => 'new',
                                    'in_progress' => 'transit',
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
                    <tr>
                        <td colspan="6">
                            <div class="vp-empty-state">
                                <p class="vp-empty-state__title">No bookings found</p>
                                <p class="vp-empty-state__text">
                                    @if ($activeFilters->isNotEmpty())
                                        Try adjusting your filters or <a href="{{ route('vendor.bookings.index') }}">reset them</a>.
                                    @else
                                        New customer orders will appear here.
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($orders->hasPages())
        <div class="vp-card-pad">{{ $orders->links('vendor.pagination.default') }}</div>
    @endif
</div>
@endsection

@extends('web.layouts.profile')

@section('title', 'Booking History')
@section('page_title', 'Booking History')
@section('page_subtitle', 'View and manage your past and upcoming dress rentals.')

@section('content')
@php $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80'; @endphp

<div class="jbw-card">
    <div class="jbw-page-head paddingtop">
        <h5 class="jbw-page-title fontsize">Booking History</h5>
    </div>
    <div class="jbw-booking-tabs">
        <a href="{{ route('web.bookings.index') }}" @class(['jbw-booking-tab', 'is-active' => ! request('tab')])>All bookings</a>
        <a href="{{ route('web.bookings.index', ['tab' => 'fashion_designer']) }}" @class(['jbw-booking-tab', 'is-active' => request('tab') === 'fashion_designer'])>Fashion designer</a>
        <a href="{{ route('web.bookings.index', ['tab' => 'rental_dress']) }}" @class(['jbw-booking-tab', 'is-active' => request('tab') === 'rental_dress'])>Rental dresses</a>
        <a href="{{ route('web.bookings.index', ['tab' => 'rental_jewellery']) }}" @class(['jbw-booking-tab', 'is-active' => request('tab') === 'rental_jewellery'])>Rental jewellery</a>
    </div>

    <div class="jbw-booking-list">
        @forelse ($orders as $entry)
            @if ($entry['kind'] === 'checkout')
                @php
                    $checkout = $entry['checkout'];
                    $subOrders = $checkout->subOrders;
                    $firstSub = $subOrders->first();
                    $vendorCount = $subOrders->count();
                    $itemCount = $subOrders->sum(function ($sub) {
                        $lines = $sub->orderItems;
                        if ($lines && $lines->isNotEmpty()) {
                            return (int) $lines->sum('quantity');
                        }

                        return max(1, (int) ($sub->quantity ?? 1));
                    });
                    $isMultiVendor = $vendorCount > 1;
                    $statusClass = match ($checkout->status) {
                        'new', 'pending_acceptance' => 'new',
                        'processing', 'partially_delivered' => 'in_progress',
                        'completed' => 'delivered',
                        'cancelled', 'refunded', 'partially_cancelled' => 'cancelled',
                        default => 'default',
                    };
                    $vendorNames = $subOrders->map(fn ($s) => $s->vendor?->brand_name)->filter()->unique()->values();
                    $title = $isMultiVendor
                        ? 'Multi-vendor order'
                        : ($firstSub?->itemDisplayName() ?? 'Order');
                    $imageUrl = $firstSub?->itemImageUrl();
                @endphp
                <article class="jbw-booking-row">
                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $title }}" loading="lazy">
                    @else
                        <img src="{{ $fallbackImg }}" alt="" loading="lazy">
                    @endif
                    <div class="jbw-booking-row-body">
                        <p class="jbw-booking-row-title">
                            {{ $title }}
                            @if ($isMultiVendor || $itemCount > 1)
                                <span class="jbw-booking-row-meta-inline">
                                    ({{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }}{{ $isMultiVendor ? ' · '.$vendorCount.' vendors' : '' }})
                                </span>
                            @endif
                        </p>
                        <p class="jbw-booking-row-meta">
                            @if ($isMultiVendor)
                                {{ $vendorNames->take(2)->implode(', ') }}{{ $vendorNames->count() > 2 ? ' +'.($vendorNames->count() - 2).' more' : '' }}
                            @else
                                {{ $firstSub?->vendor?->brand_name ?? 'Designer' }}
                                @if ($firstSub?->category)
                                    · {{ $firstSub->category->name }}
                                @endif
                            @endif
                            @if ($checkout->rental_start_date)
                                · {{ $checkout->rental_start_date->format('d M') }} – {{ $checkout->rental_end_date?->format('d M, Y') }}
                            @endif
                        </p>
                        <p class="jbw-booking-row-price">₹{{ number_format($checkout->grand_total, 0) }}</p>
                        <p class="jbw-booking-row-id">Order #{{ $checkout->order_number }}</p>
                    </div>
                    <div class="jbw-booking-row-aside">
                        <span class="jbw-status jbw-status--{{ $statusClass }}">{{ $checkout->statusLabel() }}</span>
                        <a href="{{ route('web.bookings.checkout.show', $checkout) }}" class="viewdetails">View Details</a>
                    </div>
                </article>
            @else
                @php
                    $order = $entry['order'];
                    $statusClass = match ($order->status) {
                        'new', 'pending_acceptance' => 'new',
                        'in_progress' => 'in_progress',
                        'delivered' => 'delivered',
                        'cancelled', 'refunded' => 'cancelled',
                        default => 'default',
                    };
                @endphp
                <article class="jbw-booking-row">
                    @if ($order->itemImageUrl())
                        <img src="{{ $order->itemImageUrl() }}" alt="{{ $order->itemDisplayName() }}" loading="lazy">
                    @else
                        <img src="{{ $fallbackImg }}" alt="" loading="lazy">
                    @endif
                    <div class="jbw-booking-row-body">
                        <p class="jbw-booking-row-title">{{ $order->itemDisplayName() }}</p>
                        <p class="jbw-booking-row-meta">
                            {{ $order->vendor?->brand_name ?? 'Designer' }}
                            @if ($order->category)
                                · {{ $order->category->name }}
                            @endif
                            @if ($order->isRental() && $order->rental_start_date)
                                · {{ $order->rental_start_date->format('d M') }} – {{ $order->rental_end_date?->format('d M, Y') }}
                            @endif
                        </p>
                        <p class="jbw-booking-row-price">₹{{ number_format($order->grandTotal(), 0) }}</p>
                        <p class="jbw-booking-row-id">Order #{{ $order->order_number }}</p>
                    </div>
                    <div class="jbw-booking-row-aside">
                        <span class="jbw-status jbw-status--{{ $statusClass }}">{{ $order->statusLabel() }}</span>
                        <a href="{{ route('web.bookings.show', $order) }}" class="viewdetails">View Details</a>
                    </div>
                </article>
            @endif
        @empty
            <p class="jbw-booking-list-empty">No bookings yet. <a href="{{ route('web.catalog.index') }}">Browse outfits</a></p>
        @endforelse

        @if ($orders->hasPages())
            <div class="jbw-booking-list-pagination">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection

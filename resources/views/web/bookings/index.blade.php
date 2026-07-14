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

    <div class="jbw-card">
        @forelse ($orders as $entry)
            @if ($entry['kind'] === 'checkout')
                @php
                    $checkout = $entry['checkout'];
                    $firstSub = $checkout->subOrders->first();
                    $statusClass = match ($checkout->status) {
                        'new', 'pending_acceptance' => 'new',
                        'processing', 'partially_delivered' => 'in_progress',
                        'completed' => 'delivered',
                        'cancelled', 'refunded', 'partially_cancelled' => 'cancelled',
                        default => 'default',
                    };
                    $vendorNames = $checkout->subOrders->map(fn ($s) => $s->vendor?->brand_name)->filter()->unique()->take(2)->implode(', ');
                @endphp
                <div class="jbw-booking-row" style="border-bottom: none; padding:0rem;">
                    @if ($firstSub?->itemImageUrl())
                        <img src="{{ $firstSub->itemImageUrl() }}" alt="">
                    @else
                        <div style="width:5rem;height:5rem;border-radius:0.75rem;background:#f1f5f9"></div>
                    @endif
                    <div>
                        <p style="font-weight:700;margin:0">
                            Multi-vendor order
                            <span style="font-size:0.75rem;font-weight:600;color:var(--jbw-muted)">({{ $checkout->sub_orders_count }} items)</span>
                        </p>
                        <p style="font-size:0.8125rem;color:var(--jbw-muted);margin:0.25rem 0">
                            {{ $vendorNames }}
                            @if ($checkout->rental_start_date)
                                · {{ $checkout->rental_start_date->format('d M') }} – {{ $checkout->rental_end_date?->format('d M, Y') }}
                            @endif
                        </p>
                        <p style="font-weight:800;color:var(--jbw-primary);margin:0">₹{{ number_format($checkout->grand_total, 0) }}</p>
                    </div>
                    <div style="text-align:right">
                        <span class="jbw-status jbw-status--{{ $statusClass }}">{{ $checkout->statusLabel() }}</span>
                        <p style="font-size:0.75rem;color:var(--jbw-muted);margin:0.5rem 0 0">#{{ $checkout->order_number }}</p>
                        <a href="{{ route('web.bookings.checkout.show', $checkout) }}" class="viewdetails">View Details</a>
                    </div>
                </div>
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
                <div class="jbw-booking-row" style="border-bottom: none; padding:0rem;">
                    @if ($order->itemImageUrl())
                        <img src="{{ $order->itemImageUrl() }}" alt="">
                    @else
                        <div style="width:5rem;height:5rem;border-radius:0.75rem;background:#f1f5f9"></div>
                    @endif
                    <div>
                        <p style="font-weight:700;margin:0">{{ $order->itemDisplayName() }}</p>
                        <p style="font-size:0.8125rem;color:var(--jbw-muted);margin:0.25rem 0">
                            {{ $order->vendor?->brand_name ?? 'Designer' }}
                            @if ($order->category)
                                · {{ $order->category->name }}
                            @endif
                            @if ($order->isRental() && $order->rental_start_date)
                                · {{ $order->rental_start_date->format('d M') }} – {{ $order->rental_end_date?->format('d M, Y') }}
                            @endif
                        </p>
                        <p style="font-weight:800;color:var(--jbw-primary);margin:0">₹{{ number_format($order->grandTotal(), 0) }}</p>
                    </div>
                    <div style="text-align:right">
                        <span class="jbw-status jbw-status--{{ $statusClass }}">{{ $order->statusLabel() }}</span>
                        <p style="font-size:0.75rem;color:var(--jbw-muted);margin:0.5rem 0 0">#{{ $order->order_number }}</p>
                        <a href="{{ route('web.bookings.show', $order) }}" class="viewdetails">View Details</a>
                    </div>
                </div>
            @endif
        @empty
            <p style="text-align:center;color:var(--jbw-muted);padding:2rem 0">No bookings yet. <a href="{{ route('web.catalog.index') }}">Browse outfits</a></p>
        @endforelse

        @if ($orders->hasPages())
            <div style="margin-top:1rem">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection

@extends('vendor.layouts.app')

@section('title', 'Dashboard')

@section('content')
@if ($vendor->status === 'pending')
<div class="vp-pending-banner">Your account is pending admin approval. You can explore the panel, but some actions may be limited until approved.</div>
@endif

@if ($promoBanner)
@include('vendor.partials.promo-banner', ['banner' => $promoBanner])
@endif

<div class="vp-page-head">
    <div>
        <h1 class="vp-page-title">Welcome back, {{ $vendor->owner_name ?? $vendor->displayName() }}!</h1>
        <p class="vp-page-sub">Here's what's happening with your studio today.</p>
    </div>
    <a href="{{ route('vendor.bookings.index') }}" class="vp-btn vp-btn--outline">View all bookings</a>
</div>

<div class="vp-stat-grid">
    <div class="vp-stat">
        <div class="vp-stat-icon">@include('vendor.partials.nav-icon', ['icon' => 'bag'])</div>
        <div class="vp-stat-body">
            <div class="vp-stat-label">Total Orders</div>
            <div class="vp-stat-value">{{ $stats['total_orders_today'] }}</div>
            <div class="vp-stat-sub">Today · YTD {{ number_format($stats['total_orders_ytd']) }}</div>
        </div>
    </div>
    <div class="vp-stat">
        <div class="vp-stat-icon">@include('vendor.partials.nav-icon', ['icon' => 'check'])</div>
        <div class="vp-stat-body">
            <div class="vp-stat-label">Completed</div>
            <div class="vp-stat-value">{{ $stats['completed_today'] }}</div>
            <div class="vp-stat-sub">Today · YTD {{ number_format($stats['completed_ytd']) }}</div>
        </div>
    </div>
    <div class="vp-stat">
        <div class="vp-stat-icon">@include('vendor.partials.nav-icon', ['icon' => 'plus'])</div>
        <div class="vp-stat-body">
            <div class="vp-stat-label">New Orders</div>
            <div class="vp-stat-value">{{ $stats['new_today'] }}</div>
            <div class="vp-stat-sub">Awaiting action today</div>
        </div>
    </div>
    <div class="vp-stat">
        <div class="vp-stat-icon">@include('vendor.partials.nav-icon', ['icon' => 'refresh'])</div>
        <div class="vp-stat-body">
            <div class="vp-stat-label">In Progress</div>
            <div class="vp-stat-value">{{ $stats['in_progress_today'] }}</div>
            <div class="vp-stat-sub">Active orders today</div>
        </div>
    </div>
</div>

<div class="vp-earnings">
    <div>
        <h3>WALLET BALANCES</h3>
        <div class="vp-earnings-grid">
            <div>
                <div style="font-size:.75rem;opacity:.85;font-weight:600;">DIGITAL WALLET</div>
                <div class="vp-earnings-val">₹{{ number_format($vendor->digital_wallet_balance, 0) }}</div>
                <div style="font-size:.72rem;opacity:.8;margin-top:.25rem;">15-day hold on new payments</div>
            </div>
            <div>
                <div style="font-size:.75rem;opacity:.85;font-weight:600;">ACTUAL WALLET</div>
                <div class="vp-earnings-val">₹{{ number_format($vendor->wallet_balance, 0) }}</div>
                <div style="font-size:.72rem;opacity:.8;margin-top:.25rem;">Available for withdrawal</div>
            </div>
            <div>
                <div style="font-size:.75rem;opacity:.85;font-weight:600;">THIS MONTH</div>
                <div class="vp-earnings-val" style="font-size:1.35rem;">₹{{ number_format($stats['earnings_month'], 0) }}</div>
            </div>
        </div>
    </div>
    <div class="vp-earnings-actions">
        <a href="{{ route('vendor.payments.index') }}" class="vp-btn vp-btn--outline" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);color:#fff;">View Payments</a>
    </div>
</div>

<div class="vp-card marginbottom">
    <div class="vp-card-head bordernone">
        <h3>Delivery Schedule — {{ $scheduleDate->format('M d, Y') }}</h3>
    </div>



    <div class="vp-date-slider ">
        <button class="vp-slider-arrow">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <div class="vp-slider-days">
            <div class="vp-day-item">
                <span class="vp-day-name">Mon</span>
                <span class="vp-day-num">10</span>
            </div>
            <div class="vp-day-item">
                <span class="vp-day-name">Tue</span>
                <span class="vp-day-num">11</span>
            </div>

            <div class="vp-day-item ">
                <span class="vp-day-name">Wed</span>
                <span class="vp-day-num">12</span>
            </div>

            <div class="vp-day-item active">
                <span class="vp-day-name">Thu</span>
                <span class="vp-day-num">13</span>
            </div>
            <div class="vp-day-item">
                <span class="vp-day-name">Fri</span>
                <span class="vp-day-num">14</span>
            </div>
            <div class="vp-day-item">
                <span class="vp-day-name">Sat</span>
                <span class="vp-day-num">15</span>
            </div>
            <div class="vp-day-item">
                <span class="vp-day-name">Sun</span>
                <span class="vp-day-num">16</span>
            </div>
        </div>

        <button class="vp-slider-arrow">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
    <div class="vp-card-pad ">
        @forelse ($schedule as $order)
        <div class="vp-schedule-item">
            <div class="vp-schedule-icon">@include('vendor.partials.nav-icon', ['icon' => 'bookings'])</div>
            <div>
                <strong>{{ $order->itemDisplayName() }}</strong>
                <div style="font-size:.8rem;color:var(--vp-muted);margin-top:.2rem;">
                    #{{ $order->order_number }} · {{ $order->statusLabel() }}
                </div>
            </div>
        </div>
        @empty
        <p class="vp-empty" style="padding:1.5rem 0;">No deliveries scheduled for this date.</p>
        @endforelse
    </div>
</div>

<!-- <div class="vp-grid-2"> -->
<!-- <div class="vp-card">
        <div class="vp-card-head">
            <h3>Delivery Schedule — {{ $scheduleDate->format('M d, Y') }}</h3>
        </div>
        <div class="vp-card-pad">
            @forelse ($schedule as $order)
                <div class="vp-schedule-item">
                    <div class="vp-schedule-icon">@include('vendor.partials.nav-icon', ['icon' => 'bookings'])</div>
                    <div>
                        <strong>{{ $order->itemDisplayName() }}</strong>
                        <div style="font-size:.8rem;color:var(--vp-muted);margin-top:.2rem;">
                            #{{ $order->order_number }} · {{ $order->statusLabel() }}
                        </div>
                    </div>
                </div>
            @empty
                <p class="vp-empty" style="padding:1.5rem 0;">No deliveries scheduled for this date.</p>
            @endforelse
        </div>
    </div> -->

<div class="vp-card">
    <!-- <div class="vp-card-head">
            <h3>Bookings</h3>
            <a href="{{ route('vendor.bookings.index') }}" class="vp-btn vp-btn--outline vp-btn--sm">View All</a>
        </div> -->
    <div class="vp-bookings-header">
        <div class="vp-bookings-top-row ">

            <h3 class="vp-bookings-title">Bookings</h3>

            <div class="vp-bookings-actions" style="flex-wrap: wrap;">
                <div class="vp-search-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="vp-input-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" placeholder="Search bookings..." class="vp-input-search">
                </div>

                <button class="vp-btn-filter">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="vp-input-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>from</span>
                </button>

                <button class="vp-btn-filter">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="vp-input-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>to</span>
                </button>

                <button class="vp-btn-export">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="vp-input-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export All</span>
                </button>
            </div>
        </div>

        <div class="vp-bookings-tabs bordernones" style="flex-wrap: wrap;">
            <a href="#" class="vp-tab-item active">All</a>
            <a href="#" class="vp-tab-item">New</a>
            <a href="#" class="vp-tab-item">Accepted</a>
            <a href="#" class="vp-tab-item">In transit</a>
            <a href="#" class="vp-tab-item">Completed</a>
        </div>
    </div>
    <div class="vp-card-pad paddingvp">
       <div class="vp-card">

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
                    @forelse ($recentBookings as $order)
                    {{-- Defensive Check: Skip or handle safely if $order isn't a valid object --}}
                    @if(is_object($order))
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
                    @else
                    <tr>
                        <td colspan="7" class="vp-empty-row" style="color: #e53e3e; font-weight: 500;">
                            ⚠️ Unable to render booking data row.
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="7" class="vp-empty-row">No bookings yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <!-- <div class="vp-card-pad paddingvp">
            @forelse ($recentBookings as $order)
                <div style="padding:.75rem 0;border-bottom:1px solid var(--vp-border);display:flex;justify-content:space-between;align-items:center;gap:.75rem;">
                    <div>
                        <strong style="font-size:.9rem;">{{ $order->itemDisplayName() }}</strong>
                        <div style="font-size:.78rem;color:var(--vp-muted);">{{ $order->customer?->name }}</div>
                    </div>
                    <span class="vp-badge vp-badge--{{ in_array($order->status,['delivered']) ? 'done' : (in_array($order->status,['new','pending_acceptance']) ? 'new' : 'accepted') }}">{{ $order->statusLabel() }}</span>
                </div>
            @empty
                <p class="vp-empty" style="padding:1.5rem 0;">No bookings yet.</p>
            @endforelse
        </div> -->
</div>
<!-- </div> -->
@endsection

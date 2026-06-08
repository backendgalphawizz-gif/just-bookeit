@extends('vendor.layouts.app')

@section('title', 'Dashboard')

@section('content')
@if ($vendor->status === 'pending')
    <div class="vp-pending-banner">Your account is pending admin approval. You can explore the panel, but some actions may be limited until approved.</div>
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

<div class="vp-grid-2">
    <div class="vp-card">
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
    </div>

    <div class="vp-card">
        <div class="vp-card-head">
            <h3>Recent Bookings</h3>
            <a href="{{ route('vendor.bookings.index') }}" class="vp-btn vp-btn--outline vp-btn--sm">View All</a>
        </div>
        <div class="vp-card-pad">
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
        </div>
    </div>
</div>
@endsection

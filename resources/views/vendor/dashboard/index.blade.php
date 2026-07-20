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
    <a href="{{ route('vendor.portfolio.index') }}" class="vp-btn vp-btn--outline vp-btn--edit-portfolio">
        <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M4 20h4.586a1 1 0 00.707-.293l9.414-9.414a2 2 0 000-2.828l-2.172-2.172a2 2 0 00-2.828 0L4.293 14.707A1 1 0 004 15.414V20z"/>
        </svg>
        Edit Portfolio
    </a>
</div>

<div class="vp-stat-grid">
    <div class="vp-stat vp-stat--figma">
        <div class="vp-stat-icon">@include('vendor.partials.nav-icon', ['icon' => 'bag'])</div>
        <div class="vp-stat-label">Total Orders</div>
        <div class="vp-stat-metrics">
            <div class="vp-stat-metric">
                <span>Today</span>
                <strong>{{ number_format($stats['total_orders_today']) }}</strong>
            </div>
            <div class="vp-stat-metric">
                <span>YTD</span>
                <strong>{{ number_format($stats['total_orders_ytd']) }}</strong>
            </div>
        </div>
    </div>
    <div class="vp-stat vp-stat--figma">
        <div class="vp-stat-icon">@include('vendor.partials.nav-icon', ['icon' => 'check'])</div>
        <div class="vp-stat-label">Completed</div>
        <div class="vp-stat-metrics">
            <div class="vp-stat-metric">
                <span>Today</span>
                <strong>{{ number_format($stats['completed_today']) }}</strong>
            </div>
            <div class="vp-stat-metric">
                <span>YTD</span>
                <strong>{{ number_format($stats['completed_ytd']) }}</strong>
            </div>
        </div>
    </div>
    <div class="vp-stat vp-stat--figma">
        <div class="vp-stat-icon">@include('vendor.partials.nav-icon', ['icon' => 'plus'])</div>
        <div class="vp-stat-label">New Orders</div>
        <div class="vp-stat-metrics">
            <div class="vp-stat-metric">
                <span>Today</span>
                <strong>{{ number_format($stats['new_today']) }}</strong>
            </div>
        </div>
    </div>
    <div class="vp-stat vp-stat--figma">
        <div class="vp-stat-icon">@include('vendor.partials.nav-icon', ['icon' => 'refresh'])</div>
        <div class="vp-stat-label">Inprogress</div>
        <div class="vp-stat-metrics">
            <div class="vp-stat-metric">
                <span>Today</span>
                <strong>{{ number_format($stats['in_progress_today']) }}</strong>
            </div>
        </div>
    </div>
</div>

<div class="vp-earnings vp-earnings--figma">
    <div class="vp-earnings-head">
        <h3>Total Earnings</h3>
        <div class="vp-earnings-tools">
            <form method="GET" action="{{ route('vendor.dashboard') }}" class="vp-earnings-month-form">
                @if (request('date'))
                    <input type="hidden" name="date" value="{{ request('date') }}">
                @endif
                <label class="vp-sr-only" for="earnings_month">Month</label>
                <select id="earnings_month" name="earnings_month" class="vp-earnings-month" onchange="this.form.submit()">
                    @for ($i = 0; $i < 12; $i++)
                        @php $monthOption = now()->startOfMonth()->subMonths($i); @endphp
                        <option value="{{ $monthOption->format('Y-m') }}" @selected($earningsMonth->format('Y-m') === $monthOption->format('Y-m'))>
                            {{ $monthOption->format('F Y') }}
                        </option>
                    @endfor
                </select>
            </form>
            <a href="{{ route('vendor.payments.index') }}" class="vp-btn vp-btn--earnings-download">
                <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                </svg>
                Download
            </a>
        </div>
    </div>
    <div class="vp-earnings-split">
        <div class="vp-earnings-col">
            <div class="vp-earnings-col-label">This Month</div>
            <div class="vp-earnings-val">₹ {{ number_format($stats['earnings_month'], 0) }}</div>
        </div>
        <div class="vp-earnings-divider" aria-hidden="true"></div>
        <div class="vp-earnings-col">
            <div class="vp-earnings-col-label">YTD Amount</div>
            <div class="vp-earnings-val">₹ {{ number_format($stats['earnings_ytd'], 0) }}</div>
        </div>
    </div>
</div>

<div class="vp-card vp-schedule-card">
    <div class="vp-schedule-inner">
        <h3 class="vp-schedule-title">Delivery Schedule</h3>

        <div class="vp-week-strip">
            <a
                href="{{ route('vendor.dashboard', array_filter(['date' => $scheduleDate->copy()->subWeek()->toDateString(), 'earnings_month' => request('earnings_month')])) }}"
                class="vp-week-nav"
                aria-label="Previous week"
            >
                <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="vp-week-days">
                @foreach ($weekDays as $day)
                    <a
                        href="{{ route('vendor.dashboard', array_filter(['date' => $day->toDateString(), 'earnings_month' => request('earnings_month')])) }}"
                        @class(['vp-week-day', 'is-active' => $day->isSameDay($scheduleDate)])
                    >
                        <span class="vp-week-day-name">{{ strtoupper($day->format('D')) }}</span>
                        <span class="vp-week-day-num">{{ $day->format('j') }}</span>
                    </a>
                @endforeach
            </div>
            <a
                href="{{ route('vendor.dashboard', array_filter(['date' => $scheduleDate->copy()->addWeek()->toDateString(), 'earnings_month' => request('earnings_month')])) }}"
                class="vp-week-nav"
                aria-label="Next week"
            >
                <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="vp-schedule-list">
            @forelse ($schedule as $order)
                @php
                    $isDelivered = $order->status === 'delivered';
                    $isOutForDelivery = in_array($order->status, ['in_progress', 're_intransit'], true)
                        || $order->driver_delivery_status === \App\Models\Order::DRIVER_STATUS_OUT_FOR_DELIVERY;

                    if ($isDelivered) {
                        $tag = 'Delivered';
                        $tagClass = 'vp-schedule-tag--delivered';
                        $icon = 'package';
                        $iconClass = 'vp-schedule-icon--delivered';
                    } elseif ($isOutForDelivery) {
                        $tag = 'Out for Delivery';
                        $tagClass = 'vp-schedule-tag--delivery';
                        $icon = 'package';
                        $iconClass = 'vp-schedule-icon--delivery';
                    } else {
                        $tag = 'Pending Pickup';
                        $tagClass = 'vp-schedule-tag--pickup';
                        $icon = 'truck';
                        $iconClass = 'vp-schedule-icon--pickup';
                    }

                    $when = $scheduleDate->isToday()
                        ? 'Today'
                        : ($scheduleDate->isYesterday() ? 'Yesterday' : $scheduleDate->format('M j'));
                    $timePart = $order->rental_start_date?->isSameDay($scheduleDate)
                        ? $order->rental_start_date->format('g:i A')
                        : ($order->updated_at?->format('g:i A') ?? '');
                    $timeLabel = trim($when.($timePart ? ', '.$timePart : ''));
                @endphp
                <div class="vp-schedule-row">
                    <div @class(['vp-schedule-icon', $iconClass])>
                        @include('vendor.partials.nav-icon', ['icon' => $icon])
                    </div>
                    <div class="vp-schedule-main">
                        <strong>{{ $order->itemDisplayName() }}</strong>
                        <div class="vp-schedule-meta">
                            Order #{{ $order->order_number }}
                            @if ($order->portfolio_item_id)
                                <span aria-hidden="true"> • </span>Item #{{ $order->portfolio_item_id }}
                            @endif
                        </div>
                    </div>
                    <div class="vp-schedule-aside">
                        <span @class(['vp-schedule-tag', $tagClass])>{{ strtoupper($tag) }}</span>
                        <div class="vp-schedule-time">{{ $timeLabel }}</div>
                    </div>
                </div>
            @empty
                <p class="vp-empty vp-empty--schedule">No deliveries scheduled for this date.</p>
            @endforelse
        </div>
    </div>
</div>

@include('vendor.bookings.partials.figma-list', [
    'orders' => $recentBookings,
    'listRoute' => route('vendor.bookings.index'),
    'showPagination' => false,
    'embedOnDashboard' => true,
])
@endsection

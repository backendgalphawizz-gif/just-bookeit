@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', $page_subtitle ?? 'Platform overview · Updated ' . now()->format('M d, Y h:i A'))

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-5">
        @foreach ($stat_cards as $card)
            <div class="jb-stat-card">
                <p class="jb-stat-label">{{ $card['label'] }}</p>
                <p class="jb-stat-value">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-3">
        <section class="jb-card xl:col-span-2">
            <div class="jb-card-header jb-card-header--stack">
                <div>
                    <p class="jb-card-header-title">Analytics</p>
                    <p class="text-sm text-slate-500">{{ $analytics_range_label }}</p>
                </div>
                <form method="GET" class="jb-analytics-filters">
                    <div class="jb-analytics-filters-grid">
                        @include('admin.partials.date-filter')
                        <div class="jb-filters-actions">
                            <div class="jb-filters-actions-btns">
                                <button type="submit" class="jb-btn jb-btn-primary jb-btn-sm">Apply</button>
                                <a href="{{ route('admin.dashboard') }}" class="jb-btn jb-btn-secondary jb-btn-sm">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="jb-card-body">
                <div class="grid gap-8 md:grid-cols-2">
                    @if ($chart_visibility['monthly_revenue'] ?? true)
                    <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                        <h3 class="text-sm font-bold text-slate-700">Monthly revenue</h3>
                        <canvas id="chartMonthlyRevenue" class="mt-4 max-h-52" height="180"></canvas>
                    </div>
                    @endif
                    @if ($chart_visibility['orders_trend'] ?? true)
                    <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                        <h3 class="text-sm font-bold text-slate-700">Orders trend</h3>
                        <canvas id="chartOrdersTrend" class="mt-4 max-h-52" height="180"></canvas>
                    </div>
                    @endif
                    @if ($chart_visibility['vendor_growth'] ?? true)
                    <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                        <h3 class="text-sm font-bold text-slate-700">Vendor growth</h3>
                        <canvas id="chartVendorGrowth" class="mt-4 max-h-52" height="180"></canvas>
                    </div>
                    @endif
                    @if ($chart_visibility['category_bookings'] ?? true)
                    <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                        <h3 class="text-sm font-bold text-slate-700">Category bookings</h3>
                        <canvas id="chartCategoryBookings" class="mt-4 max-h-52" height="180"></canvas>
                    </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="jb-card">
            <div class="jb-card-header">
                <p class="jb-card-header-title">Quick actions</p>
            </div>
            <div class="jb-card-body space-y-3">
                @forelse ($quick_actions as $action)
                    <a href="{{ $action['route'] }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-rose-200 hover:bg-rose-50/40">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $action['label'] }}</p>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $action['description'] }}</p>
                        </div>
                        @if ($action['count'] !== null && $action['count'] > 0)
                            <span class="jb-nav-badge">{{ $action['count'] }}</span>
                        @endif
                    </a>
                @empty
                    <p class="text-sm text-slate-500">No actions for your role.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="jb-card mt-6">
        <div class="jb-card-header">
            <p class="jb-card-header-title">Recent activity</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($recent_activities as $activity)
                @php
                    $dots = ['amber' => 'bg-amber-500', 'emerald' => 'bg-emerald-500', 'blue' => 'bg-blue-500', 'orange' => 'bg-orange-500', 'rose' => 'bg-rose-500'];
                @endphp
                <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:gap-4 sm:px-6">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $dots[$activity['tone']] ?? 'bg-slate-400' }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-900">{{ $activity['title'] }}</p>
                        <p class="truncate text-sm text-slate-500">{{ $activity['description'] }}</p>
                    </div>
                    <span class="shrink-0 text-xs font-medium text-slate-400 sm:text-right">{{ $activity['time_ago'] }}</span>
                </div>
            @empty
                <p class="jb-table-empty">No recent activity.</p>
            @endforelse
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        const charts = @json($charts);
        const chartDefaults = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
        };

        function buildChart(canvasId, labels, data, type = 'line') {
            const el = document.getElementById(canvasId);
            if (!el || !labels.length) return;
            new Chart(el, {
                type,
                data: {
                    labels,
                    datasets: [{
                        data,
                        borderColor: '#E95433',
                        backgroundColor: type === 'line' ? 'rgba(239, 66, 0, 0.12)' : ['#e67244','#e24826','#EF4200','#ff471d','#e25a3b','#e24724','#e43711','#cc2500'],
                        borderWidth: 2,
                        fill: type === 'line',
                        tension: 0.4,
                        borderRadius: type === 'bar' ? 6 : 0,
                    }],
                },
                options: {
                    ...chartDefaults,
                    scales: type === 'bar' ? { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } : { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } },
                },
            });
        }

        buildChart('chartMonthlyRevenue', charts.monthly_revenue.labels, charts.monthly_revenue.data);
        buildChart('chartOrdersTrend', charts.orders_trend.labels, charts.orders_trend.data);
        buildChart('chartVendorGrowth', charts.vendor_growth.labels, charts.vendor_growth.data);
        buildChart('chartCategoryBookings', charts.category_bookings.labels, charts.category_bookings.data, 'bar');
    </script>
@endpush

@extends('admin.layouts.app')
@section('title', 'Reports')
@section('page_title', 'Reports & analytics')
@section('page_subtitle', 'Platform performance and exportable data')
@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="jb-stat-card"><p class="jb-stat-label">Total orders</p><p class="jb-stat-value">{{ number_format($summary['total_orders'] ?? 0) }}</p></div>
        <div class="jb-stat-card"><p class="jb-stat-label">Revenue (paid)</p><p class="jb-stat-value">₹{{ number_format($summary['total_revenue'] ?? 0) }}</p></div>
        <div class="jb-stat-card"><p class="jb-stat-label">Active vendors</p><p class="jb-stat-value">{{ number_format($summary['active_vendors'] ?? 0) }}</p></div>
        <div class="jb-stat-card"><p class="jb-stat-label">Customers</p><p class="jb-stat-value">{{ number_format($summary['total_customers'] ?? 0) }}</p></div>
    </div>

    <div class="jb-card mt-6">
        <div class="jb-card-header">
            <p class="jb-card-header-title">Charts</p>
        </div>
        <div class="jb-card-body grid gap-6 md:grid-cols-2">
            <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                <h3 class="text-sm font-bold text-slate-700">Monthly revenue</h3>
                <canvas id="chartMonthlyRevenue" class="mt-4 max-h-52" height="180"></canvas>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                <h3 class="text-sm font-bold text-slate-700">Orders trend</h3>
                <canvas id="chartOrdersTrend" class="mt-4 max-h-52" height="180"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        @foreach (['overview' => 'Overview', 'orders' => 'Orders', 'vendors' => 'Vendors', 'refunds' => 'Refunds'] as $key => $label)
            <a href="{{ route('admin.reports.index', array_merge(request()->except('report'), ['report' => $key])) }}"
               class="rounded-lg px-4 py-2 text-sm font-semibold {{ ($report ?? 'overview') === $key ? 'bg-rose-500 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if (($report ?? 'overview') === 'orders')
        @push('filter_actions')
            <x-admin.export-dropdown module="orders" permission="reports" :params="['search', 'status', 'payment_status', 'vendor_id', 'from', 'to']" />
        @endpush
        <form method="GET" class="jb-filters mt-4">
            <input type="hidden" name="report" value="orders">
            <div class="jb-filters-grid">
                <div class="jb-filters-field">
                    <label class="jb-label">Order #</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="jb-input" placeholder="Search order number">
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label">Status</label>
                    <select name="status" class="jb-select">
                        <option value="">All</option>
                        @foreach (['new','pending_acceptance','accepted','in_progress','in_transit','delivered','cancelled','refunded'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label">Payment</label>
                    <select name="payment_status" class="jb-select">
                        <option value="">All</option>
                        @foreach (['pending','success','failed','refunded'] as $s)
                            <option value="{{ $s }}" @selected(request('payment_status') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label">Vendor</label>
                    <select name="vendor_id" class="jb-select">
                        <option value="">All</option>
                        @foreach ($filterVendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>{{ $vendor->brand_name }}</option>
                        @endforeach
                    </select>
                </div>
                @include('admin.partials.date-filter')
                @include('admin.partials.filters-end', ['resetUrl' => route('admin.reports.index', ['report' => 'orders'])])
            </div>
        </form>
        <div class="jb-card">
            <div class="jb-card-header">
                <p class="jb-card-header-title">{{ $orders->total() }} orders</p>
            </div>
            <div class="jb-table-wrap">
                <table class="jb-table">
                    <thead><tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-id">Order</th>
                        <th class="jb-col-name">Customer</th>
                        <th class="jb-col-name">Vendor</th>
                        <th class="jb-col-amount">Amount</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-col-date">Date</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                @include('admin.partials.table-index-cell', ['paginator' => $orders])
                                <td class="jb-col-id font-semibold">{{ $order->order_number }}</td>
                                <td class="jb-col-name">{{ $order->customer->name }}</td>
                                <td class="jb-col-name">{{ $order->vendor?->brand_name ?? '—' }}</td>
                                <td class="jb-col-amount">₹{{ number_format($order->amount, 2) }}</td>
                                <td class="jb-col-status"><span class="jb-badge bg-slate-100 text-slate-700">{{ $order->status }}</span></td>
                                <td class="jb-col-date text-sm text-slate-500">{{ $order->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="jb-table-empty">No orders for this filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="jb-card-pad">{{ $orders->links() }}</div>
            @endif
        </div>
    @elseif (($report ?? '') === 'vendors')
        @push('filter_actions')
            <x-admin.export-dropdown module="vendors" permission="reports" :params="['search', 'status', 'city', 'from', 'to']" />
        @endpush
        <form method="GET" class="jb-filters mt-4">
            <input type="hidden" name="report" value="vendors">
            <div class="jb-filters-grid">
                <div class="jb-filters-field jb-filters-field--wide">
                    <label class="jb-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="jb-input" placeholder="Brand, owner, email, code...">
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label">Status</label>
                    <select name="status" class="jb-select">
                        <option value="">All</option>
                        @foreach (['pending', 'active', 'suspended', 'rejected'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label">City</label>
                    <input type="text" name="city" value="{{ request('city') }}" class="jb-input" placeholder="City">
                </div>
                @include('admin.partials.date-filter')
                @include('admin.partials.filters-end', ['resetUrl' => route('admin.reports.index', ['report' => 'vendors'])])
            </div>
        </form>
        <div class="jb-card">
            <div class="jb-card-header">
                <p class="jb-card-header-title">{{ $vendors->total() }} vendors</p>
            </div>
            <div class="jb-table-wrap">
                <table class="jb-table">
                    <thead><tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-id">Code</th>
                        <th class="jb-col-name">Brand</th>
                        <th>City</th>
                        <th class="text-center">Orders</th>
                        <th class="jb-col-amount">Earnings</th>
                        <th class="jb-col-status">Status</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($vendors as $vendor)
                            <tr>
                                @include('admin.partials.table-index-cell', ['paginator' => $vendors])
                                <td class="jb-col-id">{{ $vendor->vendor_code }}</td>
                                <td class="jb-col-name font-semibold">{{ $vendor->brand_name }}</td>
                                <td>{{ $vendor->city }}</td>
                                <td class="text-center">{{ $vendor->orders_completed }}</td>
                                <td class="jb-col-amount">₹{{ number_format($vendor->earnings, 2) }}</td>
                                <td class="jb-col-status">{{ $vendor->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="jb-table-empty">No vendor data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($vendors->hasPages())
                <div class="jb-card-pad">{{ $vendors->links() }}</div>
            @endif
        </div>
    @elseif (($report ?? '') === 'refunds')
        @push('filter_actions')
            <x-admin.export-dropdown module="refunds" permission="reports" :params="['status', 'search', 'vendor_id', 'from', 'to']" />
        @endpush
        <form method="GET" class="jb-filters mt-4">
            <input type="hidden" name="report" value="refunds">
            <div class="jb-filters-grid">
                <div class="jb-filters-field">
                    <label class="jb-label">Status</label>
                    <select name="status" class="jb-select">
                        <option value="">All</option>
                        <option value="_open_" @selected(request('status') === '_open_')>Open only</option>
                        @foreach (['requested', 'under_review', 'approved', 'rejected', 'processed'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label">Customer</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="jb-input" placeholder="Search customer name">
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label">Vendor</label>
                    <select name="vendor_id" class="jb-select">
                        <option value="">All</option>
                        @foreach ($filterVendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>{{ $vendor->brand_name }}</option>
                        @endforeach
                    </select>
                </div>
                @include('admin.partials.date-filter')
                @include('admin.partials.filters-end', ['resetUrl' => route('admin.reports.index', ['report' => 'refunds'])])
            </div>
        </form>
        <div class="jb-card">
            <div class="jb-card-header">
                <p class="jb-card-header-title">{{ $refunds->total() }} refunds</p>
            </div>
            <div class="jb-table-wrap">
                <table class="jb-table">
                    <thead><tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-name">Customer</th>
                        <th class="jb-col-name">Vendor</th>
                        <th class="jb-col-id">Order</th>
                        <th class="jb-col-amount">Amount</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-col-date">Date</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($refunds as $refund)
                            <tr>
                                @include('admin.partials.table-index-cell', ['paginator' => $refunds])
                                <td class="jb-col-name">{{ $refund->customer->name }}</td>
                                <td class="jb-col-name">{{ $refund->order?->vendor?->brand_name ?? '—' }}</td>
                                <td class="jb-col-id">{{ $refund->order?->order_number ?? '—' }}</td>
                                <td class="jb-col-amount">₹{{ number_format($refund->amount, 2) }}</td>
                                <td class="jb-col-status">{{ $refund->status }}</td>
                                <td class="jb-col-date">{{ $refund->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="jb-table-empty">No refunds.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($refunds->hasPages())
                <div class="jb-card-pad">{{ $refunds->links() }}</div>
            @endif
        </div>
    @else
        <p class="mt-4 text-sm text-slate-600">Select Orders, Vendors, or Refunds above for detailed tables. Use Export CSV for downloads.</p>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        const charts = @json($charts);
        function buildChart(canvasId, labels, data) {
            const el = document.getElementById(canvasId);
            if (!el || !labels?.length) return;
            new Chart(el, {
                type: 'line',
                data: { labels, datasets: [{ data, borderColor: '#E95433', backgroundColor: 'rgba(239, 66, 0, 0.12)', fill: true, tension: 0.4 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
            });
        }
        buildChart('chartMonthlyRevenue', charts.monthly_revenue.labels, charts.monthly_revenue.data);
        buildChart('chartOrdersTrend', charts.orders_trend.labels, charts.orders_trend.data);
    </script>
@endpush

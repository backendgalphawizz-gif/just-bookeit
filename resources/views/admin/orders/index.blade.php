@extends('admin.layouts.app')

@section('title', 'Orders')
@section('page_title', 'Orders')
@section('page_subtitle', 'Booking and order lifecycle')

@section('content')
    @php
        $activeFilters = collect([
            'Order #' => request('search'),
            'Status' => request('status') ? \App\Models\Order::statusLabelFor(request('status')) : null,
            'Payment' => request('payment_status') ? ucfirst(request('payment_status')) : null,
            'Vendor' => $vendors->firstWhere('id', (int) request('vendor_id'))?->brand_name,
            'Category' => $categories->firstWhere('id', (int) request('category_id'))?->name,
            'From' => request('from'),
            'To' => request('to'),
        ])->filter(fn ($value) => filled($value));
    @endphp

    <form method="GET" class="jb-filters jb-orders-filters">
        <div class="jb-orders-filters__head">
            <div>
                <p class="jb-orders-filters__title">Filter orders</p>
                <p class="jb-orders-filters__hint">Search and narrow results by status, payment, vendor, category, or date.</p>
            </div>
            <div class="jb-orders-filters__toolbar">
                <x-admin.export-dropdown module="orders" :params="['search', 'status', 'payment_status', 'vendor_id', 'category_id', 'from', 'to']" />
            </div>
        </div>

        <div class="jb-orders-filters__body">
            <div class="jb-orders-filters__search">
                <label class="jb-label" for="orders-filter-search">Order #</label>
                <input
                    type="text"
                    id="orders-filter-search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="e.g. JB268680056"
                    class="jb-input"
                >
            </div>

            <div class="jb-orders-filters__grid">
                <div class="jb-filters-field">
                    <label class="jb-label" for="orders-filter-status">Status</label>
                    <select id="orders-filter-status" name="status" class="jb-select">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\Order::STATUSES as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                {{ \App\Models\Order::statusLabelFor($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label" for="orders-filter-payment">Payment</label>
                    <select id="orders-filter-payment" name="payment_status" class="jb-select">
                        <option value="">All payments</option>
                        @foreach (['pending', 'success', 'failed', 'refunded'] as $paymentStatus)
                            <option value="{{ $paymentStatus }}" @selected(request('payment_status') === $paymentStatus)>
                                {{ ucfirst($paymentStatus) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label" for="orders-filter-vendor">Vendor</label>
                    <select id="orders-filter-vendor" name="vendor_id" class="jb-select">
                        <option value="">All vendors</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>
                                {{ $vendor->brand_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label" for="orders-filter-category">Category</label>
                    <select id="orders-filter-category" name="category_id" class="jb-select">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="jb-orders-filters__footer">
                <div class="jb-orders-filters__dates">
                    @include('admin.partials.date-filter')
                </div>
                <div class="jb-filters-actions">
                    <label class="jb-label jb-label--spacer" aria-hidden="true">&nbsp;</label>
                    <div class="jb-filters-actions-btns">
                        <x-admin.button variant="primary" type="submit" size="sm">Apply filters</x-admin.button>
                        <x-admin.button variant="secondary" :href="route('admin.orders.index')" size="sm">Reset</x-admin.button>
                    </div>
                </div>
            </div>
        </div>

        @if ($activeFilters->isNotEmpty())
            <div class="jb-orders-filters__active">
                <span class="jb-orders-filters__active-label">Active:</span>
                @foreach ($activeFilters as $label => $value)
                    <span class="jb-orders-filter-chip">{{ $label }}: {{ $value }}</span>
                @endforeach
            </div>
        @endif
    </form>

    <div class="jb-card jb-orders-card">
        <div class="jb-card-header jb-orders-card__header">
            <div>
                <p class="jb-card-header-title">{{ number_format($orders->total()) }} orders</p>
                <p class="jb-orders-card__subtitle">
                    @if ($activeFilters->isNotEmpty())
                        Showing filtered results
                    @else
                        All bookings across vendors and categories
                    @endif
                </p>
            </div>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table jb-orders-table">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-id">Order</th>
                        <th class="jb-col-name">Customer</th>
                        <th class="jb-col-name">Vendor</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th class="jb-col-amount">Amount</th>
                        <th class="jb-col-status">Payment</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-col-date">Date</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $orders])
                            <td class="jb-col-id">
                                <span class="jb-orders-id">{{ $order->order_number }}</span>
                            </td>
                            <td class="jb-col-name">
                                <span class="jb-orders-name">{{ $order->customer->name }}</span>
                            </td>
                            <td class="jb-col-name">
                                <span class="jb-orders-name">{{ $order->vendor?->brand_name ?? '—' }}</span>
                            </td>
                            <td>
                                <span class="jb-orders-category">{{ $order->category->name }}</span>
                            </td>
                            <td>
                                <span class="jb-orders-type">{{ $order->order_type === 'rental' ? 'Rental' : 'Sale' }}</span>
                            </td>
                            <td class="jb-col-amount">
                                <span class="jb-orders-amount">₹{{ number_format($order->amount, 2) }}</span>
                            </td>
                            <td class="jb-col-status">
                                @include('admin.components.status-badge', ['status' => $order->payment_status, 'label' => ucfirst($order->payment_status)])
                            </td>
                            <td class="jb-col-status">
                                @include('admin.components.status-badge', ['status' => $order->status])
                            </td>
                            <td class="jb-col-date">
                                <span class="jb-orders-date">{{ $order->created_at->format('M d, Y') }}</span>
                            </td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    <x-admin.action-btn variant="view" :href="route('admin.orders.show', $order)" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="jb-table-empty">No orders match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            {{ $orders->links() }}
        @endif
    </div>
@endsection

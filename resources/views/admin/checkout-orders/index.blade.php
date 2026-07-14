@extends('admin.layouts.app')

@section('title', 'Checkout orders')
@section('page_title', 'Checkout orders')
@section('page_subtitle', 'Multi-vendor parent orders with sub-orders')

@section('content')
    <form method="GET" class="jb-filters jb-orders-filters">
        <div class="jb-orders-filters__head">
            <div>
                <p class="jb-orders-filters__title">Filter checkout orders</p>
                <p class="jb-orders-filters__hint">Parent order numbers, status, payment, or date range.</p>
            </div>
        </div>
        <div class="jb-orders-filters__body">
            <div class="jb-orders-filters__search">
                <label class="jb-label" for="checkout-filter-search">Checkout #</label>
                <input type="text" id="checkout-filter-search" name="search" value="{{ request('search') }}" placeholder="e.g. JB260600100" class="jb-input">
            </div>
            <div class="jb-orders-filters__grid">
                <div class="jb-filters-field">
                    <label class="jb-label" for="checkout-filter-status">Status</label>
                    <select id="checkout-filter-status" name="status" class="jb-select">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\CheckoutOrder::STATUSES as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Models\CheckoutOrder::statusLabelFor($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label" for="checkout-filter-payment">Payment</label>
                    <select id="checkout-filter-payment" name="payment_status" class="jb-select">
                        <option value="">All payments</option>
                        @foreach (\App\Models\CheckoutOrder::PAYMENT_STATUSES as $paymentStatus)
                            <option value="{{ $paymentStatus }}" @selected(request('payment_status') === $paymentStatus)>{{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label" for="checkout-filter-from">From</label>
                    <input type="date" id="checkout-filter-from" name="from" value="{{ request('from') }}" class="jb-input">
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label" for="checkout-filter-to">To</label>
                    <input type="date" id="checkout-filter-to" name="to" value="{{ request('to') }}" class="jb-input">
                </div>
            </div>
            <div class="jb-filters-actions">
                <x-admin.button type="submit">Apply filters</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.checkout-orders.index')">Reset</x-admin.button>
            </div>
        </div>
    </form>

    <div class="jb-card jb-orders-card">
        <div class="jb-card-header jb-orders-card__header">
            <div>
                <p class="jb-card-header-title">{{ number_format($checkouts->total()) }} checkout orders</p>
                <p class="jb-orders-card__subtitle">One customer payment, multiple vendor sub-orders</p>
            </div>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table jb-orders-table">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-id">Checkout #</th>
                        <th class="jb-col-name">Customer</th>
                        <th>Vendors</th>
                        <th class="jb-col-amount">Grand total</th>
                        <th class="jb-col-amount">Refunded</th>
                        <th class="jb-col-status">Payment</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-col-date">Date</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($checkouts as $checkout)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $checkouts])
                            <td class="jb-col-id">
                                <span class="jb-orders-id">{{ $checkout->order_number }}</span>
                            </td>
                            <td class="jb-col-name">
                                <a href="{{ route('admin.customers.show', $checkout->customer) }}" class="jb-orders-name">{{ $checkout->customer->name }}</a>
                            </td>
                            <td>
                                <span class="jb-orders-category">{{ $checkout->sub_orders_count }} vendor{{ $checkout->sub_orders_count === 1 ? '' : 's' }}</span>
                            </td>
                            <td class="jb-col-amount">
                                <span class="jb-orders-amount">₹{{ number_format($checkout->grand_total, 2) }}</span>
                            </td>
                            <td class="jb-col-amount">
                                <span class="jb-orders-amount">₹{{ number_format($checkout->amount_refunded, 2) }}</span>
                            </td>
                            <td class="jb-col-status">
                                @include('admin.components.status-badge', ['status' => $checkout->payment_status, 'label' => ucfirst(str_replace('_', ' ', $checkout->payment_status))])
                            </td>
                            <td class="jb-col-status">
                                @include('admin.components.status-badge', ['status' => $checkout->status, 'label' => $checkout->statusLabel()])
                            </td>
                            <td class="jb-col-date">
                                <span class="jb-orders-date">{{ $checkout->created_at->format('M d, Y') }}</span>
                            </td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    <x-admin.action-btn variant="view" :href="route('admin.checkout-orders.show', $checkout)" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="jb-table-empty">No checkout orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($checkouts->hasPages())
            <div class="jb-card-footer">{{ $checkouts->links() }}</div>
        @endif
    </div>
@endsection

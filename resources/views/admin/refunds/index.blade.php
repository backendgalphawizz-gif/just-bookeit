@extends('admin.layouts.app')
@section('title', 'Refunds')
@section('page_title', 'Refunds')
@section('page_subtitle', 'Refund requests and processing')
@section('content')
    @php
        $activeFilters = collect([
            'Customer' => request('customer'),
            'Order ID' => request('order_id'),
            'Vendor' => $vendors->firstWhere('id', (int) request('vendor_id'))?->brand_name,
            'Status' => match (true) {
                request('status') === '_open_' || request()->boolean('open_only') => 'Open only',
                filled(request('status')) => str_replace('_', ' ', ucfirst((string) request('status'))),
                default => null,
            },
            'From' => request('from'),
            'To' => request('to'),
        ])->filter(fn ($value) => filled($value));
    @endphp

    <form method="GET" class="jb-filters jb-orders-filters">
        <div class="jb-orders-filters__head">
            <div>
                <p class="jb-orders-filters__title">Filter refunds</p>
                <p class="jb-orders-filters__hint">Search by customer, order ID, vendor, status, or date range.</p>
            </div>
            <div class="jb-orders-filters__toolbar">
                <x-admin.export-dropdown module="refunds" :params="['status', 'customer', 'vendor_id', 'order_id', 'from', 'to']" />
                @if (auth('admin')->user()->hasPermission('refunds', 'create'))
                    <x-admin.button variant="primary" size="sm" :href="route('admin.refunds.create')">+ New Refund</x-admin.button>
                @endif
            </div>
        </div>

        <div class="jb-orders-filters__body">
            <div class="jb-orders-filters__grid">
                <div class="jb-filters-field">
                    <label class="jb-label" for="refunds-filter-customer">Customer</label>
                    <input
                        type="text"
                        id="refunds-filter-customer"
                        name="customer"
                        value="{{ request('customer') }}"
                        placeholder="Customer name"
                        class="jb-input"
                    >
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label" for="refunds-filter-order">Order ID</label>
                    <input
                        type="text"
                        id="refunds-filter-order"
                        name="order_id"
                        value="{{ request('order_id') }}"
                        placeholder="e.g. JB2686000264"
                        class="jb-input"
                    >
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label" for="refunds-filter-vendor">Vendor</label>
                    <select id="refunds-filter-vendor" name="vendor_id" class="jb-select">
                        <option value="">All vendors</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>
                                {{ $vendor->brand_name ?? $vendor->shop_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="jb-filters-field">
                    <label class="jb-label" for="refunds-filter-status">Status</label>
                    <select id="refunds-filter-status" name="status" class="jb-select">
                        <option value="">All statuses</option>
                        <option value="_open_" @selected(request('status') === '_open_' || request()->boolean('open_only'))>Open only</option>
                        @foreach (['requested', 'under_review', 'approved', 'rejected', 'processed'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                {{ str_replace('_', ' ', ucfirst($status)) }}
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
                        <x-admin.button variant="secondary" :href="route('admin.refunds.index')" size="sm">Reset</x-admin.button>
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
                <p class="jb-card-header-title">{{ number_format($refunds->total()) }} refunds</p>
                <p class="jb-orders-card__subtitle">
                    @if ($activeFilters->isNotEmpty())
                        Filtered results
                    @else
                        All refund requests
                    @endif
                </p>
            </div>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table jb-table--balanced">
                <thead><tr>
                    @include('admin.partials.table-index-header')
                    <th class="jb-col-id">Refund ID</th>
                    <th class="jb-col-name">Customer</th>
                    <th class="jb-col-name">Vendor</th>
                    <th class="jb-col-id">Order</th>
                    <th>Created Date</th>
                    <th class="jb-col-amount">Amount</th>
                    <th class="jb-col-status">Status</th>
                    <th class="jb-table-actions-col">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($refunds as $refund)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $refunds])
                            <td class="jb-col-id font-mono text-xs font-semibold text-slate-600">{{ $refund->id }}</td>
                            <td class="jb-col-name">{{ $refund->customer?->name ?? '—' }}</td>
                            <td class="jb-col-name">{{ $refund->order?->vendor?->brand_name ?? $refund->order?->vendor?->shop_name ?? '—' }}</td>
                            <td class="jb-col-id font-mono text-xs">{{ $refund->order?->order_number ?? '—' }}</td>
                            <td>{{ $refund->created_at ? $refund->created_at->format('M d, Y') : '—' }}</td>
                            <td class="jb-col-amount font-semibold">₹{{ number_format($refund->amount, 2) }}</td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $refund->status])</td>
                            <td class="jb-table-actions-col"><div class="jb-actions"><x-admin.action-btn variant="view" :href="route('admin.refunds.show', $refund)" /></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="jb-table-empty">No refunds found for the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($refunds->hasPages()) {{ $refunds->links() }} @endif
    </div>
@endsection

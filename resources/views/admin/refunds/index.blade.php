@extends('admin.layouts.app')
@section('title', 'Refunds')
@section('page_title', 'Refunds')
@section('page_subtitle', 'Refund requests and processing')
@section('content')
    @push('filter_actions')
        <x-admin.export-dropdown module="refunds" :params="['status', 'customer', 'vendor_id', 'order_id', 'from', 'to']" />
        @if (auth('admin')->user()->hasPermission('refunds', 'create'))
            <x-admin.button class="jb-btn-primary d-none" variant="primary" size="sm" :href="route('admin.refunds.create')">+ New Refund</x-admin.button>
        @endif
    @endpush
    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
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
            <div class="jb-filters-field jb-filters-field--wide">
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
                <label class="jb-label">Status</label>
                <select name="status" class="jb-select">
                    <option value="">All</option>
                    <option value="_open_" @selected(request('status') === '_open_' || request()->boolean('open_only'))>Open only</option>
                    @foreach (['requested', 'under_review', 'approved', 'rejected', 'processed'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
                    @endforeach
                </select>
            </div>
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.refunds.index')])
        </div>
    </form>
    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">
                {{ $refunds->total() }} refunds
                @if (request('customer'))
                    · Customer: {{ request('customer') }}
                @endif
                @if (request('order_id'))
                    · Order: {{ request('order_id') }}
                @endif
                @if (request('vendor_id'))
                    · {{ $vendors->firstWhere('id', (int) request('vendor_id'))?->brand_name }}
                @endif
            </p>
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
                        <tr><td colspan="9" class="jb-table-empty">No refunds.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($refunds->hasPages()) {{ $refunds->links() }} @endif
    </div>
@endsection

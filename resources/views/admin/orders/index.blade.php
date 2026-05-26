@extends('admin.layouts.app')
@section('title', 'Orders')
@section('page_title', 'Orders')
@section('page_subtitle', 'Booking and order lifecycle')
@section('content')
    @push('filter_actions')
        @if (auth('admin')->user()->hasPermission('orders', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.orders.create')">+ New Order</x-admin.button>
        @endif
    @endpush
    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field"><label class="jb-label">Order #</label><input type="text" name="search" value="{{ request('search') }}" class="jb-input"></div>
            <div class="jb-filters-field"><label class="jb-label">Status</label><select name="status" class="jb-select"><option value="">All</option>@foreach (['new','pending_acceptance','accepted','in_progress','in_transit','delivered','cancelled','refunded'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ str_replace('_', ' ', ucfirst($s)) }}</option>@endforeach</select></div>
            <div class="jb-filters-field"><label class="jb-label">Payment</label><select name="payment_status" class="jb-select"><option value="">All</option>@foreach (['pending','success','failed','refunded'] as $s)<option value="{{ $s }}" @selected(request('payment_status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
            <div class="jb-filters-field"><label class="jb-label">Vendor</label><select name="vendor_id" class="jb-select"><option value="">All</option>@foreach ($vendors as $v)<option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->brand_name }}</option>@endforeach</select></div>
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.orders.index')])
        </div>
    </form>
    <div class="jb-card">
        <div class="jb-card-header"><p class="jb-card-header-title">{{ $orders->total() }} orders</p></div>
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead><tr>
                    @include('admin.partials.table-index-header')
                    <th class="jb-col-id">Order</th>
                    <th class="jb-col-name">Customer</th>
                    <th class="jb-col-name">Vendor</th>
                    <th>Category</th>
                    <th class="jb-col-amount">Amount</th>
                    <th class="jb-col-status">Payment</th>
                    <th class="jb-col-status">Status</th>
                    <th class="jb-col-date">Date</th>
                    <th class="jb-table-actions-col">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $orders])
                            <td class="jb-col-id"><span class="font-mono text-xs font-semibold">{{ $order->order_number }}</span></td>
                            <td class="jb-col-name">{{ $order->customer->name }}</td>
                            <td class="jb-col-name">{{ $order->vendor?->brand_name ?? '—' }}</td>
                            <td>{{ $order->category->name }}</td>
                            <td class="jb-col-amount font-semibold">₹{{ number_format($order->amount, 2) }}</td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $order->payment_status, 'label' => ucfirst($order->payment_status)])</td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $order->status])</td>
                            <td class="jb-col-date text-slate-500">{{ $order->created_at->format('M d, Y') }}</td>
                            <td class="jb-table-actions-col"><div class="jb-actions"><x-admin.action-btn variant="view" :href="route('admin.orders.show', $order)" /></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="jb-table-empty">No orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages()) {{ $orders->links() }} @endif
    </div>
@endsection

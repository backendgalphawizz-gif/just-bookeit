@extends('admin.layouts.app')
@section('title', 'Payments')
@section('page_title', 'Payments')
@section('page_subtitle', 'Transaction tracking and reconciliation')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="jb-stat-card"><p class="jb-stat-label">Successful</p><p class="jb-stat-value text-emerald-700">₹{{ number_format($totals['success'], 0) }}</p></div>
        <div class="jb-stat-card"><p class="jb-stat-label">Pending</p><p class="jb-stat-value text-amber-700">₹{{ number_format($totals['pending'], 0) }}</p></div>
        <div class="jb-stat-card"><p class="jb-stat-label">Failed</p><p class="jb-stat-value text-rose-700">₹{{ number_format($totals['failed'], 0) }}</p></div>
    </div>

    @push('filter_actions')
        <x-admin.export-dropdown module="payments" :params="['search', 'payment_status', 'from', 'to']" />
    @endpush
    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide"><label class="jb-label">Order number</label><input type="text" name="search" value="{{ request('search') }}" class="jb-input"></div>
            <div class="jb-filters-field"><label class="jb-label">Payment status</label><select name="payment_status" class="jb-select"><option value="">All</option>@foreach (['pending','success','failed','refunded'] as $s)<option value="{{ $s }}" @selected(request('payment_status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.payments.index')])
        </div>
    </form>

    <div class="jb-card">
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead><tr>
                    @include('admin.partials.table-index-header')
                    <th class="jb-col-id">Order ID</th>
                    <th class="jb-col-name">Customer</th>
                    <th class="jb-col-name">Vendor</th>
                    <th class="jb-col-amount">Amount</th>
                    <th class="jb-col-date">Date</th>
                    <th class="jb-col-status">Status</th>
                    <th class="jb-table-actions-col">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $payments])
                            <td class="jb-col-id font-mono text-xs font-semibold">{{ $payment->order_number }}</td>
                            <td class="jb-col-name">{{ $payment->customer->name }}</td>
                            <td class="jb-col-name">{{ $payment->vendor?->brand_name ?? '—' }}</td>
                            <td class="jb-col-amount font-semibold">₹{{ number_format($payment->amount, 2) }}</td>
                            <td class="jb-col-date text-slate-500">{{ ($payment->paid_at ?? $payment->created_at)?->format('M d, Y - g:i A') }}</td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $payment->payment_status, 'label' => ucfirst($payment->payment_status)])</td>
                            <td class="jb-table-actions-col"><div class="jb-actions"><x-admin.action-btn variant="view" :href="route('admin.payments.show', $payment)" /></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="jb-table-empty">No payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages()) {{ $payments->links() }} @endif
    </div>
@endsection

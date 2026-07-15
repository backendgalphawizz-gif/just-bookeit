@extends('admin.layouts.app')
@section('title', 'Withdrawals')
@section('page_title', 'Vendor Withdrawals')
@section('page_subtitle', 'Review vendor actual-wallet withdrawal requests')
@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <div class="jb-stat-card"><p class="jb-stat-label">Pending requests</p><p class="jb-stat-value text-amber-700">₹{{ number_format($totals['pending'], 0) }}</p></div>
        <div class="jb-stat-card"><p class="jb-stat-label">Approved (all time)</p><p class="jb-stat-value text-emerald-700">₹{{ number_format($totals['approved'], 0) }}</p></div>
    </div>
    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Request code, vendor, reference" class="jb-input">
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">Status</label>
                <select name="status" class="jb-select">
                    <option value="">All</option>
                    <option value="_open_" @selected(request('status') === '_open_')>Pending only</option>
                    @foreach (['pending', 'approved', 'rejected'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">Vendor</label>
                <select name="vendor_id" class="jb-select">
                    <option value="">All</option>
                    @foreach ($vendors as $v)
                        <option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->brand_name }}</option>
                    @endforeach
                </select>
            </div>
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.withdrawals.index')])
        </div>
    </form>
    <div class="jb-card">
        <div class="jb-card-header"><p class="jb-card-header-title">{{ $withdrawals->total() }} withdrawal requests</p></div>
        <div class="jb-table-wrap">
            <table class="jb-table jb-table--balanced">
                <thead><tr>
                    @include('admin.partials.table-index-header')
                    <th class="jb-col-id">Request</th>
                    <th class="jb-col-name">Vendor</th>
                    <th class="jb-col-amount">Amount</th>
                    <th class="jb-col-status">Status</th>
                    <th class="jb-col-date">Requested</th>
                    <th class="jb-table-actions-col">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($withdrawals as $withdrawal)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $withdrawals])
                            <td class="jb-col-id font-mono text-xs font-semibold">{{ $withdrawal->request_code }}</td>
                            <td class="jb-col-name">{{ $withdrawal->vendor->brand_name }}</td>
                            <td class="jb-col-amount font-semibold">₹{{ number_format($withdrawal->amount, 2) }}</td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $withdrawal->status, 'label' => $withdrawal->statusLabel()])</td>
                            <td class="jb-col-date text-sm text-slate-500">{{ $withdrawal->created_at->format('M d, Y') }}</td>
                            <td class="jb-table-actions-col"><div class="jb-actions"><x-admin.action-btn variant="view" :href="route('admin.withdrawals.show', $withdrawal)" /></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="jb-table-empty">No withdrawal requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($withdrawals->hasPages()) {{ $withdrawals->links() }} @endif
    </div>
@endsection

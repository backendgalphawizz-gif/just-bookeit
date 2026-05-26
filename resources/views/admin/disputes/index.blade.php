@extends('admin.layouts.app')
@section('title', 'Disputes')
@section('page_title', 'Disputes')
@section('page_subtitle', 'Customer and vendor issue tracking')
@section('content')
    @push('filter_actions')
        @if (auth('admin')->user()->hasPermission('disputes', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.disputes.create')">+ New Dispute</x-admin.button>
        @endif
    @endpush
    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field">
                <label class="jb-label">Status</label>
                <select name="status" class="jb-select">
                    <option value="">All</option>
                    <option value="_open_" @selected(request('status') === '_open_' || request()->boolean('open_only'))>Open only</option>
                    @foreach (['raised', 'under_review', 'resolved', 'closed'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.disputes.index')])
        </div>
    </form>
    <div class="jb-card">
        <div class="jb-table-wrap">
            <table class="jb-table jb-table--balanced">
                <thead><tr>
                    @include('admin.partials.table-index-header')
                    <th class="jb-col-id">Dispute ID</th>
                    <th class="jb-col-id">Order</th>
                    <th class="jb-col-name">Subject</th>
                    <th class="jb-col-meta">Raised by</th>
                    <th class="jb-col-status">Status</th>
                    <th class="jb-table-actions-col">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($disputes as $dispute)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $disputes])
                            <td class="jb-col-id font-mono text-xs font-semibold text-slate-600">{{ $dispute->id }}</td>
                            <td class="jb-col-id font-mono text-xs">{{ $dispute->order->order_number }}</td>
                            <td class="jb-col-name font-medium">{{ $dispute->subject }}</td>
                            <td class="jb-col-meta capitalize">{{ $dispute->raised_by }}</td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $dispute->status])</td>
                            <td class="jb-table-actions-col"><div class="jb-actions"><x-admin.action-btn variant="view" :href="route('admin.disputes.show', $dispute)" /></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="jb-table-empty">No disputes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($disputes->hasPages()) {{ $disputes->links() }} @endif
    </div>
@endsection

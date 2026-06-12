@extends('admin.layouts.app')
@section('title', 'Disputes')
@section('page_title', 'Disputes')
@section('page_subtitle', 'Customer and vendor issues by service category')
@section('content')
    <div class="jb-tabs-row">
        <div class="jb-tabs-list">
            <a href="{{ route('admin.disputes.index', request()->except('category', 'page')) }}"
               class="jb-settings-tab {{ empty($categoryId) ? 'jb-settings-tab--active' : '' }}">
                All categories
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('admin.disputes.index', array_merge(request()->except('page'), ['category' => $category->id])) }}"
                   class="jb-settings-tab {{ (int) $categoryId === $category->id ? 'jb-settings-tab--active' : '' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </div>

    @push('filter_actions')
        <x-admin.export-dropdown module="disputes" :params="['category', 'status', 'from', 'to']" />
    @endpush
    <form method="GET" class="jb-filters">
        @if ($categoryId)
            <input type="hidden" name="category" value="{{ $categoryId }}">
        @endif
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
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.disputes.index', $categoryId ? ['category' => $categoryId] : [])])
        </div>
    </form>
    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">
                {{ $disputes->total() }} disputes
                @if ($categoryId)
                    · {{ $categories->firstWhere('id', $categoryId)?->name }}
                @endif
            </p>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table jb-table--balanced">
                <thead><tr>
                    @include('admin.partials.table-index-header')
                    <th class="jb-col-id">Dispute ID</th>
                    <th class="jb-col-name">Category</th>
                    <th class="jb-col-id">Order</th>
                    <th class="jb-col-name">Subject</th>
                    <th class="jb-col-name">User</th>
                    <th>Created Date</th>
                    <th class="jb-col-meta">Raised by</th>
                    <th class="jb-col-status">Status</th>
                    <th class="jb-table-actions-col">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($disputes as $dispute)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $disputes])
                            <td class="jb-col-id font-mono text-xs font-semibold text-slate-600">{{ $dispute->id }}</td>
                            <td class="jb-col-name">{{ $dispute->category?->name ?? $dispute->order?->category?->name ?? '—' }}</td>
                            <td class="jb-col-id font-mono text-xs">{{ $dispute->order->order_number }}</td>
                            <td class="jb-col-name font-medium">{{ $dispute->subject }}</td>
                            <td class="jb-col-name">{{ $dispute->raised_by === 'customer' ? ($dispute->order->customer?->name ?? '—') : ($dispute->order->vendor?->brand_name ?? '—') }}</td>
                            <td>{{ $dispute->created_at ? $dispute->created_at->format('M d, Y') : '—' }}</td>
                            <td class="jb-col-meta capitalize">{{ $dispute->raised_by }}</td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $dispute->status])</td>
                            <td class="jb-table-actions-col"><div class="jb-actions"><x-admin.action-btn variant="view" :href="route('admin.disputes.show', $dispute)" /></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="jb-table-empty">No disputes for this category.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($disputes->hasPages()) {{ $disputes->links() }} @endif
    </div>
@endsection

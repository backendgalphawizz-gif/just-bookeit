@extends('admin.layouts.app')
@section('title', 'Products')
@section('page_title', 'Products')
@section('page_subtitle', 'Items vendors want to sell or rent — approve and manage')
@section('content')
    @push('filter_actions')
        <x-admin.export-dropdown module="portfolio" :params="['search', 'status', 'vendor_id', 'from', 'to']" />
        @if (auth('admin')->user()->hasPermission('portfolio', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.portfolio.create')">+ Add Product</x-admin.button>
        @endif
    @endpush
    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, vendor..." class="jb-input">
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">Status</label>
                <select name="status" class="jb-select">
                    <option value="">All</option>
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
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.portfolio.index')])
        </div>
    </form>
    <div class="jb-card">
        <div class="jb-card-header"><p class="jb-card-header-title">{{ $items->total() }} products</p></div>
        <div class="jb-table-wrap">
            <table class="jb-table jb-table--balanced">
                <thead><tr>
                    @include('admin.partials.table-index-header')
                    <th class="jb-col-name">Title</th>
                    <th class="jb-col-name">Vendor</th>
                    <th class="jb-col-category">Category</th>
                    <th class="jb-col-amount">Price/day</th>
                    <th class="jb-col-status">Status</th>
                    <th class="jb-col-date">Submitted</th>
                    <th class="jb-table-actions-col">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $items])
                            <td class="jb-col-name max-w-[10rem]">
                                <span class="block truncate font-medium" title="{{ $item->title }}">{{ $item->title }}</span>
                            </td>
                            <td class="jb-col-name max-w-[10rem]">
                                <span class="block truncate" title="{{ $item->vendor->brand_name }}">{{ $item->vendor->brand_name }}</span>
                            </td>
                            <td class="jb-col-category">
                                <span class="block truncate text-sm" title="{{ $item->category?->name ?? '—' }}">{{ $item->category?->name ?? '—' }}</span>
                            </td>
                            <td class="jb-col-amount text-sm text-slate-600">
                                @if ($item->price_per_day !== null)
                                    ₹{{ number_format((float) $item->price_per_day, 0) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $item->status])</td>
                            <td class="jb-col-date text-sm text-slate-500">{{ $item->created_at->format('M d, Y') }}</td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    <x-admin.action-btn variant="view" :href="route('admin.portfolio.show', $item)" />
                                    @if (auth('admin')->user()->hasPermission('portfolio', 'edit'))
                                        <x-admin.action-btn variant="edit" :href="route('admin.portfolio.edit', $item)" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="jb-table-empty">No products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($items->hasPages()) {{ $items->links() }} @endif
    </div>
@endsection

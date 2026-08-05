@extends('admin.layouts.app')
@section('title', 'Products')
@section('page_title', 'Products')
@section('page_subtitle', 'Items vendors want to sell or rent — approve and manage')
@section('content')
    @php
        $typeLabels = [
            'fashion-designer' => 'Fashion Designer',
            'rented-dress' => 'Rental Dress',
            'rented-jewellery' => 'Rental Jewellery',
        ];
        $allCount = (int) collect($tabCounts)->sum();
        $activeTypeLabel = $type === 'all'
            ? 'All'
            : ($typeLabels[$type] ?? ($typeTabs->firstWhere('slug', $type)?->name ?? 'Products'));
        $filterQuery = request()->except('page', 'type');
        $createType = $type === 'all'
            ? ($typeTabs->first()?->slug ?? 'fashion-designer')
            : $type;
    @endphp

    <div class="jb-tabs-row">
        <div class="jb-tabs-list">
            <a href="{{ route('admin.portfolio.index', array_merge($filterQuery, ['type' => 'all'])) }}"
               class="jb-settings-tab {{ $type === 'all' ? 'jb-settings-tab--active' : '' }}">
                All ({{ $allCount }})
            </a>
            @foreach ($typeTabs as $tab)
                @php
                    $tabCount = (int) ($tabCounts[$tab->id] ?? 0);
                    $tabLabel = $typeLabels[$tab->slug] ?? $tab->name;
                @endphp
                <a href="{{ route('admin.portfolio.index', array_merge($filterQuery, ['type' => $tab->slug])) }}"
                   class="jb-settings-tab {{ $type === $tab->slug ? 'jb-settings-tab--active' : '' }}">
                    {{ $tabLabel }} ({{ $tabCount }})
                </a>
            @endforeach
        </div>
    </div>

    @push('filter_actions')
        <x-admin.export-dropdown module="portfolio" :params="['type', 'search', 'status', 'vendor_id', 'from', 'to']" />
        @if (auth('admin')->user()->hasPermission('portfolio', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.portfolio.create', ['type' => $createType])">+ Add Product</x-admin.button>
        @endif
    @endpush
    <form method="GET" class="jb-filters">
        <input type="hidden" name="type" value="{{ $type }}">
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
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.portfolio.index', ['type' => $type])])
        </div>
    </form>
    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $items->total() }} {{ strtolower($activeTypeLabel) }} products</p>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table jb-table--balanced">
                <thead><tr>
                    @include('admin.partials.table-index-header')
                    <th class="jb-col-name">Title</th>
                    <th class="jb-col-name">Vendor</th>
                    <th class="jb-col-category">Sub-category</th>
                    <th class="jb-col-amount">Price/day</th>
                    <th class="jb-col-status">Status</th>
                    <th class="jb-col-date">Submitted</th>
                    <th class="jb-col-status">Listing</th>
                    <th class="jb-table-actions-col">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $isApproved = $item->status === 'approved';
                            $isListingActive = (bool) ($item->is_listing_active ?? true);
                        @endphp
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $items])
                            <td class="jb-col-name max-w-[10rem]">
                                <span class="block truncate font-medium" title="{{ $item->title }}">{{ $item->title }}</span>
                            </td>
                            <td class="jb-col-name max-w-[10rem]">
                                <span class="block truncate" title="{{ $item->vendor->brand_name }}">{{ $item->vendor->brand_name }}</span>
                            </td>
                            <td class="jb-col-category">
                                @php
                                    $subLabel = $item->subcategory
                                        ? trim(($item->subcategory->parent?->name ?? '').' / '.$item->subcategory->name, ' /')
                                        : '—';
                                @endphp
                                <span class="block truncate text-sm" title="{{ $subLabel }}">{{ $subLabel }}</span>
                            </td>
                            <td class="jb-col-amount text-sm text-slate-600">
                                @if ($item->price_per_day !== null)
                                    ₹{{ number_format((float) $item->price_per_day, 0) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $item->status, 'label' => ucfirst((string) $item->status)])</td>
                            <td class="jb-col-date text-sm text-slate-500">{{ \App\Support\AdminDateTime::formatDate($item->created_at) }}</td>
                            <td class="jb-col-status">
                                @if (auth('admin')->user()->hasPermission('portfolio', 'edit'))
                                    <form
                                        method="POST"
                                        action="{{ route('admin.portfolio.listing-active', $item) }}"
                                        class="jb-listing-toggle @unless($isApproved) is-disabled @endunless"
                                        @unless($isApproved) title="Only approved products can be activated" @endunless
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_listing_active" value="0">
                                        <label class="jb-toggle">
                                            <input
                                                type="checkbox"
                                                name="is_listing_active"
                                                value="1"
                                                @checked($isApproved && $isListingActive)
                                                @disabled(! $isApproved)
                                                onchange="this.form.submit()"
                                            >
                                            <span class="jb-toggle-track"></span>
                                        </label>
                                        <span @class(['jb-listing-toggle-label', 'is-active' => $isApproved && $isListingActive])>
                                            {{ $isApproved && $isListingActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </form>
                                @else
                                    <span class="text-sm text-slate-500">{{ $isApproved && $isListingActive ? 'Active' : 'Inactive' }}</span>
                                @endif
                            </td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions jb-portfolio-row-actions" x-data="{ rejectOpen: false }">
                                    <x-admin.action-btn variant="view" :href="route('admin.portfolio.show', $item)" />
                                    @if (auth('admin')->user()->hasPermission('portfolio', 'edit'))
                                        <x-admin.action-btn variant="edit" :href="route('admin.portfolio.edit', $item)" />
                                        @if (in_array($item->status, ['pending', 'rejected'], true))
                                            <form method="POST" action="{{ route('admin.portfolio.approve', $item) }}">
                                                @csrf
                                                <x-admin.action-btn
                                                    variant="approve"
                                                    type="submit"
                                                    :confirm="$item->status === 'rejected' ? 'Approve this product again? It will become visible once listing is active.' : 'Approve this product?'"
                                                    confirmTitle="Approve product"
                                                    confirmVariant="success"
                                                    confirmLabel="Approve"
                                                >
                                                    {{ $item->status === 'rejected' ? 'Approve again' : 'Approve' }}
                                                </x-admin.action-btn>
                                            </form>
                                        @endif
                                        @if ($item->status === 'pending')
                                            <div class="relative">
                                                <x-admin.button
                                                    type="button"
                                                    size="sm"
                                                    variant="danger"
                                                    @click="rejectOpen = !rejectOpen"
                                                >
                                                    Reject
                                                </x-admin.button>
                                                <div
                                                    class="jb-portfolio-reject-panel"
                                                    x-show="rejectOpen"
                                                    x-cloak
                                                    @click.outside="rejectOpen = false"
                                                >
                                                    <form method="POST" action="{{ route('admin.portfolio.reject', $item) }}" class="space-y-2">
                                                        @csrf
                                                        <label class="jb-label" for="rejection_reason_{{ $item->id }}">Rejection reason</label>
                                                        <textarea
                                                            id="rejection_reason_{{ $item->id }}"
                                                            name="rejection_reason"
                                                            class="jb-input"
                                                            rows="3"
                                                            required
                                                            maxlength="500"
                                                            placeholder="Why is this product being rejected?"
                                                        ></textarea>
                                                        <div class="flex items-center justify-end gap-2">
                                                            <x-admin.button type="button" size="sm" variant="ghost" @click="rejectOpen = false">Cancel</x-admin.button>
                                                            <x-admin.button type="submit" size="sm" variant="danger">Reject</x-admin.button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="jb-table-empty">No {{ strtolower($activeTypeLabel) }} products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($items->hasPages()) {{ $items->links() }} @endif
    </div>
@endsection

@push('styles')
<style>
    .jb-listing-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        margin: 0;
    }
    .jb-listing-toggle.is-disabled {
        opacity: 0.7;
    }
    .jb-listing-toggle-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8;
        white-space: nowrap;
    }
    .jb-listing-toggle-label.is-active {
        color: #f25123;
    }
    .jb-toggle {
        position: relative;
        width: 42px;
        height: 24px;
        flex-shrink: 0;
    }
    .jb-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }
    .jb-toggle-track {
        position: absolute;
        inset: 0;
        background: #d5dce3;
        border-radius: 999px;
        cursor: pointer;
        transition: background .2s;
    }
    .jb-toggle-track::after {
        content: '';
        position: absolute;
        width: 18px;
        height: 18px;
        left: 3px;
        top: 3px;
        background: #fff;
        border-radius: 50%;
        transition: transform .2s;
        box-shadow: 0 1px 3px rgba(0,0,0,.15);
    }
    .jb-toggle input:checked + .jb-toggle-track {
        background: #f25123;
    }
    .jb-toggle input:checked + .jb-toggle-track::after {
        transform: translateX(18px);
    }
    .jb-toggle input:disabled + .jb-toggle-track {
        opacity: .55;
        cursor: not-allowed;
    }
    .jb-portfolio-row-actions {
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.4rem;
    }
    .jb-table td.jb-table-actions-col:has(.jb-portfolio-row-actions) {
        width: auto;
        min-width: 12rem;
        white-space: normal;
    }
    .jb-portfolio-reject-panel {
        position: absolute;
        right: 0;
        top: calc(100% + 0.4rem);
        z-index: 30;
        width: min(18rem, 70vw);
        padding: 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        box-shadow: 0 10px 30px rgb(15 23 42 / 0.12);
    }
</style>
@endpush

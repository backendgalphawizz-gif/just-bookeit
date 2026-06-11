@extends('admin.layouts.app')

@section('title', 'Portfolio')
@section('page_title', 'Portfolio')
@section('page_subtitle', 'Vendors with previous work — open a vendor to see their photos')
@section('content')
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-900">
        <p class="font-medium">Portfolio vs products</p>
        <p class="mt-1 text-amber-800">
            Pick a vendor from the list below and click <strong>View</strong> to see their portfolio photos.
            To manage items they sell or rent, go to
            <a href="{{ route('admin.portfolio.index') }}" class="font-semibold underline">Products</a>.
        </p>
    </div>

    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search vendor</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Brand, owner, email, mobile..." class="jb-input">
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">Vendor</label>
                <select name="vendor_id" class="jb-select">
                    <option value="">All vendors</option>
                    @foreach ($vendorOptions as $v)
                        <option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->brand_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">Status</label>
                <select name="status" class="jb-select">
                    <option value="">All</option>
                    @foreach (['pending', 'active', 'suspended', 'rejected'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">City</label>
                <input type="text" name="city" value="{{ request('city') }}" placeholder="City" class="jb-input">
            </div>
            <div class="jb-filters-field">
                <label class="jb-label">Audience</label>
                <select name="audience" class="jb-select">
                    <option value="">All</option>
                    @foreach (['women' => 'Women', 'men' => 'Men', 'kids' => 'Kids'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('audience') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.vendor-portfolio.index')])
        </div>
    </form>

    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $vendors->total() }} {{ Str::plural('vendor', $vendors->total()) }} with portfolio photos</p>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table jb-table--balanced">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-name">Brand</th>
                        <th class="jb-col-name">Owner</th>
                        <th>Mobile</th>
                        <th class="jb-col-category">City</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-col-amount text-center">Photos</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendors as $vendor)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $vendors])
                            <td class="jb-col-name max-w-[10rem]">
                                <div class="jb-actor-cell">
                                    @include('admin.partials.actor-avatar', [
                                        'imageUrl' => $vendor->profileImageUrl(),
                                        'fallbackUrl' => $vendor->shopLogoUrl(),
                                        'label' => $vendor->brand_name,
                                    ])
                                    <span class="block truncate font-medium" title="{{ $vendor->brand_name }}">{{ $vendor->brand_name }}</span>
                                </div>
                            </td>
                            <td class="jb-col-name max-w-[10rem]">
                                <span class="block truncate" title="{{ $vendor->owner_name }}">{{ $vendor->owner_name ?? '—' }}</span>
                            </td>
                            <td class="text-sm text-slate-600">{{ $vendor->mobile ?? $vendor->business_mobile ?? '—' }}</td>
                            <td class="jb-col-category">
                                <span class="block truncate text-sm" title="{{ $vendor->city }}">{{ $vendor->city ?? '—' }}</span>
                            </td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $vendor->status])</td>
                            <td class="jb-col-amount text-center text-sm font-medium text-slate-700">{{ $vendor->portfolio_photos_count }}</td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    <x-admin.action-btn variant="view" :href="route('admin.vendor-portfolio.show', array_merge([$vendor], request()->only(['audience', 'from', 'to'])))" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="jb-table-empty">No vendors with portfolio photos match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($vendors->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $vendors->links() }}</div>
        @endif
    </div>
@endsection

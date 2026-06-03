@extends('admin.layouts.app')
@section('title', 'Vendors')
@section('page_title', 'Vendors')
@section('page_subtitle', 'Designer onboarding and vendor operations')

@section('content')
    @push('filter_actions')
        @if (auth('admin')->user()->hasPermission('vendors', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.vendors.create')">+ Add Vendor</x-admin.button>
        @endif
    @endpush
    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Brand, owner, email ID..." class="jb-input">
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
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.vendors.index')])
        </div>
    </form>

    <div class="jb-card">
        <div class="jb-card-header"><p class="jb-card-header-title">{{ $vendors->total() }} vendors</p></div>
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-id">Vendor ID</th>
                        <th class="jb-col-name">Brand</th>
                        <th>City</th>
                        <th class="text-center">Rating</th>
                        <th class="text-center">Orders</th>
                        <th class="jb-col-amount">Earnings</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendors as $vendor)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $vendors])
                            <td class="jb-col-id"><span class="font-mono text-xs font-semibold text-slate-500">{{ $vendor->vendor_code }}</span></td>
                            <td class="jb-col-name">
                                <div class="jb-actor-cell">
                                    @include('admin.partials.actor-avatar', [
                                        'imageUrl' => $vendor->profileImageUrl(),
                                        'fallbackUrl' => $vendor->shopLogoUrl(),
                                        'label' => $vendor->brand_name,
                                    ])
                                    <span class="font-semibold" title="{{ $vendor->brand_name }}">{{ $vendor->brand_name }}</span>
                                </div>
                            </td>
                            <td>{{ $vendor->city ?? '—' }}</td>
                            <td class="text-center">{{ number_format($vendor->rating, 1) }}</td>
                            <td class="text-center">{{ $vendor->orders_completed }}</td>
                            <td class="jb-col-amount">₹{{ number_format($vendor->earnings, 0) }}</td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $vendor->status])</td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    <x-admin.action-btn variant="view" :href="route('admin.vendors.show', $vendor)" />
                                    @if ($vendor->status === 'pending' && auth('admin')->user()->hasPermission('vendors', 'edit'))
                                        <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}" class="jb-action-form">@csrf
                                            <x-admin.action-btn variant="approve" type="submit" />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="jb-table-empty">No vendors found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($vendors->hasPages()) {{ $vendors->links() }} @endif
    </div>
@endsection

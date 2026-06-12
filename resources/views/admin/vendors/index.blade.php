in this table  i need @extends('admin.layouts.app')
@section('title', 'Vendors')
@section('page_title', 'Vendors')
@section('page_subtitle', 'Designer onboarding and vendor operations')

@php
    $canBulkApprove = auth('admin')->user()->hasPermission('vendors', 'edit');
    $pendingOnPage = $vendors->where('status', 'pending')->pluck('id')->values();
@endphp

@section('content')
    @push('filter_actions')
        <x-admin.export-dropdown module="vendors" :params="['search', 'status', 'city', 'from', 'to']" />
        @if (auth('admin')->user()->hasPermission('vendors', 'create'))
            <x-admin.button variant="primary" size="sm" :href="route('admin.vendors.create')">+ Add Vendor</x-admin.button>
        @endif
    @endpush
    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Brand, owner, email ID, mobile no..." class="jb-input">
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

    <div
        class="jb-card"
        x-data="{
            pendingOnPage: @json($pendingOnPage),
            selected: [],
            toggleAll(checked) {
                this.selected = checked ? this.pendingOnPage.map(String) : [];
            },
            get allPendingSelected() {
                return this.pendingOnPage.length > 0 && this.selected.length === this.pendingOnPage.length;
            },
            get someSelected() {
                return this.selected.length > 0 && !this.allPendingSelected;
            }
        }"
    >
        @if ($canBulkApprove)
            <form
                method="POST"
                action="{{ route('admin.vendors.bulk-approve') }}"
                id="vendor-bulk-approve-form"
                data-jb-confirm="The selected pending vendors will be approved and activated."
                data-jb-confirm-title="Approve selected vendors?"
                data-jb-confirm-label="Approve selected"
                @submit="if (selected.length === 0) $event.preventDefault()"
            >
                @csrf
            </form>
        @endif
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $vendors->total() }} vendors</p>
            @if ($canBulkApprove)
                <div class="jb-bulk-actions" x-show="selected.length > 0" x-cloak>
                    <span class="jb-bulk-actions-count" x-text="`${selected.length} selected`"></span>
                    <x-admin.button variant="success" size="sm" type="submit" form="vendor-bulk-approve-form">Approve selected</x-admin.button>
                </div>
            @endif
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table">
                <thead>
                    <tr>
                        @if ($canBulkApprove)
                            <th class="jb-col-check">
                                <input
                                    type="checkbox"
                                    class="jb-checkbox-accent"
                                    aria-label="Select all pending vendors on this page"
                                    :checked="allPendingSelected"
                                    :disabled="pendingOnPage.length === 0"
                                    x-bind:indeterminate="someSelected"
                                    @change="toggleAll($event.target.checked)"
                                >
                            </th>
                        @endif
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-name">Name</th>
                        <th class="jb-col-name">Brand</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>City</th>
                        <th>Registration Date</th>
                        <th class="jb-col-status">Status</th>
                        <th class="text-center">Rating</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendors as $vendor)
                        <tr>
                            @if ($canBulkApprove)
                                <td class="jb-col-check">
                                    @if ($vendor->status === 'pending')
                                        <input
                                            type="checkbox"
                                            name="vendor_ids[]"
                                            value="{{ $vendor->id }}"
                                            form="vendor-bulk-approve-form"
                                            class="jb-checkbox-accent"
                                            aria-label="Select {{ $vendor->brand_name }}"
                                            x-model="selected"
                                        >
                                    @endif
                                </td>
                            @endif
                            @include('admin.partials.table-index-cell', ['paginator' => $vendors])
                            <td class="jb-col-name">
                                <span>{{ $vendor->owner_name ?? '—' }}</span>
                            </td>
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
                            <td>{{ $vendor->mobile ?? '—' }}</td>
                            <td>{{ $vendor->email ?? '—' }}</td>
                            <td>{{ $vendor->city ?? '—' }}</td>
                            <td>{{ $vendor->created_at ? $vendor->created_at->format('M d, Y') : '—' }}</td>
                            <td class="jb-col-status">
                                @include('admin.components.status-badge', ['status' => $vendor->status])
                                @if ($vendor->status === 'suspended' && $vendor->suspension_reason)
                                    <p class="mt-1 max-w-[12rem] truncate text-xs text-orange-700" title="{{ $vendor->suspension_reason }}">{{ $vendor->suspension_reason }}</p>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($vendor->rating, 1) }}</td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    @if (in_array($vendor->status, ['pending', 'rejected'], true) && $canBulkApprove)
                                        <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}" class="jb-action-form">@csrf
                                            <x-admin.action-btn variant="approve" type="submit" title="{{ $vendor->status === 'rejected' ? 'Approve again' : 'Approve' }}" />
                                        </form>
                                    @endif
                                    <x-admin.action-btn variant="view" :href="route('admin.vendors.show', $vendor)" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canBulkApprove ? 11 : 10 }}" class="jb-table-empty">No vendors found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($vendors->hasPages())
            <div class="jb-card-pad">{{ $vendors->links() }}</div>
        @endif
    </div>
@endsection

@extends('admin.layouts.app')
@section('title', $vendor->brand_name)
@section('page_title')
    <span class="block max-w-full truncate" title="{{ $vendor->brand_name }}">{{ $vendor->brand_name }}</span>
@endsection
@section('page_subtitle', $vendor->vendor_code)
@section('back_href', route('admin.vendors.index'))
@section('header_actions')
    @if (in_array($vendor->status, ['pending', 'rejected'], true) && auth('admin')->user()->hasPermission('vendors', 'edit'))
        <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">@csrf<x-admin.button variant="success" type="submit">{{ $vendor->status === 'rejected' ? 'Approve again' : 'Approve' }}</x-admin.button></form>
        @if ($vendor->status === 'pending')
            <form
                method="POST"
                action="{{ route('admin.vendors.reject', $vendor) }}"
                data-jb-confirm="This vendor will be rejected. The reason you enter will be visible to them."
                data-jb-confirm-title="Reject vendor"
                data-jb-confirm-variant="error"
                data-jb-confirm-label="Reject"
                data-jb-confirm-requires-reason="Rejection reason"
            >
                @csrf
                <x-admin.button variant="danger" type="submit">Reject</x-admin.button>
            </form>
        @endif
    @endif
    @if ($vendor->status === 'inactive' && auth('admin')->user()->hasPermission('vendors', 'edit'))
        <form method="POST" action="{{ route('admin.vendors.activate', $vendor) }}" class="inline-flex">@csrf<x-admin.button variant="success" type="submit">Unblock</x-admin.button></form>
    @endif
    @if ($vendor->status === 'active' && auth('admin')->user()->hasPermission('vendors', 'edit'))
        <form
            method="POST"
            action="{{ route('admin.vendors.inactivate', $vendor) }}"
            class="inline-flex"
            data-jb-confirm="This vendor will be blocked. Login and listings will be disabled. The reason you enter will be visible to them."
            data-jb-confirm-title="Block vendor"
            data-jb-confirm-variant="error"
            data-jb-confirm-label="Block"
            data-jb-confirm-requires-reason="Reason for blocking"
        >
            @csrf
            <x-admin.button variant="danger" type="submit">Block</x-admin.button>
        </form>
    @endif
    <x-admin.account-history :histories="$vendor->statusHistories" title="Vendor account history" />
    @if (auth('admin')->user()->hasPermission('vendors', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.vendors.edit', $vendor)">Edit</x-admin.button>
    @endif
@endsection
@section('content')
    @if ($vendor->isInactive())
        @include('admin.partials.account-status-banner', [
            'title' => 'Account blocked',
            'reason' => $vendor->rejection_reason,
            'emptyReason' => 'No reason recorded.',
            'showAction' => auth('admin')->user()->hasPermission('vendors', 'edit'),
            'actionRoute' => route('admin.vendors.activate', $vendor),
            'actionLabel' => 'Unblock account',
        ])
    @endif

    @if ($vendor->status === 'rejected')
        @include('admin.partials.account-status-banner', [
            'title' => 'Application rejected',
            'reason' => $vendor->rejection_reason,
            'emptyReason' => 'No rejection reason recorded.',
            'showAction' => auth('admin')->user()->hasPermission('vendors', 'edit'),
            'actionRoute' => route('admin.vendors.approve', $vendor),
            'actionLabel' => 'Approve vendor',
        ])
    @endif

    <div class="jb-wallet-grid">
        <div class="jb-wallet-card jb-wallet-card--digital">
            <p class="jb-wallet-card-label">Digital Wallet</p>
            <p class="jb-wallet-card-value">₹{{ number_format($vendor->digital_wallet_balance, 0) }}</p>
            <p class="jb-wallet-card-note">Payments on 15-day hold</p>
        </div>
        <div class="jb-wallet-card jb-wallet-card--actual">
            <p class="jb-wallet-card-label">Actual Wallet</p>
            <p class="jb-wallet-card-value">₹{{ number_format($vendor->wallet_balance, 0) }}</p>
            <p class="jb-wallet-card-note">Available for withdrawal</p>
        </div>
        <div class="jb-wallet-card">
            <p class="jb-wallet-card-label">Total Earnings</p>
            <p class="jb-wallet-card-value">₹{{ number_format($vendor->earnings, 0) }}</p>
            <p class="jb-wallet-card-note">Lifetime recorded earnings</p>
        </div>
    </div>

    <div class="jb-detail-grid">
        <div class="jb-detail-card lg:col-span-2">
            <h2>Profile & branding</h2>
            <x-admin.actor-profile-header
                :image-url="$vendor->profileImageUrl()"
                :fallback-url="$vendor->shopLogoUrl()"
                :title="$vendor->shop_name ?? $vendor->brand_name"
                :subtitle="$vendor->vendor_code"
            >
                @include('admin.components.status-badge', ['status' => $vendor->status, 'label' => \App\Support\AdminAccountStatus::labelFor($vendor->status)])
            </x-admin.actor-profile-header>
            <div class="jb-doc-image-grid mt-4">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Profile photo</p>
                    @if ($vendor->profileImageUrl())
                        <img src="{{ $vendor->profileImageUrl() }}" alt="Profile photo" class="jb-doc-image panel-lightbox-trigger" style="max-width:10rem">
                    @else
                        <p class="text-sm text-slate-500">Not uploaded</p>
                    @endif
                </div>
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Shop logo</p>
                    @if ($vendor->shopLogoUrl())
                        <img src="{{ $vendor->shopLogoUrl() }}" alt="Shop logo" class="jb-doc-image panel-lightbox-trigger" style="max-width:10rem">
                    @else
                        <p class="text-sm text-slate-500">Not uploaded</p>
                    @endif
                </div>
                <div class="sm:col-span-2">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Shop images</p>
                    @if ($vendor->shopImageUrls() !== [])
                        <div class="flex flex-wrap gap-3">
                            @foreach ($vendor->shopImageUrls() as $imageUrl)
                                <img src="{{ $imageUrl }}" alt="Shop image" class="jb-doc-image panel-lightbox-trigger" style="max-width:10rem">
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500">Not uploaded</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="jb-detail-card">
            <h2>Business details</h2>
            <dl class="jb-dl">
                <div><dt>Shop name</dt><dd>{{ $vendor->shop_name ?? '—' }}</dd></div>
                <div><dt>Brand name</dt><dd>{{ $vendor->brand_name }}</dd></div>
                <div><dt>Owner name</dt><dd>{{ $vendor->owner_name }}</dd></div>
                <div><dt>Mobile No</dt><dd>{{ $vendor->mobile }}</dd></div>
                <div><dt>Email ID</dt><dd>{{ $vendor->email ?? '—' }}</dd></div>
                <div><dt>Service types</dt><dd>{{ $vendor->serviceType() ?? '—' }}</dd></div>
                <div><dt>Business Mobile No</dt><dd>{{ $vendor->business_mobile ?? '—' }}</dd></div>
                <div><dt>Business Email ID</dt><dd>{{ $vendor->business_email ?? '—' }}</dd></div>
                <div><dt>GST number</dt><dd>{{ $vendor->gst_number ?? '—' }}</dd></div>
            </dl>
        </div>

        <div class="jb-detail-card">
            <h2>Address</h2>
            <dl class="jb-dl">
                <div class="sm:col-span-2"><dt>Address</dt><dd>{{ $vendor->address ?? '—' }}</dd></div>
                <div><dt>Country</dt><dd>{{ $vendor->country ?? '—' }}</dd></div>
                <div><dt>State</dt><dd>{{ $vendor->state ?? '—' }}</dd></div>
                <div><dt>City</dt><dd>{{ $vendor->city ?? '—' }}</dd></div>
                <div><dt>Pincode</dt><dd>{{ $vendor->pincode ?? '—' }}</dd></div>
            </dl>
        </div>

        <div class="jb-detail-card lg:col-span-2">
            <h2>KYC documents</h2>
            <div class="jb-doc-image-grid">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Aadhar front</p>
                    @if ($vendor->aadharFrontUrl())
                        <img src="{{ $vendor->aadharFrontUrl() }}" alt="Aadhar front" class="jb-doc-image panel-lightbox-trigger">
                    @else
                        <p class="text-sm text-slate-500">Not uploaded</p>
                    @endif
                </div>
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Aadhar back</p>
                    @if ($vendor->aadharBackUrl())
                        <img src="{{ $vendor->aadharBackUrl() }}" alt="Aadhar back" class="jb-doc-image panel-lightbox-trigger">
                    @else
                        <p class="text-sm text-slate-500">Not uploaded</p>
                    @endif
                </div>
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">PAN card</p>
                    @if ($vendor->panCardUrl())
                        <img src="{{ $vendor->panCardUrl() }}" alt="PAN card" class="jb-doc-image panel-lightbox-trigger">
                    @else
                        <p class="text-sm text-slate-500">Not uploaded</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="jb-detail-card">
            <h2>Bank details</h2>
            <dl class="jb-dl">
                <div><dt>Account holder name</dt><dd>{{ $vendor->account_name ?? '—' }}</dd></div>
                <div><dt>Account number</dt><dd>{{ $vendor->account_number ?? '—' }}</dd></div>
                <div><dt>IFSC code</dt><dd>{{ $vendor->ifsc_code ?? '—' }}</dd></div>
                <div><dt>Bank name</dt><dd>{{ $vendor->bank_name ?? '—' }}</dd></div>
                <div><dt>Account type</dt><dd>{{ $vendor->account_type ? ucfirst($vendor->account_type) : '—' }}</dd></div>
            </dl>
        </div>

        <div class="jb-detail-card">
            <h2>Categories & admin stats</h2>
            <dl class="jb-dl">
                <div class="sm:col-span-2">
                    <dt>Categories</dt>
                    <dd>{{ filled($vendor->categories) ? implode(', ', $vendor->categories) : '—' }}</dd>
                </div>
                <div><dt>Status</dt><dd>@include('admin.components.status-badge', ['status' => $vendor->status, 'label' => \App\Support\AdminAccountStatus::labelFor($vendor->status)])</dd></div>
                <div><dt>Commission</dt><dd>{{ $vendor->commission ? $vendor->commission . '%' : 'Using global commission' }}</dd></div>
                <div><dt>Rating</dt><dd>{{ $vendor->rating }} / 5</dd></div>
                <div><dt>Orders completed</dt><dd>{{ number_format($vendor->orders_completed) }}</dd></div>
                <div><dt>Earnings</dt><dd>₹{{ number_format($vendor->earnings, 2) }}</dd></div>
            </dl>
        </div>

        <div class="jb-detail-card lg:col-span-2">
            <h2>Recent Orders</h2>
            <div class="jb-table-wrap mt-4">
                <table class="jb-table">
                    <thead><tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-id">Order</th>
                        <th class="jb-col-name">Customer</th>
                        <th class="jb-col-amount">Amount</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($vendor->orders as $order)
                            <tr>
                                @include('admin.partials.table-index-cell')
                                <td class="jb-col-id font-semibold">{{ $order->order_number }}</td>
                                <td class="jb-col-name">{{ $order->customer->name }}</td>
                                <td class="jb-col-amount">₹{{ number_format($order->amount, 2) }}</td>
                                <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $order->status])</td>
                                <td class="jb-table-actions-col"><div class="jb-actions"><x-admin.action-btn variant="view" :href="route('admin.orders.show', $order)" /></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="jb-table-empty">No orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

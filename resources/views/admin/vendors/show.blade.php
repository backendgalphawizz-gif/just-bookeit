@extends('admin.layouts.app')
@section('title', $vendor->brand_name)
@section('page_title')
    <span class="block max-w-full truncate" title="{{ $vendor->brand_name }}">{{ $vendor->brand_name }}</span>
@endsection
@section('page_subtitle', $vendor->vendor_code)
@section('back_href', route('admin.vendors.index'))
@section('header_actions')
    @if ($vendor->status === 'pending' && auth('admin')->user()->hasPermission('vendors', 'edit'))
        <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">@csrf<x-admin.button variant="success" type="submit">Approve</x-admin.button></form>
        <form method="POST" action="{{ route('admin.vendors.reject', $vendor) }}">@csrf<x-admin.button variant="danger" type="submit">Reject</x-admin.button></form>
    @endif
    @if ($vendor->status === 'suspended' && auth('admin')->user()->hasPermission('vendors', 'edit'))
        <form method="POST" action="{{ route('admin.vendors.activate', $vendor) }}">@csrf<x-admin.button variant="success" type="submit">Activate</x-admin.button></form>
    @endif
    @if (auth('admin')->user()->hasPermission('vendors', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.vendors.edit', $vendor)">Edit</x-admin.button>
    @endif
@endsection
@section('content')
    @if ($vendor->isSuspended())
        <div class="jb-card mb-6 border-orange-200 bg-orange-50/80">
            <div class="jb-card-body">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold uppercase tracking-wide text-orange-800">Account suspended</p>
                        <p class="mt-2 text-sm leading-relaxed text-orange-950">{{ $vendor->suspension_reason ?: 'No suspension reason recorded.' }}</p>
                        <dl class="mt-4 grid gap-2 text-sm text-orange-900/80 sm:grid-cols-2">
                            @if ($vendor->suspended_at)
                                <div><dt class="font-semibold text-orange-900">Suspended on</dt><dd>{{ $vendor->suspended_at->format('M d, Y h:i A') }}</dd></div>
                            @endif
                            @if ($vendor->suspendedBy)
                                <div><dt class="font-semibold text-orange-900">Suspended by</dt><dd>{{ $vendor->suspendedBy->name }}</dd></div>
                            @endif
                        </dl>
                    </div>
                    @if (auth('admin')->user()->hasPermission('vendors', 'edit'))
                        <form method="POST" action="{{ route('admin.vendors.activate', $vendor) }}">@csrf
                            <x-admin.button variant="success" type="submit">Activate account</x-admin.button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
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
        <div class="jb-detail-card">
            <h2>Profile</h2>
            <x-admin.actor-profile-header
                :image-url="$vendor->profileImageUrl()"
                :fallback-url="$vendor->shopLogoUrl()"
                :title="$vendor->shop_name ?? $vendor->brand_name"
                :subtitle="$vendor->vendor_code"
            >
                @include('admin.components.status-badge', ['status' => $vendor->status])
            </x-admin.actor-profile-header>
            <dl class="jb-dl">
                <div><dt>Owner</dt><dd>{{ $vendor->owner_name }}</dd></div>
                <div><dt>Mobile No</dt><dd>{{ $vendor->mobile }}</dd></div>
                <div><dt>Email ID</dt><dd>{{ $vendor->email ?? '—' }}</dd></div>
                <div><dt>City</dt><dd>{{ $vendor->city ?? '—' }}</dd></div>
                <div><dt>Service type</dt><dd>{{ $vendor->serviceType() ?? '—' }}</dd></div>
                <div><dt>Rating</dt><dd>{{ $vendor->rating }} / 5</dd></div>
                <div><dt>Digital wallet</dt><dd>₹{{ number_format($vendor->digital_wallet_balance, 2) }}</dd></div>
                <div><dt>Actual wallet</dt><dd>₹{{ number_format($vendor->wallet_balance, 2) }}</dd></div>
                <div><dt>Total earnings</dt><dd>₹{{ number_format($vendor->earnings, 2) }}</dd></div>
            </dl>
        </div>
        @if ($vendor->shopLogoUrls() !== [] || $vendor->panCardUrl())
            <div class="jb-detail-card lg:col-span-2">
                <h2>Shop & documents</h2>
                <div class="jb-doc-image-grid">
                    @if ($vendor->shopLogoUrls() !== [])
                        <div class="sm:col-span-2">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Shop logos</p>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($vendor->shopLogoUrls() as $logoUrl)
                                    <img src="{{ $logoUrl }}" alt="Shop logo" class="jb-doc-image panel-lightbox-trigger" style="max-width:10rem">
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if ($vendor->panCardUrl())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">PAN card</p>
                            <img src="{{ $vendor->panCardUrl() }}" alt="PAN card" class="jb-doc-image panel-lightbox-trigger">
                        </div>
                    @endif
                </div>
            </div>
        @endif
        @if ($vendor->aadharFrontUrl() || $vendor->aadharBackUrl())
            <div class="jb-detail-card lg:col-span-2">
                <h2>Aadhar</h2>
                <div class="jb-doc-image-grid">
                    @if ($vendor->aadharFrontUrl())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Front</p>
                            <img src="{{ $vendor->aadharFrontUrl() }}" alt="Aadhar front" class="jb-doc-image panel-lightbox-trigger">
                        </div>
                    @endif
                    @if ($vendor->aadharBackUrl())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Back</p>
                            <img src="{{ $vendor->aadharBackUrl() }}" alt="Aadhar back" class="jb-doc-image panel-lightbox-trigger">
                        </div>
                    @endif
                </div>
            </div>
        @endif
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

    @if ($vendor->status === 'active' && auth('admin')->user()->hasPermission('vendors', 'edit'))
        <div class="jb-card mt-6 max-w-3xl">
            <div class="jb-card-header"><p class="jb-card-header-title">Suspend vendor</p></div>
            <div class="jb-card-body">
                <p class="mb-4 text-sm text-slate-500">Suspending blocks vendor login and listings. A clear reason is required and will be stored on the account.</p>
                <form method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}" class="space-y-4">
                    @csrf
                    @include('admin.partials.form-input', [
                        'label' => 'Reason for suspension',
                        'name' => 'suspension_reason',
                        'type' => 'textarea',
                        'rows' => 4,
                        'value' => old('suspension_reason'),
                        'required' => true,
                        'full' => true,
                        'hint' => 'Minimum 10 characters. Shown to admins and communicated to the vendor.',
                    ])
                    <x-admin.button variant="danger" type="submit">Suspend vendor</x-admin.button>
                </form>
            </div>
        </div>
    @endif
@endsection

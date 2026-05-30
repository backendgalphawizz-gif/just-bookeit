@extends('admin.layouts.app')
@section('title', $vendor->brand_name)
@section('page_title', $vendor->brand_name)
@section('page_subtitle', $vendor->vendor_code)
@section('header_actions')
    @if ($vendor->status === 'pending' && auth('admin')->user()->hasPermission('vendors', 'edit'))
        <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">@csrf<x-admin.button variant="success" type="submit">Approve</x-admin.button></form>
        <form method="POST" action="{{ route('admin.vendors.reject', $vendor) }}">@csrf<x-admin.button variant="danger" type="submit">Reject</x-admin.button></form>
    @endif
    @if ($vendor->status === 'active' && auth('admin')->user()->hasPermission('vendors', 'edit'))
        <form method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}">@csrf<x-admin.button variant="danger" type="submit">Suspend</x-admin.button></form>
    @endif
    @if (auth('admin')->user()->hasPermission('vendors', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.vendors.edit', $vendor)">Edit</x-admin.button>
    @endif
@endsection
@section('content')
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
                <div><dt>Contact</dt><dd>{{ $vendor->mobile }}<br>{{ $vendor->email }}</dd></div>
                <div><dt>City</dt><dd>{{ $vendor->city ?? '—' }}</dd></div>
                <div><dt>Service type</dt><dd>{{ $vendor->serviceType() ?? '—' }}</dd></div>
                <div><dt>Rating</dt><dd>{{ $vendor->rating }} / 5</dd></div>
                <div><dt>Earnings</dt><dd>₹{{ number_format($vendor->earnings, 2) }}</dd></div>
            </dl>
        </div>
        @if ($vendor->shopLogoUrl() || $vendor->panCardUrl())
            <div class="jb-detail-card lg:col-span-2">
                <h2>Shop & documents</h2>
                <div class="jb-doc-image-grid">
                    @if ($vendor->shopLogoUrl())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Shop logo</p>
                            <img src="{{ $vendor->shopLogoUrl() }}" alt="Shop logo" class="jb-doc-image">
                        </div>
                    @endif
                    @if ($vendor->panCardUrl())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">PAN card</p>
                            <img src="{{ $vendor->panCardUrl() }}" alt="PAN card" class="jb-doc-image">
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
                            <img src="{{ $vendor->aadharFrontUrl() }}" alt="Aadhar front" class="jb-doc-image">
                        </div>
                    @endif
                    @if ($vendor->aadharBackUrl())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Back</p>
                            <img src="{{ $vendor->aadharBackUrl() }}" alt="Aadhar back" class="jb-doc-image">
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
@endsection

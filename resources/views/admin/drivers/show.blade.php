@extends('admin.layouts.app')
@section('title', $driver->name)
@section('page_title', $driver->name)
@section('page_subtitle', $driver->driver_code)
@section('back_href', route('admin.drivers.index'))
@section('header_actions')
    @if (in_array($driver->status, ['pending', 'rejected'], true) && auth('admin')->user()->hasPermission('drivers', 'edit'))
        <form method="POST" action="{{ route('admin.drivers.approve', $driver) }}">@csrf<x-admin.button variant="success" type="submit">{{ $driver->status === 'rejected' ? 'Approve again' : 'Approve' }}</x-admin.button></form>
        @if ($driver->status === 'pending')
            <form
                method="POST"
                action="{{ route('admin.drivers.reject', $driver) }}"
                data-jb-confirm="This driver will be rejected. The reason you enter will be visible to them."
                data-jb-confirm-title="Reject driver"
                data-jb-confirm-variant="error"
                data-jb-confirm-label="Reject"
                data-jb-confirm-requires-reason="Rejection reason"
            >
                @csrf
                <x-admin.button variant="danger" type="submit">Reject</x-admin.button>
            </form>
        @endif
    @endif
    @if ($driver->status === 'inactive' && auth('admin')->user()->hasPermission('drivers', 'edit'))
        <form method="POST" action="{{ route('admin.drivers.approve', $driver) }}" class="inline-flex">@csrf<x-admin.button variant="success" type="submit">Unblock</x-admin.button></form>
    @endif
    @if ($driver->status === 'active' && auth('admin')->user()->hasPermission('drivers', 'edit'))
        <form
            method="POST"
            action="{{ route('admin.drivers.inactivate', $driver) }}"
            class="inline-flex"
            data-jb-confirm="This driver will be blocked and will not be able to use the app. The reason you enter will be visible to them."
            data-jb-confirm-title="Block driver"
            data-jb-confirm-variant="error"
            data-jb-confirm-label="Block"
            data-jb-confirm-requires-reason="Reason for blocking"
        >
            @csrf
            <x-admin.button variant="danger" type="submit">Block</x-admin.button>
        </form>
    @endif
    <x-admin.account-history :histories="$driver->statusHistories" title="Driver account history" />
    @if (auth('admin')->user()->hasPermission('drivers', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.drivers.edit', $driver)">Edit</x-admin.button>
    @endif
@endsection
@section('content')
    @if ($driver->status === 'inactive')
        @include('admin.partials.account-status-banner', [
            'title' => 'Account blocked',
            'reason' => $driver->rejection_reason,
            'emptyReason' => 'No reason recorded.',
            'showAction' => auth('admin')->user()->hasPermission('drivers', 'edit'),
            'actionRoute' => route('admin.drivers.approve', $driver),
            'actionLabel' => 'Unblock driver',
        ])
    @endif
    @if ($driver->status === 'rejected')
        @include('admin.partials.account-status-banner', [
            'title' => 'Application rejected',
            'reason' => $driver->rejection_reason,
            'emptyReason' => 'No rejection reason recorded.',
            'showAction' => auth('admin')->user()->hasPermission('drivers', 'edit'),
            'actionRoute' => route('admin.drivers.approve', $driver),
            'actionLabel' => 'Approve driver',
        ])
    @endif

    <div class="jb-detail-grid">
        <div class="jb-detail-card">
            <h2>Profile</h2>
            <x-admin.actor-profile-header
                :image-url="$driver->profileImageUrl()"
                :title="$driver->name"
                :subtitle="$driver->driver_code"
            >
                @include('admin.components.status-badge', ['status' => $driver->status, 'label' => \App\Support\AdminAccountStatus::labelFor($driver->status)])
            </x-admin.actor-profile-header>
            <dl class="jb-dl">
                <div><dt>Mobile No</dt><dd>{{ $driver->mobile }}</dd></div>
                <div><dt>Email ID</dt><dd>{{ $driver->email ?? '—' }}</dd></div>
                <div><dt>City</dt><dd>{{ $driver->city ?? '—' }}</dd></div>
                <div><dt>Vehicle no.</dt><dd>{{ $driver->vehicle_no ?? '—' }}</dd></div>
                <div><dt>Verified</dt><dd>{{ $driver->is_verified ? 'Yes' : 'No' }}</dd></div>
                <div><dt>Registered</dt><dd>{{ \App\Support\AdminDateTime::formatDate($driver->registered_at) }}</dd></div>
            </dl>
        </div>
        @if ($driver->aadharFrontUrl() || $driver->aadharBackUrl() || $driver->drivingLicenceUrl() || $driver->aadharUrl())
            <div class="jb-detail-card lg:col-span-2">
                <h2>Documents</h2>
                <div class="jb-doc-image-grid">
                    @if ($driver->aadharFrontUrl())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Aadhar front</p>
                            <img src="{{ $driver->aadharFrontUrl() }}" alt="Aadhar front" class="jb-doc-image panel-lightbox-trigger">
                        </div>
                    @elseif ($driver->aadharUrl())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Aadhar</p>
                            <img src="{{ $driver->aadharUrl() }}" alt="Aadhar" class="jb-doc-image panel-lightbox-trigger">
                        </div>
                    @endif
                    @if ($driver->aadharBackUrl())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Aadhar back</p>
                            <img src="{{ $driver->aadharBackUrl() }}" alt="Aadhar back" class="jb-doc-image panel-lightbox-trigger">
                        </div>
                    @endif
                    @if ($driver->drivingLicenceUrl())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Driving licence</p>
                            <img src="{{ $driver->drivingLicenceUrl() }}" alt="Driving licence" class="jb-doc-image panel-lightbox-trigger">
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="jb-detail-card lg:col-span-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="mb-0">Orders</h2>
                @if ($orders->total() > 0)
                    <p class="text-sm text-slate-500">{{ $orders->total() }} {{ Str::plural('order', $orders->total()) }}</p>
                @endif
            </div>
            <div class="jb-table-wrap mt-4">
                <table class="jb-table">
                    <thead><tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-id">Order</th>
                        <th class="jb-col-name">Customer</th>
                        <th class="jb-col-name">Vendor</th>
                        <th class="jb-col-amount">Amount</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-col-date">Placed</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                @include('admin.partials.table-index-cell', ['paginator' => $orders])
                                <td class="jb-col-id font-semibold">{{ $order->order_number }}</td>
                                <td class="jb-col-name max-w-[12rem]">
                                    <span class="block truncate font-medium" title="{{ $order->customer?->name ?? '—' }}">{{ $order->customer?->name ?? '—' }}</span>
                                </td>
                                <td class="jb-col-name max-w-[12rem]">
                                    <span class="block truncate" title="{{ $order->vendor?->brand_name ?? '—' }}">{{ $order->vendor?->brand_name ?? '—' }}</span>
                                </td>
                                <td class="jb-col-amount">₹{{ number_format((float) $order->amount, 2) }}</td>
                                <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $order->status])</td>
                                <td class="jb-col-date text-sm text-slate-500">{{ \App\Support\AdminDateTime::formatDate($order->created_at) }}</td>
                                <td class="jb-table-actions-col">
                                    <div class="jb-actions">
                                        <x-admin.action-btn variant="view" :href="route('admin.orders.show', $order)" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="jb-table-empty">No orders assigned to this driver yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="border-t border-slate-100 px-4 py-3">{{ $orders->links() }}</div>
            @endif
        </div>
    </div>
@endsection

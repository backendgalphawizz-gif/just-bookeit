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
    @if ($driver->status === 'active' && auth('admin')->user()->hasPermission('drivers', 'edit'))
        <form method="POST" action="{{ route('admin.drivers.suspend', $driver) }}">@csrf<x-admin.button variant="danger" type="submit">Suspend</x-admin.button></form>
    @endif
    <x-admin.account-history :histories="$driver->statusHistories" title="Driver account history" />
    @if (auth('admin')->user()->hasPermission('drivers', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.drivers.edit', $driver)">Edit</x-admin.button>
    @endif
@endsection
@section('content')
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
                @include('admin.components.status-badge', ['status' => $driver->status])
            </x-admin.actor-profile-header>
            <dl class="jb-dl">
                <div><dt>Mobile No</dt><dd>{{ $driver->mobile }}</dd></div>
                <div><dt>Email ID</dt><dd>{{ $driver->email ?? '—' }}</dd></div>
                <div><dt>City</dt><dd>{{ $driver->city ?? '—' }}</dd></div>
                <div><dt>Vehicle no.</dt><dd>{{ $driver->vehicle_no ?? '—' }}</dd></div>
                <div><dt>Verified</dt><dd>{{ $driver->is_verified ? 'Yes' : 'No' }}</dd></div>
                <div><dt>Registered</dt><dd>{{ $driver->registered_at?->format('M d, Y') ?? '—' }}</dd></div>
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
    </div>
@endsection

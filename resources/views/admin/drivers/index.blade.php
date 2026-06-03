@extends('admin.layouts.app')
@section('title', 'Drivers')
@section('page_title', 'Drivers')
@section('page_subtitle', 'Delivery partners and driver onboarding')

@php $canCreateDriver = auth('admin')->user()->isSuperAdmin() || auth('admin')->user()->hasPermission('drivers', 'create'); @endphp

@section('content')
    <form method="GET" class="jb-filters">
        <div class="jb-filters-grid">
            <div class="jb-filters-field jb-filters-field--wide">
                <label class="jb-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, mobile no, code..." class="jb-input">
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
            @include('admin.partials.date-filter')
            @include('admin.partials.filters-end', ['resetUrl' => route('admin.drivers.index')])
        </div>
    </form>

    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $drivers->total() }} drivers</p>
            @if ($canCreateDriver)
                <x-admin.button variant="primary" size="sm" :href="route('admin.drivers.create')">+ Add Driver</x-admin.button>
            @endif
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table jb-table--balanced">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-id">Driver ID</th>
                        <th class="jb-col-name">Name</th>
                        <th>Mobile No</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drivers as $driver)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $drivers])
                            <td class="jb-col-id font-mono text-xs">{{ $driver->driver_code }}</td>
                            <td class="jb-col-name">
                                <div class="jb-actor-cell">
                                    @include('admin.partials.actor-avatar', [
                                        'imageUrl' => $driver->profileImageUrl(),
                                        'label' => $driver->name,
                                    ])
                                    <span class="font-semibold">{{ $driver->name }}</span>
                                </div>
                            </td>
                            <td>{{ $driver->mobile }}</td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $driver->status])</td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    <x-admin.action-btn variant="view" :href="route('admin.drivers.show', $driver)" />
                                    @if ($driver->status === 'pending' && auth('admin')->user()->hasPermission('drivers', 'edit'))
                                        <form method="POST" action="{{ route('admin.drivers.approve', $driver) }}" class="jb-action-form">@csrf
                                            <x-admin.action-btn variant="approve" type="submit" />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="jb-table-empty">No drivers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($drivers->hasPages()) {{ $drivers->links() }} @endif
    </div>
@endsection

@extends('admin.layouts.app')
@section('title', $driver->name)
@section('page_title', $driver->name)
@section('page_subtitle', $driver->driver_code)
@section('header_actions')
    @if ($driver->status === 'pending' && auth('admin')->user()->hasPermission('drivers', 'edit'))
        <form method="POST" action="{{ route('admin.drivers.approve', $driver) }}">@csrf<x-admin.button variant="success" type="submit">Approve</x-admin.button></form>
        <form method="POST" action="{{ route('admin.drivers.reject', $driver) }}">@csrf<x-admin.button variant="danger" type="submit">Reject</x-admin.button></form>
    @endif
    @if ($driver->status === 'active' && auth('admin')->user()->hasPermission('drivers', 'edit'))
        <form method="POST" action="{{ route('admin.drivers.suspend', $driver) }}">@csrf<x-admin.button variant="danger" type="submit">Suspend</x-admin.button></form>
    @endif
    @if (auth('admin')->user()->hasPermission('drivers', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.drivers.edit', $driver)">Edit</x-admin.button>
    @endif
@endsection
@section('content')
    <div class="jb-detail-grid">
        <div class="jb-detail-card">
            <dl class="jb-detail-list">
                <div><dt>Mobile</dt><dd>{{ $driver->mobile }}</dd></div>
                <div><dt>Email</dt><dd>{{ $driver->email ?? '—' }}</dd></div>
                <div><dt>City</dt><dd>{{ $driver->city ?? '—' }}</dd></div>
                <div><dt>Status</dt><dd>@include('admin.components.status-badge', ['status' => $driver->status])</dd></div>
                <div><dt>Verified</dt><dd>{{ $driver->is_verified ? 'Yes' : 'No' }}</dd></div>
                <div><dt>Registered</dt><dd>{{ $driver->registered_at?->format('M d, Y') ?? '—' }}</dd></div>
            </dl>
        </div>
        @if ($driver->aadharUrl())
            <div class="jb-card overflow-hidden">
                <img src="{{ $driver->aadharUrl() }}" alt="Aadhar" class="w-full max-h-80 object-contain bg-slate-50 p-4">
            </div>
        @endif
    </div>
@endsection

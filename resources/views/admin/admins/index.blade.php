@extends('admin.layouts.app')
@section('title', 'Admin Users')
@section('page_title', 'Admin Users')
@section('page_subtitle', 'Team accounts and role assignments')

@php
    $canCreateAdmin = auth('admin')->user()->isSuperAdmin() || auth('admin')->user()->hasPermission('admins', 'create');
@endphp

@section('content')
    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $admins->total() }} admin users</p>
            @if ($canCreateAdmin)
                <x-admin.button variant="primary" size="sm" :href="route('admin.admins.create')">+ Add Admin</x-admin.button>
            @endif
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table jb-table--balanced">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-name">Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>City</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-col-date">Last login</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($admins as $admin)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $admins])
                            <td class="jb-col-name font-semibold">{{ $admin->name }}</td>
                            <td class="font-mono text-xs">{{ $admin->username }}</td>
                            <td>{{ $admin->email }}</td>
                            <td>{{ $admin->role?->name ?? '—' }}</td>
                            <td class="text-sm text-slate-600">
                                @if ($admin->role?->slug === 'super_admin')
                                    All cities
                                @else
                                    {{ $admin->assignedCities->first()?->city ?? '—' }}
                                @endif
                            </td>
                            <td class="jb-col-status">@include('admin.components.status-badge', ['status' => $admin->status])</td>
                            <td class="jb-col-date text-sm text-slate-500">{{ $admin->last_login_at?->format('M d, Y h:i A') ?? '—' }}</td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    @if (auth('admin')->user()->hasPermission('admins', 'edit'))
                                        <x-admin.action-btn variant="edit" :href="route('admin.admins.edit', $admin)" />
                                    @endif
                                    @if (auth('admin')->user()->hasPermission('admins', 'delete') && $admin->id !== auth('admin')->id())
                                        <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}" class="jb-action-form">
                                            @csrf @method('DELETE')
                                            <x-admin.action-btn variant="delete" type="submit" confirm="Delete this admin user?" />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="jb-table-empty">No admin users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($admins->hasPages()) {{ $admins->links() }} @endif
    </div>
@endsection

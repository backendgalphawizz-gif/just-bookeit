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
            <div class="flex flex-wrap items-center gap-2">
                <x-admin.export-dropdown module="admins" :params="[]" />
                @if ($canCreateAdmin)
                    <x-admin.button variant="primary" size="sm" :href="route('admin.admins.create')">+ Add Admin</x-admin.button>
                @endif
            </div>
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table jb-table--balanced jb-table--wide">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-name">Name</th>
                        <th class="jb-col-username">Username</th>
                        <th class="jb-col-email">Email ID</th>
                        <th class="jb-col-role">Role</th>
                        <th class="jb-col-city">City</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-col-date">Last login</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($admins as $admin)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => $admins])
                            <td class="jb-col-name">
                                <span class="block truncate font-semibold" title="{{ $admin->name }}">{{ $admin->name }}</span>
                            </td>
                            <td class="jb-col-username">
                                <span class="block truncate font-mono text-xs" title="{{ $admin->username }}">{{ $admin->username }}</span>
                            </td>
                            <td class="jb-col-email">
                                <span class="block truncate" title="{{ $admin->email }}">{{ $admin->email }}</span>
                            </td>
                            <td class="jb-col-role">
                                <span class="block truncate" title="{{ $admin->role?->name ?? '—' }}">{{ $admin->role?->name ?? '—' }}</span>
                            </td>
                            @php
                                $adminCityLabel = $admin->role?->slug === 'super_admin'
                                    ? 'All cities'
                                    : ($admin->assignedCities->first()?->city ?? '—');
                            @endphp
                            <td class="jb-col-city text-sm text-slate-600">
                                <span class="block truncate" title="{{ $adminCityLabel }}">{{ $adminCityLabel }}</span>
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

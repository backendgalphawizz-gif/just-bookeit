@extends('admin.layouts.app')
@section('title', 'Permissions')
@section('page_title', 'Permissions')
@section('page_subtitle', 'Platform modules and role access matrix')

@php
    $canCreateAdmin = auth('admin')->user()->isSuperAdmin() || auth('admin')->user()->hasPermission('admins', 'create');
@endphp

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="jb-stat-card">
            <p class="jb-stat-label">Modules</p>
            <p class="jb-stat-value">{{ $permissions->count() }}</p>
        </div>
        <div class="jb-stat-card">
            <p class="jb-stat-label">Roles</p>
            <p class="jb-stat-value">{{ $roles->count() }}</p>
        </div>
        <div class="jb-stat-card">
            <p class="jb-stat-label">Admin users</p>
            <p class="jb-stat-value">{{ \App\Models\Admin::query()->count() }}</p>
        </div>
    </div>

    <div class="jb-card mb-6">
        <div class="jb-card-header flex flex-wrap items-center justify-between gap-2">
            <p class="jb-card-header-title">Module permissions</p>
            @if ($canCreateAdmin)
                <div class="flex flex-wrap gap-2">
                    <x-admin.button variant="primary" size="sm" :href="route('admin.admins.create')">+ Add Admin</x-admin.button>
                    <x-admin.button variant="secondary" size="sm" :href="route('admin.roles.create')">+ Add Role</x-admin.button>
                </div>
            @elseif (auth('admin')->user()->hasPermission('admins', 'edit'))
                <x-admin.button variant="secondary" size="sm" :href="route('admin.roles.index')">Manage roles</x-admin.button>
            @endif
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table text-sm">
                <thead>
                    <tr>
                        <th class="jb-col-name">Module</th>
                        <th>Slug</th>
                        <th class="jb-col-name">Assigned roles</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($permissions as $permission)
                        <tr>
                            <td class="jb-col-name font-semibold">{{ $permission->name }}</td>
                            <td class="font-mono text-xs text-slate-500">{{ $permission->slug }}</td>
                            <td>
                                @if ($permission->roles->isEmpty())
                                    <span class="text-xs text-slate-400">No roles</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($permission->roles as $role)
                                            <span class="jb-badge bg-slate-100 text-slate-700">{{ $role->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">Role access matrix</p>
        </div>
        <div class="jb-table-wrap overflow-x-auto">
            <table class="jb-table text-xs">
                <thead>
                    <tr>
                        <th class="jb-col-name jb-table-sticky-col">Role</th>
                        @foreach ($permissions as $permission)
                            <th class="min-w-[5rem] text-center whitespace-nowrap px-2" title="{{ $permission->name }}">{{ Str::limit($permission->slug, 10) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td class="jb-col-name jb-table-sticky-col font-semibold">
                                {{ $role->name }}
                                @if ($role->slug === 'super_admin')
                                    <span class="jb-badge bg-amber-100 text-amber-800 ml-1">Full</span>
                                @endif
                            </td>
                            @foreach ($permissions as $permission)
                                @php
                                    $pivot = $role->permissions->firstWhere('id', $permission->id)?->pivot;
                                @endphp
                                <td class="text-center px-1">
                                    @if ($role->slug === 'super_admin')
                                        <span class="text-emerald-600" title="Full access">✓</span>
                                    @elseif ($pivot?->can_view)
                                        <span class="text-emerald-600" title="View{{ $pivot->can_edit ? ', Edit' : '' }}{{ $pivot->can_create ? ', Create' : '' }}">✓</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

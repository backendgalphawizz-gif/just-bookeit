@extends('admin.layouts.app')
@section('title', 'Roles')
@section('page_title', 'Roles & Permissions')
@section('page_subtitle', 'Define what each admin role can access in the panel')

@php
    $canCreateRole = auth('admin')->user()->isSuperAdmin() || auth('admin')->user()->hasPermission('admins', 'create');
@endphp

@section('content')
    <div class="jb-card">
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $roles->count() }} roles</p>
            @if ($canCreateRole)
                <x-admin.button variant="primary" size="sm" :href="route('admin.roles.create')">+ Add Role</x-admin.button>
            @endif
        </div>
        <div class="jb-table-wrap">
            <table class="jb-table jb-table--balanced">
                <thead>
                    <tr>
                        @include('admin.partials.table-index-header')
                        <th class="jb-col-name">Role</th>
                        <th>Slug</th>
                        <th class="text-center">Admins</th>
                        <th class="text-center">Modules</th>
                        <th class="jb-col-status">Status</th>
                        <th class="jb-table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            @include('admin.partials.table-index-cell', ['paginator' => null, 'index' => $loop->iteration])
                            <td class="jb-col-name">
                                <p class="font-semibold">{{ $role->name }}</p>
                                <p class="text-xs text-slate-500">{{ $role->description }}</p>
                            </td>
                            <td class="font-mono text-xs">{{ $role->slug }}</td>
                            <td class="text-center">{{ $role->admins_count }}</td>
                            <td class="text-center">
                                @if ($role->slug === 'super_admin')
                                    All
                                @else
                                    {{ $role->permissions->filter(fn ($p) => $p->pivot->can_view)->count() }}
                                @endif
                            </td>
                            <td class="jb-col-status">
                                @if ($role->is_active)
                                    <span class="jb-badge bg-emerald-100 text-emerald-800">Active</span>
                                @else
                                    <span class="jb-badge bg-slate-100 text-slate-600">Inactive</span>
                                @endif
                            </td>
                            <td class="jb-table-actions-col">
                                <div class="jb-actions">
                                    @if (auth('admin')->user()->hasPermission('admins', 'edit'))
                                        <x-admin.action-btn variant="edit" :href="route('admin.roles.edit', $role)" />
                                    @endif
                                    @if (auth('admin')->user()->hasPermission('admins', 'delete') && $role->slug !== 'super_admin')
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="jb-action-form">
                                            @csrf @method('DELETE')
                                            <x-admin.action-btn variant="delete" type="submit" confirm="Delete this role?" />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="jb-table-empty">No roles defined.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

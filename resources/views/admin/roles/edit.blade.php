@extends('admin.layouts.app')
@section('title', 'Edit Role')
@section('page_title', 'Edit Role')
@section('page_subtitle', $role->name)
@section('header_actions')
    <x-admin.button variant="secondary" :href="route('admin.roles.index')">← Back</x-admin.button>
@endsection
@section('content')
    <div class="jb-card max-w-5xl">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                @csrf @method('PUT')
                <div class="jb-form-grid">
                    @include('admin.roles._form', compact('role', 'permissions', 'rolePermissions', 'isSuperAdmin'))
                </div>
                <div class="jb-form-actions">
                    <x-admin.button variant="primary" type="submit">Update Role</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.roles.index')">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection

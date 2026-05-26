@extends('admin.layouts.app')
@section('title', 'Add Role')
@section('page_title', 'Add Role')
@section('header_actions')
    <x-admin.button variant="secondary" :href="route('admin.roles.index')">← Back</x-admin.button>
@endsection
@section('content')
    <div class="jb-card max-w-5xl">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf
                <div class="jb-form-grid">
                    @include('admin.roles._form', ['permissions' => $permissions, 'rolePermissions' => $rolePermissions])
                </div>
                <div class="jb-form-actions">
                    <x-admin.button variant="primary" type="submit">Save Role</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.roles.index')">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection

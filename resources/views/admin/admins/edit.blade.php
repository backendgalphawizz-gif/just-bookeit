@extends('admin.layouts.app')
@section('title', 'Edit Admin')
@section('page_title', 'Edit Admin User')
@section('page_subtitle', $admin->name)
@section('header_actions')
    <x-admin.button variant="secondary" :href="route('admin.admins.index')">← Back</x-admin.button>
@endsection
@section('content')
    <div class="jb-card">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.admins.update', $admin) }}">
                @csrf @method('PUT')
                <div class="jb-form-grid">@include('admin.admins._form', compact('admin', 'roles', 'cities'))</div>
                <div class="jb-form-actions">
                    <x-admin.button variant="primary" type="submit">Update Admin</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.admins.index')">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection

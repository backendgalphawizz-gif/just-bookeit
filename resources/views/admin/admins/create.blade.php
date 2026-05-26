@extends('admin.layouts.app')
@section('title', 'Add Admin')
@section('page_title', 'Add Admin User')
@section('header_actions')
    <x-admin.button variant="secondary" :href="route('admin.admins.index')">← Back</x-admin.button>
@endsection
@section('content')
    <div class="jb-card max-w-3xl">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.admins.store') }}">
                @csrf
                <div class="jb-form-grid">@include('admin.admins._form', ['roles' => $roles])</div>
                <div class="jb-form-actions">
                    <x-admin.button variant="primary" type="submit">Save Admin</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.admins.index')">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection

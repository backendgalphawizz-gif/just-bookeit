@extends('admin.layouts.app')
@section('title', 'Add Admin')
@section('page_title', 'Add Admin User')
@section('back_href', route('admin.admins.index'))
@section('content')
    <div class="jb-card">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.admins.store') }}">
                @csrf
                <div class="jb-form-grid">@include('admin.admins._form', ['roles' => $roles, 'cities' => $cities])</div>
                <div class="jb-form-actions">
                    <x-admin.button variant="primary" type="submit">Save Admin</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.admins.index')">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection

@extends('admin.layouts.app')
@section('title', 'Add Customer')
@section('page_title', 'Add Customer')
@section('page_subtitle', 'Create a new customer account')

@section('header_actions')
    <x-admin.button variant="secondary" :href="route('admin.customers.index')">← Back to list</x-admin.button>
@endsection

@section('content')
    <div class="jb-card">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.customers.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="jb-form-grid">@include('admin.customers._form')</div>
                <div class="jb-form-actions">
                    <x-admin.button variant="primary" type="submit">Save Customer</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.customers.index')">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection

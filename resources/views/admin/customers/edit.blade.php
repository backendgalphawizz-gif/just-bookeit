@extends('admin.layouts.app')
@section('title', 'Edit Customer')
@section('page_title', 'Edit Customer')
@section('page_subtitle', $customer->customer_code)
@section('back_href', route('admin.customers.index'))

@section('content')
    <div class="jb-card">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.customers.update', $customer) }}" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="jb-form-grid">@include('admin.customers._form')</div>
                <div class="jb-form-actions">
                    <x-admin.button variant="primary" type="submit">Update Customer</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.customers.index')">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection

@extends('admin.layouts.app')
@section('title', 'Edit Vendor')
@section('page_title', 'Edit Vendor')
@section('header_actions')<x-admin.button variant="secondary" :href="route('admin.vendors.show', $vendor)">← Back</x-admin.button>@endsection
@section('content')
    <div class="jb-card max-w-4xl"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.vendors.update', $vendor) }}" enctype="multipart/form-data">@csrf @method('PUT')
            <div class="jb-form-grid">@include('admin.vendors._form')</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Update</x-admin.button><x-admin.button variant="secondary" :href="route('admin.vendors.show', $vendor)">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection

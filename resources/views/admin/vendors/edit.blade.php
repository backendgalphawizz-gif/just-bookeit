@extends('admin.layouts.app')
@section('title', 'Edit Vendor')
@section('page_title', 'Edit Vendor')
@section('back_href', route('admin.vendors.index'))
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.vendors.update', $vendor) }}" enctype="multipart/form-data">@csrf @method('PUT')
            <div class="jb-form-grid">@include('admin.vendors._form', ['vendor' => $vendor, 'categories' => $categories])</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Update</x-admin.button><x-admin.button variant="secondary" :href="route('admin.vendors.index')">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection

@extends('admin.layouts.app')
@section('title', 'Add Vendor')
@section('page_title', 'Add Vendor')
@section('header_actions')<x-admin.button variant="secondary" :href="route('admin.vendors.index')">← Back</x-admin.button>@endsection
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.vendors.store') }}" enctype="multipart/form-data">@csrf
            <div class="jb-form-grid">@include('admin.vendors._form', ['categories' => $categories])</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Save Vendor</x-admin.button><x-admin.button variant="secondary" :href="route('admin.vendors.index')">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection

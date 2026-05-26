@extends('admin.layouts.app')
@section('title', 'Edit Banner')
@section('page_title', 'Edit Banner')
@section('header_actions')<x-admin.button variant="secondary" :href="route('admin.banners.index')">← Back</x-admin.button>@endsection
@section('content')
    <div class="jb-card max-w-4xl"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data">@csrf @method('PUT')
            <div class="jb-form-grid">@include('admin.banners._form')</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Update</x-admin.button><x-admin.button variant="secondary" :href="route('admin.banners.index')">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection

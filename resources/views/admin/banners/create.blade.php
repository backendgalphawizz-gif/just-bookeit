@extends('admin.layouts.app')
@section('title', 'Add Banner')
@section('page_title', 'Add Banner')
@section('header_actions')<x-admin.button variant="secondary" :href="route('admin.banners.index')">← Back</x-admin.button>@endsection
@section('content')
    <div class="jb-card max-w-4xl"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">@csrf
            <div class="jb-form-grid">@include('admin.banners._form')</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Save Banner</x-admin.button><x-admin.button variant="secondary" :href="route('admin.banners.index')">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection

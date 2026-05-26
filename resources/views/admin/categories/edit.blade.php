@extends('admin.layouts.app')
@section('title', 'Edit Category')
@section('page_title', 'Edit Category')
@section('header_actions')<x-admin.button variant="secondary" :href="route('admin.categories.index')">← Back</x-admin.button>@endsection
@section('content')
    <div class="jb-card max-w-3xl"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.categories.update', $category) }}">@csrf @method('PUT')
            <div class="jb-form-grid">@include('admin.categories._form')</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Update</x-admin.button><x-admin.button variant="secondary" :href="route('admin.categories.index')">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection

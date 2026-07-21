@extends('admin.layouts.app')
@section('title', 'Edit Size')
@section('page_title', 'Edit Size')
@section('page_subtitle', $size->name)
@section('back_href', route('admin.sizes.index'))
@section('content')
    <div class="jb-card">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.sizes.update', $size) }}">
                @csrf
                @method('PUT')
                <div class="jb-form-grid">@include('admin.sizes._form')</div>
                <div class="jb-form-actions">
                    <x-admin.button variant="primary" type="submit">Update Size</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.sizes.index')">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection

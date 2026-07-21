@extends('admin.layouts.app')
@section('title', 'Add Size')
@section('page_title', 'Add Size')
@section('page_subtitle', 'Create a size option for rental dress variants')
@section('back_href', route('admin.sizes.index'))
@section('content')
    <div class="jb-card">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.sizes.store') }}">
                @csrf
                <div class="jb-form-grid">@include('admin.sizes._form')</div>
                <div class="jb-form-actions">
                    <x-admin.button variant="primary" type="submit">Save Size</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.sizes.index')">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection

@extends('admin.layouts.app')
@section('title', 'Add Color')
@section('page_title', 'Add Color')
@section('page_subtitle', 'Create a color option for rental dress variants')
@section('back_href', route('admin.colors.index'))
@section('content')
    <div class="jb-card">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.colors.store') }}">
                @csrf
                <div class="jb-form-grid">@include('admin.colors._form')</div>
                <div class="jb-form-actions">
                    <x-admin.button variant="primary" type="submit">Save Color</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.colors.index')">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection

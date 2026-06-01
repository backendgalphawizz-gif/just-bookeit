@extends('admin.layouts.app')
@section('title', 'Add Category')
@section('page_title', 'Add Category')
@section('header_actions')<x-admin.button variant="secondary" :href="route('admin.categories.index')">← Back</x-admin.button>@endsection
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.categories.store') }}">@csrf
            <div class="jb-form-grid">@include('admin.categories._form')</div>
            <div class="jb-form-actions"><x-admin.button variant="primary" type="submit">Save</x-admin.button><x-admin.button variant="secondary" :href="route('admin.categories.index')">Cancel</x-admin.button></div>
        </form>
    </div></div>
@endsection

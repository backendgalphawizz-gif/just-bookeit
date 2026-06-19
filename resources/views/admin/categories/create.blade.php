@extends('admin.layouts.app')
@section('title', 'Add Category')
@php
    $indexType = $type === \App\Models\Category::TYPE_SERVICE ? $type : 'catalog';
    $labels = [
        \App\Models\Category::TYPE_MAIN => 'category',
        \App\Models\Category::TYPE_SUB => 'sub-category',
        \App\Models\Category::TYPE_SERVICE => 'service category',
    ];
@endphp
@section('page_title', 'Add ' . ($labels[$type] ?? 'category'))
@section('page_subtitle', match ($type) {
    \App\Models\Category::TYPE_MAIN => 'Men, Women, Kids, and other top-level groups',
    \App\Models\Category::TYPE_SUB => 'Sarees, Suits, and other groups under a category',
    default => 'Fashion Designer, Rented Dress, and other vendor services',
})
@section('back_href', route('admin.categories.index', ['type' => $indexType]))
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">@csrf
            <div class="jb-form-grid">@include('admin.categories._form', compact('parents', 'type', 'serviceCategories'))</div>
            <div class="jb-form-actions">
                <x-admin.button variant="primary" type="submit">Save</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.categories.index', ['type' => $indexType])">Cancel</x-admin.button>
            </div>
        </form>
    </div></div>
@endsection

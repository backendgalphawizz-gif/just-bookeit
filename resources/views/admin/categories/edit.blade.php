@extends('admin.layouts.app')
@section('title', 'Edit Category')
@section('page_title', 'Edit ' . $category->name)
@section('page_subtitle', \App\Models\Category::typeLabel($type))
@section('back_href', route('admin.categories.index', ['type' => $type]))
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data">@csrf @method('PUT')
            <div class="jb-form-grid">@include('admin.categories._form', compact('category', 'parents', 'type'))</div>
            <div class="jb-form-actions">
                <x-admin.button variant="primary" type="submit">Update</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.categories.index', ['type' => $type])">Cancel</x-admin.button>
            </div>
        </form>
    </div></div>
@endsection

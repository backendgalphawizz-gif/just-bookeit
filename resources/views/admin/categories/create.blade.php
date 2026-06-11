@extends('admin.layouts.app')
@section('title', 'Add Category')
@section('page_title', 'Add ' . \App\Models\Category::typeLabel($type))
@section('page_subtitle', $type === \App\Models\Category::TYPE_MAIN ? 'Men, Women, Kids, and other groups' : 'Fashion Designer, Rented Dress, and other vendor services')
@section('back_href', route('admin.categories.index', ['type' => $type]))
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">@csrf
            <div class="jb-form-grid">@include('admin.categories._form', compact('parents', 'type'))</div>
            <div class="jb-form-actions">
                <x-admin.button variant="primary" type="submit">Save</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.categories.index', ['type' => $type])">Cancel</x-admin.button>
            </div>
        </form>
    </div></div>
@endsection

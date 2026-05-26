@extends('admin.layouts.app')
@section('title', 'Add Driver')
@section('page_title', 'Add Driver')
@section('header_actions')
    <x-admin.button variant="secondary" :href="route('admin.drivers.index')">← Back</x-admin.button>
@endsection
@section('content')
    <div class="jb-card max-w-3xl"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.drivers.store') }}" enctype="multipart/form-data">@csrf
            <div class="jb-form-grid">@include('admin.drivers._form')</div>
            <div class="jb-form-actions">
                <x-admin.button variant="primary" type="submit">Save Driver</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.drivers.index')">Cancel</x-admin.button>
            </div>
        </form>
    </div></div>
@endsection

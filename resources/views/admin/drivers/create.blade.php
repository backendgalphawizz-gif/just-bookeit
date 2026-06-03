@extends('admin.layouts.app')
@section('title', 'Add Driver')
@section('page_title', 'Add Driver')
@section('back_href', route('admin.drivers.index'))
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.drivers.store') }}" enctype="multipart/form-data">@csrf
            <div class="jb-form-grid">@include('admin.drivers._form')</div>
            <div class="jb-form-actions">
                <x-admin.button variant="primary" type="submit">Save Driver</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.drivers.index')">Cancel</x-admin.button>
            </div>
        </form>
    </div></div>
@endsection

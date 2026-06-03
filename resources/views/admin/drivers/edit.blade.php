@extends('admin.layouts.app')
@section('title', 'Edit Driver')
@section('page_title', 'Edit Driver')
@section('page_subtitle', $driver->name)
@section('back_href', route('admin.drivers.index'))
@section('content')
    <div class="jb-card"><div class="jb-card-body">
        <form method="POST" action="{{ route('admin.drivers.update', $driver) }}" enctype="multipart/form-data">@csrf @method('PUT')
            <div class="jb-form-grid">@include('admin.drivers._form', compact('driver'))</div>
            <div class="jb-form-actions">
                <x-admin.button variant="primary" type="submit">Update</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.drivers.index')">Cancel</x-admin.button>
            </div>
        </form>
    </div></div>
@endsection

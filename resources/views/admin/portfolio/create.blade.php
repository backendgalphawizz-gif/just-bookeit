@extends('admin.layouts.app')

@section('title', 'Add Product')
@section('page_title', 'Add product')
@section('page_subtitle', 'Same fields as the vendor app — items vendors sell or rent')
@section('back_href', route('admin.portfolio.index', array_filter(['type' => $type ?? null])))

@section('content')
    <div class="jb-card">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.portfolio.store') }}" enctype="multipart/form-data">
                @csrf
                @include('admin.portfolio._form')

                <div class="jb-form-actions mt-6 border-t border-slate-100 pt-6">
                    <x-admin.button variant="primary" type="submit">Create product</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.portfolio.index', array_filter(['type' => $type ?? null]))">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection

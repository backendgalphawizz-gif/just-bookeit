@extends('admin.layouts.app')
@section('title', 'Edit Banner')
@section('page_title', 'Edit Banner')
@section('page_subtitle', 'Preview on website and mobile app before publishing')
@section('back_href', route('admin.banners.index'))
@section('content')
    <form
        method="POST"
        action="{{ route('admin.banners.update', $banner) }}"
        enctype="multipart/form-data"
        x-data="bannerPreviewForm({ imageUrl: @js($banner->image_url), audience: @js($banner->audience) })"
    >
        @csrf
        @method('PUT')
        <div class="jb-banner-editor">
            <div class="jb-card">
                <div class="jb-card-body">
                    <div class="jb-form-grid">@include('admin.banners._form', ['audience' => $banner->audience])</div>
                    <div class="jb-form-actions">
                        <x-admin.button variant="primary" type="submit">Update</x-admin.button>
                        <x-admin.button variant="secondary" :href="route('admin.banners.index')">Cancel</x-admin.button>
                    </div>
                </div>
            </div>
            @include('admin.banners.partials.preview')
        </div>
    </form>
    @include('admin.banners.partials.preview-script')
@endsection

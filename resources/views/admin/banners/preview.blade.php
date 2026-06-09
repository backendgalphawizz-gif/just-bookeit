@extends('admin.layouts.app')
@section('title', 'Preview Banner')
@section('page_title', 'Banner Preview')
@section('page_subtitle', $banner->title.' · '.App\Models\Banner::audienceLabel($banner->audience))
@section('back_href', route('admin.banners.edit', $banner))
@section('header_actions')
    <x-admin.button variant="secondary" :href="route('admin.banners.edit', $banner)">Edit banner</x-admin.button>
@endsection
@section('content')
    <div
        class="jb-banner-editor"
        style="grid-template-columns: minmax(0, 1fr); max-width: 520px;"
        x-data="bannerPreviewForm({
            static: true,
            imageUrl: @js($banner->image_url),
            title: @js($banner->title),
            subtitle: @js($banner->subtitle ?? ''),
            redirectUrl: @js($banner->redirect_url ?? ''),
            isActive: @js($banner->is_active),
            audience: @js($banner->audience),
        })"
    >
        @include('admin.banners.partials.preview')
    </div>
    @include('admin.banners.partials.preview-script')
@endsection

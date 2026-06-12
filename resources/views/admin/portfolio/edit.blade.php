@extends('admin.layouts.app')

@section('title', 'Edit Product')
@section('page_title', 'Edit product')
@section('page_subtitle', $portfolio->vendor->brand_name.' · '.$portfolio->title)
@section('back_href', route('admin.portfolio.show', $portfolio))

@section('content')
    <!-- Hidden forms for image deletion -->
    @if ($portfolio->relationLoaded('images') && $portfolio->images->isNotEmpty())
        @foreach ($portfolio->images as $image)
            <form id="delete-image-{{ $image->id }}" method="POST" action="{{ route('admin.portfolio.images.destroy', [$portfolio, $image]) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif

    <div class="jb-card">
        <div class="jb-card-body">
            <form method="POST" action="{{ route('admin.portfolio.update', $portfolio) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.portfolio._form')

                <div class="jb-form-actions mt-6 border-t border-slate-100 pt-6">
                    <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.portfolio.show', $portfolio)">Cancel</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection

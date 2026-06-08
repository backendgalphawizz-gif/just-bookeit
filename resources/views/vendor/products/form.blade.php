@extends('vendor.layouts.app')

@section('title', ($item->exists ? 'Edit' : 'Add').' '.$typeLabel)

@section('content')
<a href="{{ route('vendor.products.index', ['type' => $type]) }}" class="vp-back-link">← Back to {{ $typeLabel }}</a>

<div class="vp-page-head">
    <h1 class="vp-page-title">{{ $item->exists ? 'Edit' : 'Add' }} {{ $typeLabel }}</h1>
</div>

<div class="vp-card vp-card-pad" style="max-width:640px;">
    <form method="POST" action="{{ $item->exists ? route('vendor.products.update', $item) : route('vendor.products.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($item->exists) @method('PUT') @endif
        <input type="hidden" name="type" value="{{ $type }}">

        <div class="vp-field">
            <label class="vp-label">Title <span class="vp-required">*</span></label>
            <input type="text" name="title" class="vp-input @error('title') vp-input--error @enderror" value="{{ old('title', $item->title) }}" required maxlength="255" data-vp-restrict="title">
            @error('title')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
        <div class="vp-field">
            <label class="vp-label">Description</label>
            <textarea name="description" class="vp-textarea @error('description') vp-textarea--error @enderror" rows="4" maxlength="5000" data-vp-restrict="text">{{ old('description', $item->description) }}</textarea>
            @error('description')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
        <div class="vp-field">
            <label class="vp-label">Image {{ $item->exists ? '(optional)' : '' }} @if(!$item->exists)<span class="vp-required">*</span>@endif</label>
            @if ($item->displayImageUrl())
                <img src="{{ url($item->displayImageUrl()) }}" alt="" class="vp-thumb panel-lightbox-trigger" style="width:80px;height:80px;margin-bottom:.65rem;">
            @endif
            <input type="file" name="image" class="vp-file" accept="image/jpeg,image/jpg,image/png,image/webp" {{ $item->exists ? '' : 'required' }}>
            <p class="vp-field-hint">JPEG, PNG or WebP, max 4 MB</p>
            @error('image')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="vp-btn vp-btn--primary">{{ $item->exists ? 'Update' : 'Submit for Approval' }}</button>
    </form>
</div>
@endsection

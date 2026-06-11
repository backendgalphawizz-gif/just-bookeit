@php
    $isCreate = ! $item->exists;
@endphp

<input type="hidden" name="type" value="{{ $type }}">

<div class="vp-form-grid">
    <div class="vp-field">
        <label class="vp-label">Title <span class="vp-required">*</span></label>
        <input type="text" name="title" class="vp-input @error('title') vp-input--error @enderror" value="{{ old('title', $item->title) }}" required maxlength="255" data-vp-restrict="title">
        @error('title')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-field">
        <label class="vp-label">Audience</label>
        <select name="audience" class="vp-select">
            @foreach (['women' => 'Women', 'men' => 'Men', 'kids' => 'Kids'] as $value => $label)
                <option value="{{ $value }}" @selected(old('audience', $item->audience ?? 'women') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('audience')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-field">
        <label class="vp-label">Price per day (₹) @if($isCreate)<span class="vp-required">*</span>@endif</label>
        <input type="number" name="price_per_day" class="vp-input @error('price_per_day') vp-input--error @enderror" value="{{ old('price_per_day', $item->price_per_day) }}" min="0" step="0.01" {{ $isCreate ? 'required' : '' }}>
        @error('price_per_day')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-field">
        <label class="vp-label">Advance amount (₹)</label>
        <input type="number" name="advance_amount" class="vp-input @error('advance_amount') vp-input--error @enderror" value="{{ old('advance_amount', $item->advance_amount) }}" min="0" step="0.01">
        @error('advance_amount')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-field vp-field--full">
        <label class="vp-label">Description</label>
        <textarea name="description" class="vp-textarea @error('description') vp-textarea--error @enderror" rows="4" maxlength="5000" data-vp-restrict="text">{{ old('description', $item->description) }}</textarea>
        @error('description')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-field vp-field--full vp-form-section">
        <label class="vp-label">Primary image @if($isCreate)<span class="vp-required">*</span>@else<span class="vp-field-hint"> (optional)</span>@endif</label>
        <p class="vp-field-hint">Main cover photo shown in listings.</p>
        @if ($item->displayImageUrl())
            <img src="{{ url($item->displayImageUrl()) }}" alt="" class="vp-product-preview panel-lightbox-trigger">
        @endif
        <input type="file" name="image" class="vp-file" accept="image/jpeg,image/jpg,image/png,image/webp" {{ $isCreate ? 'required' : '' }}>
        <p class="vp-field-hint">JPEG, PNG or WebP — max 20 MB</p>
        @error('image')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-field vp-field--full vp-form-section">
        <label class="vp-label">Gallery images</label>
        <p class="vp-field-hint">Additional photos customers can browse when booking (up to 10).</p>

        @if ($item->relationLoaded('images') && $item->images->isNotEmpty())
            <div class="vp-gallery-grid">
                @foreach ($item->images as $image)
                    <div class="vp-gallery-item">
                        @if ($image->imageUrl())
                            <img src="{{ $image->imageUrl() }}" alt="" class="panel-lightbox-trigger">
                        @endif
                        @if ($item->exists)
                            <button type="submit" form="vendor-delete-gallery-{{ $image->id }}" class="vp-btn vp-btn--danger vp-btn--sm vp-gallery-remove" title="Remove">×</button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <input type="file" name="gallery_images[]" class="vp-file" accept="image/jpeg,image/jpg,image/png,image/webp" multiple>
        @error('gallery_images')<p class="vp-field-error">{{ $message }}</p>@enderror
        @error('gallery_images.*')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    @include('vendor.products.partials.variants')
    @include('vendor.products.partials.damage-deductions')
</div>

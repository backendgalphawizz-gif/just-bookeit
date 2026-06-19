@php
    use App\Support\VendorValidationRules;

    $isCreate = ! $item->exists;
    $productImageMaxMb = (int) (VendorValidationRules::MAX_IMAGE_KB / 1024);
    $selectedSubcategoryId = old('subcategory_id', $item->subcategory_id);
    $selectedMainCategoryId = old('main_category_id', $item->subcategory?->parent_id);
    $subcategoryOptions = $subcategories->map(fn ($sub) => [
        'id' => $sub->id,
        'name' => $sub->name,
        'parent_id' => $sub->parent_id,
    ])->values();
    $audienceByMainSlug = $mainCategories->mapWithKeys(fn ($main) => [$main->id => $main->slug])->all();
@endphp

<input type="hidden" name="type" value="{{ $type }}">

<div class="vp-form-grid">
    <div
        class="vp-field vp-field--full"
        x-data="{
            mainCategoryId: @js((string) ($selectedMainCategoryId ?? '')),
            subcategoryId: @js((string) ($selectedSubcategoryId ?? '')),
            subcategories: @js($subcategoryOptions),
            audienceByMain: @js($audienceByMainSlug),
            filteredSubs() {
                if (!this.mainCategoryId) return [];
                return this.subcategories.filter((sub) => String(sub.parent_id) === String(this.mainCategoryId));
            },
            onMainChange() {
                this.subcategoryId = '';
            },
            onSubChange() {
                const sub = this.subcategories.find((item) => String(item.id) === String(this.subcategoryId));
                if (sub) this.mainCategoryId = String(sub.parent_id);
            }
        }"
    >
        <div class="vp-form-grid">
            <div class="vp-field">
                <label class="vp-label" for="main_category_id">Category <span class="vp-required">*</span></label>
                <select id="main_category_id" name="main_category_id" class="vp-select" x-model="mainCategoryId" @change="onMainChange()" required>
                    <option value="">Select category</option>
                    @foreach ($mainCategories as $mainCategory)
                        <option value="{{ $mainCategory->id }}">{{ $mainCategory->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="vp-field">
                <label class="vp-label" for="subcategory_id">Sub-category <span class="vp-required">*</span></label>
                <select id="subcategory_id" name="subcategory_id" class="vp-select" x-model="subcategoryId" @change="onSubChange()" required>
                    <option value="">Select sub-category</option>
                    <template x-for="sub in filteredSubs()" :key="sub.id">
                        <option :value="sub.id" x-text="sub.name"></option>
                    </template>
                </select>
                @error('subcategory_id')<p class="vp-field-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="vp-field">
        <label class="vp-label">Title <span class="vp-required">*</span></label>
        <input type="text" name="title" class="vp-input @error('title') vp-input--error @enderror" value="{{ old('title', $item->title) }}" required maxlength="255" data-vp-restrict="title">
        @error('title')<p class="vp-field-error">{{ $message }}</p>@enderror
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
        <input
            type="file"
            name="image"
            class="vp-file vp-input"
            accept="image/jpeg,image/jpg,image/png,image/webp"
            data-vp-max-file-bytes="{{ VendorValidationRules::MAX_IMAGE_KB * 1024 }}"
            data-vp-file-label="Primary image"
            {{ $isCreate ? 'required' : '' }}
        >
        <p class="vp-field-hint">JPEG, PNG or WebP — max {{ $productImageMaxMb }} MB</p>
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

        <input
            type="file"
            name="gallery_images[]"
            class="vp-file vp-input"
            accept="image/jpeg,image/jpg,image/png,image/webp"
            multiple
            data-vp-max-file-bytes="{{ VendorValidationRules::MAX_IMAGE_KB * 1024 }}"
            data-vp-file-label="Gallery image"
        >
        <p class="vp-field-hint">Up to 10 images — max {{ $productImageMaxMb }} MB each</p>
        @error('gallery_images')<p class="vp-field-error">{{ $message }}</p>@enderror
        @error('gallery_images.*')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    @include('vendor.products.partials.variants')
    @include('vendor.products.partials.damage-deductions')
</div>

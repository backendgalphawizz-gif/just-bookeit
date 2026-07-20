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
    $isFashion = ($type ?? '') === 'fashion-designer';
    $isRentalDress = ($type ?? '') === 'rented-dress';
    $isRentalJewellery = ($type ?? '') === 'rented-jewellery';
    $priceLabel = $isFashion ? 'Selling Price' : 'Price per day';
@endphp

<input type="hidden" name="type" value="{{ $type }}">

<div
    class="vp-product-form-grid {{ $isRentalJewellery ? 'vp-product-form-grid--jewellery' : '' }}"
    x-data="{
        mainCategoryId: @js((string) ($selectedMainCategoryId ?? '')),
        subcategoryId: @js((string) ($selectedSubcategoryId ?? '')),
        subcategories: @js($subcategoryOptions),
        audienceByMain: @js($audienceByMainSlug),
        init() {
            this.onSubChange();
        },
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
    <div class="vp-field">
        <label class="vp-label" for="title">Product Name <span class="vp-required">*</span></label>
        <input
            id="title"
            type="text"
            name="title"
            class="vp-input @error('title') vp-input--error @enderror"
            value="{{ old('title', $item->title) }}"
            required
            maxlength="255"
            placeholder="Enter product name"
            data-vp-restrict="title"
        >
        @error('title')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-field">
        <label class="vp-label" for="main_category_id">Category <span class="vp-required">*</span></label>
        <select id="main_category_id" name="main_category_id" class="vp-select" x-model="mainCategoryId" @change="onMainChange()" required>
            <option value="">Select category</option>
            @foreach ($mainCategories as $mainCategory)
                <option value="{{ $mainCategory->id }}">{{ $mainCategory->name }}</option>
            @endforeach
        </select>
        @error('main_category_id')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-field">
        <label class="vp-label" for="subcategory_id">Sub - Category <span class="vp-required">*</span></label>
        <input type="hidden" name="subcategory_id" x-model="subcategoryId">
        <select id="subcategory_id" class="vp-select" x-model="subcategoryId" @change="onSubChange()" required>
            <option value="">Select sub-category</option>
            <template x-for="sub in filteredSubs()" :key="sub.id">
                <option :value="String(sub.id)" x-text="sub.name" :selected="String(subcategoryId) === String(sub.id)"></option>
            </template>
        </select>
        @error('subcategory_id')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    @if ($isRentalDress)
        @include('vendor.products.partials.rental-fields')
    @elseif ($isRentalJewellery)
        @include('vendor.products.partials.jewellery-fields')
    @else
        <div class="vp-field vp-field--full">
            <div class="vp-product-form-grid vp-product-form-grid--2">
                <div class="vp-field">
                    <label class="vp-label" for="price_per_day">{{ $priceLabel }} @if($isCreate)<span class="vp-required">*</span>@endif</label>
                    <div class="vp-currency-input @error('price_per_day') vp-currency-input--error @enderror">
                        <span class="vp-currency-prefix" aria-hidden="true">₹</span>
                        <input
                            id="price_per_day"
                            type="number"
                            name="price_per_day"
                            class="vp-input"
                            value="{{ old('price_per_day', $item->price_per_day) }}"
                            min="0"
                            step="0.01"
                            placeholder="0"
                            {{ $isCreate ? 'required' : '' }}
                        >
                    </div>
                    @error('price_per_day')<p class="vp-field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="vp-field vp-field--full">
            <label class="vp-label" for="description">Product Description</label>
            <textarea
                id="description"
                name="description"
                class="vp-textarea @error('description') vp-textarea--error @enderror"
                rows="5"
                maxlength="5000"
                placeholder="Enter description..."
                data-vp-restrict="text"
            >{{ old('description', $item->description) }}</textarea>
            @error('description')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>

        <div class="vp-field vp-field--full">
            <label class="vp-label">Upload Images and Videos @if($isCreate)<span class="vp-required">*</span>@endif</label>

            <div class="vp-upload-stack">
                <div class="vp-upload-block">
                    <div class="vp-upload-block-label">Primary image @if($isCreate)<span class="vp-required">*</span>@endif</div>
                    <p class="vp-field-hint">Main cover photo shown in listings.</p>
                    @if ($item->displayImageUrl())
                        <img src="{{ url($item->displayImageUrl()) }}" alt="" class="vp-product-preview panel-lightbox-trigger">
                    @endif
                    <div
                        class="vp-dropzone"
                        data-vp-dropzone
                        data-empty-text="Click to upload or drag and drop"
                    >
                        <input
                            type="file"
                            name="image"
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            data-vp-max-file-bytes="{{ VendorValidationRules::MAX_IMAGE_KB * 1024 }}"
                            data-vp-file-label="Primary image"
                            {{ $isCreate ? 'required' : '' }}
                            hidden
                        >
                        <div class="vp-dropzone-icon" aria-hidden="true">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25"/>
                            </svg>
                        </div>
                        <div class="vp-dropzone-title">Click to upload or drag and drop</div>
                        <div class="vp-dropzone-hint">SVG, PNG, JPG or WebP (max. {{ $productImageMaxMb }}MB)</div>
                        <div class="vp-dropzone-file" data-vp-dropzone-name>No file chosen</div>
                    </div>
                    @error('image')<p class="vp-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="vp-upload-block">
                    <div class="vp-upload-block-label">Gallery images</div>
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

                    <div
                        class="vp-dropzone"
                        data-vp-dropzone
                        data-empty-text="Click to upload or drag and drop"
                    >
                        <input
                            type="file"
                            name="gallery_images[]"
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            multiple
                            data-vp-max-file-bytes="{{ VendorValidationRules::MAX_IMAGE_KB * 1024 }}"
                            data-vp-file-label="Gallery image"
                            hidden
                        >
                        <div class="vp-dropzone-icon" aria-hidden="true">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                            </svg>
                        </div>
                        <div class="vp-dropzone-title">Click to upload or drag and drop</div>
                        <div class="vp-dropzone-hint">Up to 10 images — max {{ $productImageMaxMb }} MB each</div>
                        <div class="vp-dropzone-file" data-vp-dropzone-name>No file chosen</div>
                    </div>
                    @error('gallery_images')<p class="vp-field-error">{{ $message }}</p>@enderror
                    @error('gallery_images.*')<p class="vp-field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    @endif
</div>

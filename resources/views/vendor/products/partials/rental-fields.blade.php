@php
    use App\Models\PlatformSetting;
    use App\Support\VendorValidationRules;

    $productImageMaxMb = (int) (VendorValidationRules::MAX_IMAGE_KB / 1024);
    $productVideoMaxMb = (int) (VendorValidationRules::MAX_VIDEO_KB / 1024);
    $sizeOptions = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    $colorOptions = [
        'Black', 'White', 'Red', 'Blue', 'Green', 'Pink', 'Gold', 'Silver',
        'Maroon', 'Ivory', 'Navy Blue', 'Rose Gold',
    ];
    $colorCssMap = [
        'black' => '#111111', 'white' => '#ffffff', 'red' => '#e11d48', 'blue' => '#2563eb',
        'green' => '#16a34a', 'pink' => '#ec4899', 'gold' => '#ca8a04', 'silver' => '#a8a29e',
        'maroon' => '#9f1239', 'ivory' => '#fffff0', 'navy blue' => '#1e3a8a', 'rose gold' => '#b76e79',
    ];

    $variantRows = old('variants');
    if (! is_array($variantRows)) {
        $variantRows = $item->relationLoaded('variants') && $item->variants->isNotEmpty()
            ? $item->variants->map(fn ($v) => [
                'size' => $v->size,
                'color' => $v->color,
                'price' => $v->price,
                'image_url' => $v->imageUrl(),
                'stored_image_path' => $v->image_path,
            ])->values()->all()
            : [];
    }

    $damageRows = old('damage_deductions');
    if (! is_array($damageRows)) {
        $damageRows = $item->relationLoaded('damageDeductions') && $item->damageDeductions->isNotEmpty()
            ? $item->damageDeductions->map(fn ($r) => ['damage_type' => $r->damage_type, 'percent' => $r->percent])->all()
            : [['damage_type' => '', 'percent' => '']];
    }
    if ($damageRows === []) {
        $damageRows = [['damage_type' => '', 'percent' => '']];
    }

    $serviceCategoryId = (int) ($category->id ?? $item->category_id ?? 0);
    $subcategoryId = (int) old('subcategory_id', $item->subcategory_id ?? 0);
    $maxDamagePercent = PlatformSetting::maxDamagePercentForPortfolioItem(
        $subcategoryId ?: null,
        $serviceCategoryId ?: null
    );

    $existingMedia = $item->relationLoaded('images')
        ? $item->images->filter(fn ($img) => $img->mediaUrl())->values()
        : collect();
@endphp

{{-- Product Price + Advance Amount --}}
<div class="vp-field vp-field--full">
    <div class="vp-product-form-grid vp-product-form-grid--2">
        <div class="vp-field">
            <label class="vp-label" for="price_per_day">Product Price (per day) <span class="vp-required">*</span></label>
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

        <div class="vp-field">
            <label class="vp-label" for="advance_amount">Advance Amount</label>
            <div class="vp-currency-input @error('advance_amount') vp-currency-input--error @enderror">
                <span class="vp-currency-prefix" aria-hidden="true">₹</span>
                <input
                    id="advance_amount"
                    type="number"
                    name="advance_amount"
                    class="vp-input"
                    value="{{ old('advance_amount', $item->advance_amount) }}"
                    min="0"
                    step="0.01"
                    placeholder="0"
                >
            </div>
            @error('advance_amount')<p class="vp-field-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

{{-- Description --}}
<div class="vp-field vp-field--full">
    <label class="vp-label" for="description">Product Description</label>
    <textarea
        id="description"
        name="description"
        class="vp-textarea @error('description') vp-textarea--error @enderror"
        rows="4"
        maxlength="5000"
        placeholder="Enter description..."
        data-vp-restrict="text"
    >{{ old('description', $item->description) }}</textarea>
    @error('description')<p class="vp-field-error">{{ $message }}</p>@enderror
</div>

{{-- Upload Images and Videos --}}
<div class="vp-field vp-field--full">
    <label class="vp-label">Upload Images and Videos</label>
    @include('vendor.products.partials.media-upload-slots', [
        'existingMedia' => $existingMedia,
        'productImageMaxMb' => $productImageMaxMb,
        'mediaRequired' => false,
        'item' => $item,
    ])
    @error('media_files')<p class="vp-field-error">{{ $message }}</p>@enderror
    @error('media_files.*')<p class="vp-field-error">{{ $message }}</p>@enderror
    @error('image')<p class="vp-field-error">{{ $message }}</p>@enderror
</div>

{{-- Variants --}}
<div class="vp-field vp-field--full" data-vp-dress-variants>
    <button type="button" class="vp-dress-variants-toggle" data-vp-dress-variants-toggle>
        Add Variants
    </button>

    <div class="vp-dress-variant-composer" data-vp-dress-variant-composer hidden>
        <div class="vp-product-form-grid vp-product-form-grid--3">
            <div class="vp-field">
                <label class="vp-label">Size</label>
                <select class="vp-select" data-vp-variant-size>
                    <option value="">Select size</option>
                    @foreach ($sizeOptions as $size)
                        <option value="{{ $size }}">{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="vp-field">
                <label class="vp-label">Color</label>
                <select class="vp-select" data-vp-variant-color>
                    <option value="">Select color</option>
                    @foreach ($colorOptions as $colorName)
                        <option value="{{ $colorName }}">{{ $colorName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="vp-field">
                <label class="vp-label">Price</label>
                <div class="vp-currency-input">
                    <span class="vp-currency-prefix" aria-hidden="true">₹</span>
                    <input type="number" class="vp-input" min="0" step="0.01" placeholder="0" data-vp-variant-price>
                </div>
            </div>
        </div>

        <label class="vp-dress-variant-upload" data-vp-variant-upload-zone>
            <input type="file" accept="image/jpeg,image/jpg,image/png,image/webp" hidden data-vp-variant-file>
            <span class="vp-dress-variant-upload-preview" data-vp-variant-file-preview hidden>
                <img alt="">
            </span>
            <span class="vp-dress-variant-upload-empty" data-vp-variant-file-empty>
                <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 5.25v2.25m0 0V9.75m0-2.25H17.25m2.25 0h2.25"/>
                </svg>
                <span>UPLOAD</span>
            </span>
        </label>

        <button type="button" class="vp-btn vp-btn--primary vp-dress-variant-add-btn" data-vp-dress-variant-add>Add Variant</button>
    </div>

    <div class="vp-dress-variant-list" data-vp-dress-variant-list>
        @foreach ($variantRows as $index => $variant)
            @php
                $colorName = (string) ($variant['color'] ?? '');
                $colorCss = $colorCssMap[strtolower(trim($colorName))] ?? '#ec4899';
            @endphp
            <div class="vp-dress-variant-card" data-vp-dress-variant-row>
                <div class="vp-dress-variant-card-thumb">
                    @if (! empty($variant['image_url']))
                        <img src="{{ $variant['image_url'] }}" alt="" data-vp-variant-thumb>
                        <span class="vp-dress-variant-card-thumb-empty" data-vp-variant-thumb-empty hidden>No image</span>
                    @else
                        <span class="vp-dress-variant-card-thumb-empty" data-vp-variant-thumb-empty>No image</span>
                        <img alt="" data-vp-variant-thumb hidden>
                    @endif
                </div>
                <div class="vp-dress-variant-card-meta">
                    <div>SIZE - <strong data-vp-variant-size-label>{{ strtoupper((string) ($variant['size'] ?? '—')) }}</strong></div>
                    <div>COLOR - <strong data-vp-variant-color-label style="color: {{ $colorCss }};">{{ strtoupper($colorName !== '' ? $colorName : '—') }}</strong></div>
                    <div>PRICE - <strong data-vp-variant-price-label>₹{{ number_format((float) ($variant['price'] ?? 0), 0) }}</strong></div>
                </div>
                <div class="vp-dress-variant-card-actions">
                    <button type="button" class="vp-dress-variant-icon-btn" data-vp-dress-variant-edit title="Edit" aria-label="Edit variant">
                        @include('vendor.partials.nav-icon', ['icon' => 'edit'])
                    </button>
                    <button type="button" class="vp-dress-variant-icon-btn vp-dress-variant-icon-btn--danger" data-vp-dress-variant-remove title="Delete" aria-label="Delete variant">
                        @include('vendor.partials.nav-icon', ['icon' => 'delete'])
                    </button>
                </div>
                <input type="hidden" name="variants[{{ $index }}][size]" value="{{ $variant['size'] ?? '' }}" data-vp-variant-size-input>
                <input type="hidden" name="variants[{{ $index }}][color]" value="{{ $variant['color'] ?? '' }}" data-vp-variant-color-input>
                <input type="hidden" name="variants[{{ $index }}][price]" value="{{ $variant['price'] ?? '' }}" data-vp-variant-price-input>
                <input type="hidden" name="variants[{{ $index }}][stored_image_path]" value="{{ $variant['stored_image_path'] ?? '' }}" data-vp-variant-stored-path>
                <input type="hidden" name="variants[{{ $index }}][image_base64]" value="" data-vp-variant-image-base64>
                <input type="file" name="variants[{{ $index }}][image]" accept="image/jpeg,image/jpg,image/png,image/webp" hidden data-vp-variant-image-input>
            </div>
        @endforeach
    </div>

    @error('variants')<p class="vp-field-error">{{ $message }}</p>@enderror
    @error('variants.*')<p class="vp-field-error">{{ $message }}</p>@enderror
    @error('variants.*.image')<p class="vp-field-error">{{ $message }}</p>@enderror

    <template data-vp-dress-variant-template>
        <div class="vp-dress-variant-card" data-vp-dress-variant-row>
                <div class="vp-dress-variant-card-thumb">
                    <span class="vp-dress-variant-card-thumb-empty" data-vp-variant-thumb-empty>No image</span>
                    <img alt="" data-vp-variant-thumb hidden>
                </div>
            <div class="vp-dress-variant-card-meta">
                <div>SIZE - <strong data-vp-variant-size-label>—</strong></div>
                <div>COLOR - <strong data-vp-variant-color-label>—</strong></div>
                <div>PRICE - <strong data-vp-variant-price-label>₹0</strong></div>
            </div>
            <div class="vp-dress-variant-card-actions">
                <button type="button" class="vp-dress-variant-icon-btn" data-vp-dress-variant-edit title="Edit" aria-label="Edit variant">
                    @include('vendor.partials.nav-icon', ['icon' => 'edit'])
                </button>
                <button type="button" class="vp-dress-variant-icon-btn vp-dress-variant-icon-btn--danger" data-vp-dress-variant-remove title="Delete" aria-label="Delete variant">
                    @include('vendor.partials.nav-icon', ['icon' => 'delete'])
                </button>
            </div>
            <input type="hidden" name="variants[__INDEX__][size]" value="" data-vp-variant-size-input>
            <input type="hidden" name="variants[__INDEX__][color]" value="" data-vp-variant-color-input>
            <input type="hidden" name="variants[__INDEX__][price]" value="" data-vp-variant-price-input>
            <input type="hidden" name="variants[__INDEX__][stored_image_path]" value="" data-vp-variant-stored-path>
            <input type="hidden" name="variants[__INDEX__][image_base64]" value="" data-vp-variant-image-base64>
            <input type="file" name="variants[__INDEX__][image]" accept="image/jpeg,image/jpg,image/png,image/webp" hidden data-vp-variant-image-input>
        </div>
    </template>
</div>

{{-- Damage Deduction --}}
<div class="vp-field vp-field--full" data-vp-damage>
    <div class="vp-form-section-head" style="margin-bottom:.55rem;">
        <label class="vp-label" style="margin:0;">Damage Deduction</label>
        <button type="button" class="vp-link-btn vp-link-btn--accent" data-vp-damage-add>+ Add More</button>
    </div>
    @if ($maxDamagePercent !== null)
        <p class="vp-field-hint" style="color:#b45309;">Maximum total deduction for this category: {{ rtrim(rtrim(number_format($maxDamagePercent, 2), '0'), '.') }}%.</p>
    @endif

    <div class="vp-damage-figma-list" data-vp-damage-list>
        @foreach ($damageRows as $index => $rule)
            <div class="vp-damage-figma-row" data-vp-damage-row>
                <input
                    type="text"
                    name="damage_deductions[{{ $index }}][damage_type]"
                    value="{{ $rule['damage_type'] ?? '' }}"
                    class="vp-input"
                    placeholder="Enter damage type"
                >
                <input
                    type="number"
                    name="damage_deductions[{{ $index }}][percent]"
                    value="{{ $rule['percent'] ?? '' }}"
                    class="vp-input"
                    min="0"
                    max="{{ $maxDamagePercent ?? 100 }}"
                    step="0.01"
                    placeholder="Enter amount %"
                >
                <button type="button" class="vp-link-btn vp-link-btn--muted" data-vp-damage-remove>Remove</button>
            </div>
        @endforeach
    </div>

    @error('damage_deductions')<p class="vp-field-error">{{ $message }}</p>@enderror
    @error('damage_deductions.*')<p class="vp-field-error">{{ $message }}</p>@enderror

    <template data-vp-damage-template>
        <div class="vp-damage-figma-row" data-vp-damage-row>
            <input type="text" name="damage_deductions[__INDEX__][damage_type]" class="vp-input" placeholder="Enter damage type">
            <input type="number" name="damage_deductions[__INDEX__][percent]" class="vp-input" min="0" max="{{ $maxDamagePercent ?? 100 }}" step="0.01" placeholder="Enter amount %">
            <button type="button" class="vp-link-btn vp-link-btn--muted" data-vp-damage-remove>Remove</button>
        </div>
    </template>
</div>

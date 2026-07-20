@php
    use App\Models\PlatformSetting;
    use App\Support\VendorValidationRules;

    $productImageMaxMb = (int) (VendorValidationRules::MAX_IMAGE_KB / 1024);

    $damageRows = old('damage_deductions');
    if (! is_array($damageRows)) {
        $damageRows = $item->relationLoaded('damageDeductions') && $item->damageDeductions->isNotEmpty()
            ? $item->damageDeductions->map(fn ($r) => ['damage_type' => $r->damage_type, 'percent' => $r->percent])->all()
            : [['damage_type' => '', 'percent' => ''], ['damage_type' => '', 'percent' => '']];
    }
    if ($damageRows === []) {
        $damageRows = [['damage_type' => '', 'percent' => ''], ['damage_type' => '', 'percent' => '']];
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
    <label class="vp-label">Upload Images and Videos @if($isCreate)<span class="vp-required">*</span>@endif</label>
    @include('vendor.products.partials.media-upload-slots', [
        'existingMedia' => $existingMedia,
        'productImageMaxMb' => $productImageMaxMb,
        'mediaRequired' => $isCreate,
        'item' => $item,
    ])
    @error('media_files')<p class="vp-field-error">{{ $message }}</p>@enderror
    @error('media_files.*')<p class="vp-field-error">{{ $message }}</p>@enderror
    @error('image')<p class="vp-field-error">{{ $message }}</p>@enderror
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

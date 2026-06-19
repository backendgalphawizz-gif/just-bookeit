@php
    use App\Support\VendorValidationRules;

    $productImageMaxMb = (int) (VendorValidationRules::MAX_IMAGE_KB / 1024);
    $variantRows = old('variants');
    if (! is_array($variantRows)) {
        $variantRows = $item->relationLoaded('variants') && $item->variants->isNotEmpty()
            ? $item->variants->map(fn ($v) => [
                'size' => $v->size,
                'color' => $v->color,
                'price' => $v->price,
                'image_url' => $v->imageUrl(),
            ])->all()
            : [];
    }
    if ($variantRows === []) {
        $variantRows = [['size' => '', 'color' => '', 'price' => '', 'image_url' => null]];
    }
@endphp

<div class="vp-field vp-field--full vp-form-section" data-vp-variants>
    <div class="vp-form-section-head">
        <div>
            <label class="vp-label">Size / color variants</label>
            <p class="vp-field-hint">Optional — size, color, price, and image per variant.</p>
        </div>
        <button type="button" class="vp-btn vp-btn--outline vp-btn--sm" data-vp-variants-add>+ Add variant</button>
    </div>

    <div class="vp-field" style="display:flex;flex-direction:column;gap:.75rem;" data-vp-variants-list>
        @foreach ($variantRows as $index => $variant)
            <div class="vp-repeat-row" data-vp-variants-row>
                <div class="vp-repeat-row-grid">
                    <div>
                        <label class="vp-label">Size</label>
                        <input type="text" name="variants[{{ $index }}][size]" value="{{ $variant['size'] ?? '' }}" class="vp-input" placeholder="e.g. M, 32">
                    </div>
                    <div>
                        <label class="vp-label">Color</label>
                        <input type="text" name="variants[{{ $index }}][color]" value="{{ $variant['color'] ?? '' }}" class="vp-input" placeholder="e.g. Red">
                    </div>
                    <div>
                        <label class="vp-label">Price (₹)</label>
                        <input type="number" name="variants[{{ $index }}][price]" value="{{ $variant['price'] ?? '' }}" class="vp-input" min="0" step="0.01" placeholder="0">
                    </div>
                    <div>
                        <label class="vp-label">Variant image</label>
                        @if (! empty($variant['image_url']))
                            <img src="{{ $variant['image_url'] }}" alt="" class="vp-thumb panel-lightbox-trigger " style="width:3rem;height:3rem;margin-bottom:.4rem;border-radius:8px;object-fit:cover;">
                        @endif
                        <input type="file" name="variant_images[]" accept="image/jpeg,image/jpg,image/png,image/webp" class="vp-file vp-input" data-vp-max-file-bytes="{{ VendorValidationRules::MAX_IMAGE_KB * 1024 }}" data-vp-file-label="Variant image">
                    </div>
                    <div>
                        <button type="button" class="vp-btn vp-btn--ghost vp-btn--sm" style="color:#dc2626;" data-vp-variants-remove>Remove</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @error('variants')<p class="vp-field-error">{{ $message }}</p>@enderror
    @error('variants.*')<p class="vp-field-error">{{ $message }}</p>@enderror
    @error('variant_images.*')<p class="vp-field-error">{{ $message }}</p>@enderror

    <template data-vp-variants-template>
        <div class="vp-repeat-row" data-vp-variants-row>
            <div class="vp-repeat-row-grid">
                <div><label class="vp-label">Size</label><input type="text" name="variants[__INDEX__][size]" class="vp-input" placeholder="e.g. M, 32"></div>
                <div><label class="vp-label">Color</label><input type="text" name="variants[__INDEX__][color]" class="vp-input" placeholder="e.g. Red"></div>
                <div><label class="vp-label">Price (₹)</label><input type="number" name="variants[__INDEX__][price]" class="vp-input" min="0" step="0.01" placeholder="0"></div>
                <div><label class="vp-label">Variant image</label><input type="file" name="variant_images[]" accept="image/jpeg,image/jpg,image/png,image/webp" class="vp-file vp-input" data-vp-max-file-bytes="{{ VendorValidationRules::MAX_IMAGE_KB * 1024 }}" data-vp-file-label="Variant image"></div>
                <div><button type="button" class="vp-btn vp-btn--ghost vp-btn--sm" style="color:#dc2626;" data-vp-variants-remove>Remove</button></div>
            </div>
        </div>
    </template>
</div>

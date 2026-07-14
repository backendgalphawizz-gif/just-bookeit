@php
    $variants = $item->availableVariants();
    $selectedVariantId = (int) ($selectedVariantId ?? request()->integer('variant') ?: old('portfolio_item_variant_id'));
    $selectedVariant = $selectedVariantId ? $item->findVariant($selectedVariantId) : null;
    $hasVariants = $variants->isNotEmpty();
    $baseImage = $baseImageUrl ?? $item->displayImageUrl();
    $baseLabel = $item->rentalPriceLabel();
@endphp

@if ($hasVariants)
    <div
        class="jbw-variant-picker"
        id="jbw-variant-picker"
        data-base-label="{{ $baseLabel }}"
        @if($baseImage) data-base-image="{{ $baseImage }}" @endif
    >
        <p class="jbw-variant-label">Select size &amp; color <span class="jbw-variant-optional">(optional)</span></p>
        <div class="jbw-variant-options" role="radiogroup" aria-label="Product variants">
            <label class="jbw-variant-chip jbw-variant-chip--base{{ ! $selectedVariantId ? ' is-selected' : '' }}">
                <input
                    type="radio"
                    name="portfolio_item_variant_id"
                    value=""
                    class="jbw-variant-input"
                    data-label="{{ $baseLabel }}"
                    @if($baseImage) data-image="{{ $baseImage }}" @endif
                    @checked(! $selectedVariantId)
                >
                <span class="jbw-variant-chip-text">
                    <strong>Base item</strong>
                    <span>{{ $baseLabel }}</span>
                </span>
            </label>

            @foreach ($variants as $variant)
                @php
                    $label = collect([$variant->size, $variant->color])->filter()->implode(' · ');
                    $label = $label !== '' ? $label : 'Option '.$loop->iteration;
                    $variantImg = $variant->imageUrl();
                @endphp
                <label class="jbw-variant-chip{{ $selectedVariantId === $variant->id ? ' is-selected' : '' }}">
                    <input
                        type="radio"
                        name="portfolio_item_variant_id"
                        value="{{ $variant->id }}"
                        class="jbw-variant-input"
                        data-price="{{ (float) $variant->price }}"
                        data-label="{{ $item->rentalPriceLabelFor($variant) }}"
                        @if($variantImg) data-image="{{ $variantImg }}" @endif
                        @checked($selectedVariantId === $variant->id)
                    >
                    <span class="jbw-variant-chip-text">
                        <strong>{{ $label }}</strong>
                        <span>{{ $item->rentalPriceLabelFor($variant) }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('portfolio_item_variant_id')<p class="jbw-field-error">{{ $message }}</p>@enderror
    </div>
@endif

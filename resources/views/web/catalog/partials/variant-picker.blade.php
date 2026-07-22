@php
    $variants = $item->availableVariants();
    $selectedVariantId = (int) ($selectedVariantId ?? request()->integer('variant') ?: old('portfolio_item_variant_id'));
    $selectedVariant = $selectedVariantId ? $item->findVariant($selectedVariantId) : null;
    $hasVariants = $variants->isNotEmpty();
    $baseImage = $baseImageUrl ?? $item->displayImageUrl();
    $baseLabel = $item->rentalPriceLabel();

    $colors = $variants
        ->groupBy(fn ($variant) => trim((string) $variant->color))
        ->filter(fn ($group, $color) => $color !== '')
        ->map(function ($group, $color) {
            $withImage = $group->first(fn ($variant) => filled($variant->imageUrl()));

            return [
                'name' => $color,
                'hex' => \App\Support\ProductOptionCatalog::hexForName($color) ?: '#c4c4c4',
                'image' => $withImage?->imageUrl(),
            ];
        })
        ->values();

    $sizes = $variants
        ->pluck('size')
        ->map(fn ($size) => trim((string) $size))
        ->filter()
        ->unique()
        ->values();

    $variantPayload = $variants->map(function ($variant) use ($item) {
        return [
            'id' => $variant->id,
            'size' => trim((string) ($variant->size ?? '')),
            'color' => trim((string) ($variant->color ?? '')),
            'label' => $item->rentalPriceLabelFor($variant),
            'image' => $variant->imageUrl(),
        ];
    })->values();

    $initialColor = $selectedVariant?->color
        ? trim((string) $selectedVariant->color)
        : ($colors->first()['name'] ?? '');
    $initialSize = $selectedVariant?->size
        ? trim((string) $selectedVariant->size)
        : ($sizes->first() ?? '');
@endphp

@if ($hasVariants)
    <div
        class="jbw-variant-picker jbw-variant-picker--swatches"
        id="jbw-variant-picker"
        data-base-label="{{ $baseLabel }}"
        @if($baseImage) data-base-image="{{ $baseImage }}" @endif
        data-variants="{{ Js::from($variantPayload) }}"
        data-selected-color="{{ $initialColor }}"
        data-selected-size="{{ $initialSize }}"
    >
        <input type="hidden" name="portfolio_item_variant_id" id="jbw-variant-id" class="jbw-variant-input" value="{{ $selectedVariantId ?: '' }}" data-label="{{ $selectedVariant ? $item->rentalPriceLabelFor($selectedVariant) : $baseLabel }}" @if($selectedVariant?->imageUrl() ?: $baseImage) data-image="{{ $selectedVariant?->imageUrl() ?: $baseImage }}" @endif>

        @if ($colors->isNotEmpty())
            <div class="jbw-variant-group">
                <p class="jbw-variant-label">Select color</p>
                <div class="jbw-color-swatch-row" role="listbox" aria-label="Select color">
                    @foreach ($colors as $color)
                        <button
                            type="button"
                            class="jbw-detail-color-swatch{{ $initialColor === $color['name'] ? ' is-selected' : '' }}"
                            data-color="{{ $color['name'] }}"
                            title="{{ $color['name'] }}"
                            aria-label="{{ $color['name'] }}"
                            style="--swatch: {{ $color['hex'] }}"
                        >
                            @if ($color['image'])
                                <img src="{{ $color['image'] }}" alt="">
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($sizes->isNotEmpty())
            <div class="jbw-variant-group">
                <p class="jbw-variant-label">Size</p>
                <div class="jbw-size-chip-row" role="listbox" aria-label="Select size">
                    @foreach ($sizes as $size)
                        <button
                            type="button"
                            class="jbw-detail-size-chip{{ $initialSize === $size ? ' is-selected' : '' }}"
                            data-size="{{ $size }}"
                        >{{ $size }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        @error('portfolio_item_variant_id')<p class="jbw-field-error">{{ $message }}</p>@enderror
    </div>

    @once
        @push('scripts')
        <script>
        (function () {
            const picker = document.getElementById('jbw-variant-picker');
            if (!picker || !picker.classList.contains('jbw-variant-picker--swatches')) return;

            const variantInput = document.getElementById('jbw-variant-id');
            const variants = (() => {
                try { return JSON.parse(picker.getAttribute('data-variants') || '[]'); }
                catch (_) { return []; }
            })();

            let selectedColor = picker.getAttribute('data-selected-color') || '';
            let selectedSize = picker.getAttribute('data-selected-size') || '';
            const norm = (v) => String(v || '').trim().toLowerCase();

            const findVariant = () => {
                const hasColors = picker.querySelectorAll('[data-color]').length > 0;
                const hasSizes = picker.querySelectorAll('[data-size]').length > 0;
                return variants.find((v) => {
                    const colorOk = !hasColors || norm(v.color) === norm(selectedColor);
                    const sizeOk = !hasSizes || norm(v.size) === norm(selectedSize);
                    return colorOk && sizeOk;
                }) || null;
            };

            const syncSizeAvailability = () => {
                const hasColors = picker.querySelectorAll('[data-color]').length > 0;
                picker.querySelectorAll('[data-size]').forEach((btn) => {
                    if (!hasColors || !selectedColor) {
                        btn.disabled = false;
                        btn.classList.remove('is-disabled');
                        return;
                    }
                    const available = variants.some((v) =>
                        norm(v.color) === norm(selectedColor) && norm(v.size) === norm(btn.dataset.size)
                    );
                    btn.disabled = !available;
                    btn.classList.toggle('is-disabled', !available);
                    if (!available && norm(selectedSize) === norm(btn.dataset.size)) {
                        const next = Array.from(picker.querySelectorAll('[data-size]')).find((b) => !b.disabled);
                        selectedSize = next ? next.dataset.size : '';
                    }
                });
            };

            const applyVariant = ({ syncGallery = true } = {}) => {
                syncSizeAvailability();

                picker.querySelectorAll('[data-color]').forEach((btn) => {
                    btn.classList.toggle('is-selected', norm(btn.dataset.color) === norm(selectedColor));
                });
                picker.querySelectorAll('[data-size]').forEach((btn) => {
                    btn.classList.toggle('is-selected', norm(btn.dataset.size) === norm(selectedSize));
                });

                const match = findVariant();
                const label = match?.label || picker.dataset.baseLabel || '';
                const image = match?.image || picker.dataset.baseImage || '';
                const variantLabel = [match?.color, match?.size].filter(Boolean).join(' · ');

                if (variantInput) {
                    variantInput.value = match ? String(match.id) : '';
                    variantInput.dataset.label = label;
                    if (image) variantInput.dataset.image = image;
                    else delete variantInput.dataset.image;
                }

                picker.dispatchEvent(new CustomEvent('jbw:variant-changed', {
                    bubbles: true,
                    detail: {
                        id: match ? match.id : null,
                        label,
                        image,
                        variantLabel,
                        syncGallery,
                    },
                }));
            };

            picker.querySelectorAll('[data-color]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    selectedColor = btn.dataset.color || '';
                    applyVariant({ syncGallery: true });
                });
            });

            picker.querySelectorAll('[data-size]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (btn.disabled) return;
                    selectedSize = btn.dataset.size || '';
                    applyVariant({ syncGallery: false });
                });
            });

            applyVariant({ syncGallery: false });
        })();
        </script>
        @endpush
    @endonce
@endif

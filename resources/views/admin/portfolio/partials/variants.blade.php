@php
    use App\Support\VendorValidationRules;

    $productImageMaxMb = (int) (VendorValidationRules::MAX_IMAGE_KB / 1024);
    $variantRows = old('variants');
    if (! is_array($variantRows)) {
        $variantRows = $portfolio->relationLoaded('variants') && $portfolio->variants->isNotEmpty()
            ? $portfolio->variants->map(fn ($v) => [
                'size' => $v->size,
                'color' => $v->color,
                'price' => $v->price,
                'advance_amount' => $v->advance_amount,
                'quantity' => $v->quantity,
                'image_url' => $v->imageUrl(),
                'thumb_url' => $v->thumbUrl() ?: $v->imageUrl(),
                'stored_image_path' => $v->image_path,
            ])->all()
            : [];
    }
    if ($variantRows === []) {
        $variantRows = [['size' => '', 'color' => '', 'price' => '', 'advance_amount' => '', 'quantity' => '', 'image_url' => null, 'stored_image_path' => null]];
    }
@endphp

<div class="" data-product-variants>
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
            <label class="jb-label">Variants <span class="text-rose-600">*</span></label>
            <p class="mt-1 text-sm text-slate-500">Required for rental dress — size and/or color, price, advance, quantity, and optional image per variant.</p>
        </div>
        <button type="button" class="jb-btn jb-btn-secondary jb-btn-sm" data-product-variants-add>+ Add variant</button>
    </div>

    <div class="space-y-3" data-product-variants-list>
        @foreach ($variantRows as $index => $variant)
            <div class="grid gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_7rem_7rem_6rem_minmax(0,10rem)_auto]" data-product-variants-row>
                <div>
                    <label class="jb-label">Size</label>
                    <input type="text" name="variants[{{ $index }}][size]" value="{{ $variant['size'] ?? '' }}" class="jb-input" placeholder="e.g. M, 32">
                </div>
                <div>
                    <label class="jb-label">Color</label>
                    <input type="text" name="variants[{{ $index }}][color]" value="{{ $variant['color'] ?? '' }}" class="jb-input" placeholder="e.g. Red">
                </div>
                <div>
                    <label class="jb-label">Price (₹)/per day</label>
                    <input type="text" name="variants[{{ $index }}][price]" value="{{ $variant['price'] ?? '' }}" class="jb-input" inputmode="decimal" autocomplete="off" placeholder="0" data-jb-restrict="amount">
                </div>
                <div>
                    <label class="jb-label">Advance (₹)</label>
                    <input type="text" name="variants[{{ $index }}][advance_amount]" value="{{ $variant['advance_amount'] ?? '' }}" class="jb-input" inputmode="decimal" autocomplete="off" placeholder="0" data-jb-restrict="amount">
                </div>
                <div>
                    <label class="jb-label">Qty</label>
                    <input type="text" name="variants[{{ $index }}][quantity]" value="{{ $variant['quantity'] ?? '' }}" class="jb-input" inputmode="numeric" autocomplete="off" placeholder="1" data-jb-restrict="integer">
                </div>
                <div data-variant-image-field>
                    <label class="jb-label">Variant image</label>
                    <div class="jb-variant-image-preview" data-variant-image-preview @if (empty($variant['image_url'])) hidden @endif>
                        <img
                            src="{{ $variant['thumb_url'] ?? $variant['image_url'] ?? '' }}"
                            data-lightbox-src="{{ $variant['image_url'] ?? '' }}"
                            alt="Variant image preview"
                            class="jb-variant-image-preview__img panel-lightbox-trigger"
                            data-variant-image-thumb
                            loading="lazy"
                            decoding="async"
                        >
                        <p class="jb-variant-image-preview__hint" data-variant-image-hint>{{ ! empty($variant['image_url']) ? 'Current image' : '' }}</p>
                    </div>
                    <div class="jb-variant-image-preview jb-variant-image-preview--empty" data-variant-image-empty @if (! empty($variant['image_url'])) hidden @endif>
                        <span>No image</span>
                    </div>
                    <input type="hidden" name="variants[{{ $index }}][stored_image_path]" value="{{ $variant['stored_image_path'] ?? '' }}">
                    <input
                        type="file"
                        name="variants[{{ $index }}][image]"
                        accept="{{ \App\Support\MediaUploadSupport::acceptAttribute('image') }}"
                        class="jb-input vp-input mt-2"
                        data-jb-max-mb="{{ $productImageMaxMb }}"
                        data-jb-file-label="Variant image"
                        data-variant-image-input
                    >
                </div>
                <div class="flex items-end">
                    <button type="button" class="jb-btn jb-btn-ghost jb-btn-sm text-rose-600" data-product-variants-remove>Remove</button>
                </div>
            </div>
        @endforeach
    </div>

    @error('variants')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    @error('variants.*')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    @error('variants.*.image')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror

    <template data-product-variants-template>
        <div class="grid gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_7rem_7rem_6rem_minmax(0,10rem)_auto]" data-product-variants-row>
            <div>
                <label class="jb-label">Size</label>
                <input type="text" name="variants[__INDEX__][size]" class="jb-input" placeholder="e.g. M, 32">
            </div>
            <div>
                <label class="jb-label">Color</label>
                <input type="text" name="variants[__INDEX__][color]" class="jb-input" placeholder="e.g. Red">
            </div>
            <div>
                <label class="jb-label">Price (₹)</label>
                <input type="text" name="variants[__INDEX__][price]" class="jb-input" inputmode="decimal" autocomplete="off" placeholder="0" data-jb-restrict="amount">
            </div>
            <div>
                <label class="jb-label">Advance (₹)</label>
                <input type="text" name="variants[__INDEX__][advance_amount]" class="jb-input" inputmode="decimal" autocomplete="off" placeholder="0" data-jb-restrict="amount">
            </div>
            <div>
                <label class="jb-label">Qty</label>
                <input type="text" name="variants[__INDEX__][quantity]" class="jb-input" inputmode="numeric" autocomplete="off" placeholder="1" data-jb-restrict="integer">
            </div>
            <div data-variant-image-field>
                <label class="jb-label">Variant image</label>
                <div class="jb-variant-image-preview" data-variant-image-preview hidden>
                    <img src="" alt="Variant image preview" class="jb-variant-image-preview__img panel-lightbox-trigger" data-variant-image-thumb>
                    <p class="jb-variant-image-preview__hint" data-variant-image-hint></p>
                </div>
                <div class="jb-variant-image-preview jb-variant-image-preview--empty" data-variant-image-empty>
                    <span>No image</span>
                </div>
                <input type="hidden" name="variants[__INDEX__][stored_image_path]" value="">
                <input
                    type="file"
                    name="variants[__INDEX__][image]"
                    accept="{{ \App\Support\MediaUploadSupport::acceptAttribute('image') }}"
                    class="jb-input mt-2"
                    data-jb-max-mb="{{ $productImageMaxMb }}"
                    data-jb-file-label="Variant image"
                    data-variant-image-input
                >
            </div>
            <div class="flex items-end">
                <button type="button" class="jb-btn jb-btn-ghost jb-btn-sm text-rose-600" data-product-variants-remove>Remove</button>
            </div>
        </div>
    </template>
</div>

<script>
    (function () {
        const root = document.querySelector('[data-product-variants]');
        if (!root) return;

        const list = root.querySelector('[data-product-variants-list]');
        const template = root.querySelector('[data-product-variants-template]');
        const addBtn = root.querySelector('[data-product-variants-add]');
        const form = root.closest('form');
        const maxBytes = {{ (int) $productImageMaxMb }} * 1024 * 1024;

        const variantsEnabled = () => {
            const host = root.closest('[data-variants-enabled]');
            return !host || host.getAttribute('data-variants-enabled') !== '0';
        };

        const setInputsEnabled = (enabled) => {
            root.querySelectorAll('input, select, textarea, button').forEach((el) => {
                if (el.hasAttribute('data-product-variants-add') || el.hasAttribute('data-product-variants-remove')) {
                    el.disabled = !enabled;
                    return;
                }
                if (el.type === 'hidden' && el.name && el.name.includes('stored_image_path')) {
                    el.disabled = !enabled;
                    return;
                }
                if (el.name && el.name.startsWith('variants[')) {
                    el.disabled = !enabled;
                }
            });
        };

        const syncEnabledState = () => setInputsEnabled(variantsEnabled());

        const revokePreviewUrl = (row) => {
            const url = row.dataset.variantPreviewUrl;
            if (url && url.startsWith('blob:')) {
                URL.revokeObjectURL(url);
            }
            delete row.dataset.variantPreviewUrl;
        };

        const showPreview = (row, url, hint) => {
            const preview = row.querySelector('[data-variant-image-preview]');
            const empty = row.querySelector('[data-variant-image-empty]');
            const thumb = row.querySelector('[data-variant-image-thumb]');
            const hintEl = row.querySelector('[data-variant-image-hint]');
            if (!preview || !empty || !thumb) return;

            if (url) {
                thumb.src = url;
                preview.hidden = false;
                empty.hidden = true;
                if (hintEl) hintEl.textContent = hint || '';
            } else {
                thumb.removeAttribute('src');
                preview.hidden = true;
                empty.hidden = false;
                if (hintEl) hintEl.textContent = '';
            }
        };

        const clearImageField = (row) => {
            revokePreviewUrl(row);
            const file = row.querySelector('[data-variant-image-input]');
            if (file) file.value = '';
            const stored = row.querySelector('input[name*="[stored_image_path]"]');
            const current = stored?.value ? (row.querySelector('[data-variant-image-thumb]')?.getAttribute('src') || '') : '';
            // After clearing a new file, fall back to stored current image if still present in DOM dataset.
            const fallback = row.dataset.variantCurrentUrl || '';
            if (file && !file.files?.length && fallback) {
                showPreview(row, fallback, 'Current image');
            } else {
                showPreview(row, '', '');
            }
        };

        const bindImagePreview = (row) => {
            const fileInput = row.querySelector('[data-variant-image-input]');
            if (!fileInput || fileInput.dataset.previewBound === '1') return;
            fileInput.dataset.previewBound = '1';

            const thumb = row.querySelector('[data-variant-image-thumb]');
            if (thumb?.getAttribute('src')) {
                row.dataset.variantCurrentUrl = thumb.getAttribute('src');
            }

            fileInput.addEventListener('change', () => {
                const file = fileInput.files && fileInput.files[0];
                revokePreviewUrl(row);

                if (!file) {
                    const fallback = row.dataset.variantCurrentUrl || '';
                    showPreview(row, fallback, fallback ? 'Current image' : '');
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    fileInput.value = '';
                    const fallback = row.dataset.variantCurrentUrl || '';
                    showPreview(row, fallback, fallback ? 'Current image' : '');
                    window.alert('Please choose an image file for the variant.');
                    return;
                }

                if (file.size > maxBytes) {
                    fileInput.value = '';
                    const fallback = row.dataset.variantCurrentUrl || '';
                    showPreview(row, fallback, fallback ? 'Current image' : '');
                    window.alert('Variant image is too large. Maximum size is {{ $productImageMaxMb }} MB.');
                    return;
                }

                const stored = row.querySelector('input[name*="[stored_image_path]"]');
                if (stored) stored.value = '';

                const url = URL.createObjectURL(file);
                row.dataset.variantPreviewUrl = url;
                showPreview(row, url, 'New upload preview');
            });
        };

        const reindexRows = () => {
            list.querySelectorAll('[data-product-variants-row]').forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    if (!input.name) return;
                    input.name = input.name.replace(/variants\[\d+\]/, `variants[${index}]`);
                });
            });
        };

        const bindAmountRestrictions = (row) => {
            row.querySelectorAll('[data-jb-restrict]').forEach((input) => {
                if (typeof window.jbBindRestriction === 'function') {
                    window.jbBindRestriction(input);
                }
            });
        };

        const bindRemove = (row) => {
            row.querySelector('[data-product-variants-remove]')?.addEventListener('click', () => {
                if (list.querySelectorAll('[data-product-variants-row]').length <= 1) {
                    row.querySelectorAll('input[type="text"], input[type="hidden"]').forEach((input) => {
                        if (input.name && input.name.includes('[stored_image_path]')) {
                            input.value = '';
                            return;
                        }
                        input.value = '';
                    });
                    delete row.dataset.variantCurrentUrl;
                    clearImageField(row);
                    showPreview(row, '', '');
                    return;
                }
                revokePreviewUrl(row);
                row.remove();
                reindexRows();
            });
        };

        list.querySelectorAll('[data-product-variants-row]').forEach((row) => {
            bindRemove(row);
            bindImagePreview(row);
            bindAmountRestrictions(row);
        });

        addBtn?.addEventListener('click', () => {
            if (!variantsEnabled()) return;
            const index = list.querySelectorAll('[data-product-variants-row]').length;
            const html = template.innerHTML.replaceAll('__INDEX__', String(index));
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const row = wrapper.firstElementChild;
            list.appendChild(row);
            bindRemove(row);
            bindImagePreview(row);
            bindAmountRestrictions(row);
            syncEnabledState();
            row.querySelector('input')?.focus();
        });

        const categorySelect = form?.querySelector('#category_id');
        categorySelect?.addEventListener('change', () => {
            setTimeout(syncEnabledState, 0);
        });

        const host = root.closest('[data-variants-enabled]') || root.parentElement;
        if (host) {
            const observer = new MutationObserver(syncEnabledState);
            observer.observe(host, { attributes: true, attributeFilter: ['data-variants-enabled'] });
        }

        syncEnabledState();
    })();
</script>

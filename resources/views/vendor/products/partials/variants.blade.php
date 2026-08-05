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
                'stored_image_path' => $v->image_path,
            ])->all()
            : [];
    }
    if ($variantRows === []) {
        $variantRows = [['size' => '', 'color' => '', 'price' => '', 'image_url' => null, 'stored_image_path' => null]];
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
                        <label class="vp-label">Price Per Day</label>
                        <input type="number" name="variants[{{ $index }}][price]" value="{{ $variant['price'] ?? '' }}" class="vp-input" min="0" step="0.01" placeholder="0" inputmode="decimal" data-vp-restrict="amount">
                    </div>
                    <div data-variant-image-field>
                        <label class="vp-label">Variant image</label>
                        <div class="vp-variant-image-preview" data-variant-image-preview @if (empty($variant['image_url'])) hidden @endif>
                            <img
                                src="{{ $variant['image_url'] ?? '' }}"
                                alt="Variant image preview"
                                class="vp-variant-image-preview__img panel-lightbox-trigger"
                                data-variant-image-thumb
                            >
                            <p class="vp-variant-image-preview__hint" data-variant-image-hint>{{ ! empty($variant['image_url']) ? 'Current image' : '' }}</p>
                        </div>
                        <div class="vp-variant-image-preview vp-variant-image-preview--empty" data-variant-image-empty @if (! empty($variant['image_url'])) hidden @endif>
                            <span>No image</span>
                        </div>
                        <input type="hidden" name="variants[{{ $index }}][stored_image_path]" value="{{ $variant['stored_image_path'] ?? '' }}">
                        <input
                            type="file"
                            name="variants[{{ $index }}][image]"
                            accept="{{ \App\Support\MediaUploadSupport::acceptAttribute('image') }}"
                            class="vp-file vp-input"
                            data-vp-max-file-bytes="{{ VendorValidationRules::MAX_IMAGE_KB * 1024 }}"
                            data-vp-file-label="Variant image"
                            data-variant-image-input
                        >
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
    @error('variants.*.image')<p class="vp-field-error">{{ $message }}</p>@enderror

    <template data-vp-variants-template>
        <div class="vp-repeat-row" data-vp-variants-row>
            <div class="vp-repeat-row-grid">
                <div><label class="vp-label">Size</label><input type="text" name="variants[__INDEX__][size]" class="vp-input" placeholder="e.g. M, 32"></div>
                <div><label class="vp-label">Color</label><input type="text" name="variants[__INDEX__][color]" class="vp-input" placeholder="e.g. Red"></div>
                <div><label class="vp-label">Price Per Day</label><input type="number" name="variants[__INDEX__][price]" class="vp-input" min="0" step="0.01" placeholder="0" inputmode="decimal" data-vp-restrict="amount"></div>
                <div data-variant-image-field>
                    <label class="vp-label">Variant image</label>
                    <div class="vp-variant-image-preview" data-variant-image-preview hidden>
                        <img src="" alt="Variant image preview" class="vp-variant-image-preview__img panel-lightbox-trigger" data-variant-image-thumb>
                        <p class="vp-variant-image-preview__hint" data-variant-image-hint></p>
                    </div>
                    <div class="vp-variant-image-preview vp-variant-image-preview--empty" data-variant-image-empty>
                        <span>No image</span>
                    </div>
                    <input type="hidden" name="variants[__INDEX__][stored_image_path]" value="">
                    <input
                        type="file"
                        name="variants[__INDEX__][image]"
                        accept="{{ \App\Support\MediaUploadSupport::acceptAttribute('image') }}"
                        class="vp-file vp-input"
                        data-vp-max-file-bytes="{{ VendorValidationRules::MAX_IMAGE_KB * 1024 }}"
                        data-vp-file-label="Variant image"
                        data-variant-image-input
                    >
                </div>
                <div><button type="button" class="vp-btn vp-btn--ghost vp-btn--sm" style="color:#dc2626;" data-vp-variants-remove>Remove</button></div>
            </div>
        </div>
    </template>
</div>

@once
@push('styles')
<style>
    .vp-variant-image-preview { display:flex; flex-direction:column; gap:.3rem; margin-bottom:.35rem; }
    .vp-variant-image-preview__img {
        display:block; width:4.5rem; height:4.5rem; object-fit:cover; border-radius:8px;
        border:1px solid #e5e7eb; background:#f8fafc; cursor:zoom-in;
    }
    .vp-variant-image-preview__hint { margin:0; font-size:.7rem; color:#64748b; }
    .vp-variant-image-preview--empty {
        display:flex; align-items:center; justify-content:center;
        width:4.5rem; height:4.5rem; border-radius:8px; border:1px dashed #cbd5e1;
        background:#f8fafc; color:#94a3b8; font-size:.7rem; margin-bottom:.35rem;
    }
    .vp-variant-image-preview[hidden],
    .vp-variant-image-preview--empty[hidden] { display:none !important; }
</style>
@endpush
@endonce

@once
@push('scripts')
<script>
(function () {
    const roots = document.querySelectorAll('[data-vp-variants]');
    if (!roots.length) return;

    roots.forEach((root) => {
        if (root.dataset.variantPreviewInit === '1') return;
        root.dataset.variantPreviewInit = '1';

        const list = root.querySelector('[data-vp-variants-list]');
        const template = root.querySelector('[data-vp-variants-template]');
        const addBtn = root.querySelector('[data-vp-variants-add]');
        const maxBytes = {{ (int) VendorValidationRules::MAX_IMAGE_KB }} * 1024;

        const revokePreviewUrl = (row) => {
            const url = row.dataset.variantPreviewUrl;
            if (url && url.startsWith('blob:')) URL.revokeObjectURL(url);
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
            list.querySelectorAll('[data-vp-variants-row]').forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    if (!input.name) return;
                    input.name = input.name.replace(/variants\[\d+\]/, `variants[${index}]`);
                });
            });
        };

        const bindRemove = (row) => {
            row.querySelector('[data-vp-variants-remove]')?.addEventListener('click', () => {
                if (list.querySelectorAll('[data-vp-variants-row]').length <= 1) {
                    row.querySelectorAll('input[type="text"], input[type="number"], input[type="hidden"]').forEach((input) => input.value = '');
                    const file = row.querySelector('input[type="file"]');
                    if (file) file.value = '';
                    delete row.dataset.variantCurrentUrl;
                    revokePreviewUrl(row);
                    showPreview(row, '', '');
                    return;
                }
                revokePreviewUrl(row);
                row.remove();
                reindexRows();
            });
        };

        list.querySelectorAll('[data-vp-variants-row]').forEach((row) => {
            bindRemove(row);
            bindImagePreview(row);
        });

        addBtn?.addEventListener('click', () => {
            const index = list.querySelectorAll('[data-vp-variants-row]').length;
            const html = template.innerHTML.replaceAll('__INDEX__', String(index));
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const row = wrapper.firstElementChild;
            list.appendChild(row);
            bindRemove(row);
            bindImagePreview(row);
            row.querySelector('input')?.focus();
        });
    });
})();
</script>
@endpush
@endonce

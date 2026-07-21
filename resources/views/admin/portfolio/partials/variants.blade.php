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
                    <label class="jb-label">Price (₹)</label>
                    <input type="number" name="variants[{{ $index }}][price]" value="{{ $variant['price'] ?? '' }}" class="jb-input" min="0" step="0.01" placeholder="0">
                </div>
                <div>
                    <label class="jb-label">Advance (₹)</label>
                    <input type="number" name="variants[{{ $index }}][advance_amount]" value="{{ $variant['advance_amount'] ?? '' }}" class="jb-input" min="0" step="0.01" placeholder="0">
                </div>
                <div>
                    <label class="jb-label">Qty</label>
                    <input type="number" name="variants[{{ $index }}][quantity]" value="{{ $variant['quantity'] ?? '' }}" class="jb-input" min="0" step="1" placeholder="1">
                </div>
                <div>
                    <label class="jb-label">Variant image</label>
                    @if (! empty($variant['image_url']))
                        <img src="{{ $variant['image_url'] }}" alt="" class="mb-2 h-12 w-12 rounded-lg object-cover ring-1 ring-slate-200 panel-lightbox-trigger">
                    @endif
                    <input type="hidden" name="variants[{{ $index }}][stored_image_path]" value="{{ $variant['stored_image_path'] ?? '' }}">
                    <input type="file" name="variants[{{ $index }}][image]" accept="image/jpeg,image/jpg,image/png,image/webp" class="jb-input vp-input" data-jb-max-mb="{{ $productImageMaxMb }}" data-jb-file-label="Variant image">
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
                <input type="number" name="variants[__INDEX__][price]" class="jb-input" min="0" step="0.01" placeholder="0">
            </div>
            <div>
                <label class="jb-label">Advance (₹)</label>
                <input type="number" name="variants[__INDEX__][advance_amount]" class="jb-input" min="0" step="0.01" placeholder="0">
            </div>
            <div>
                <label class="jb-label">Qty</label>
                <input type="number" name="variants[__INDEX__][quantity]" class="jb-input" min="0" step="1" placeholder="1">
            </div>
            <div>
                <label class="jb-label">Variant image</label>
                <input type="hidden" name="variants[__INDEX__][stored_image_path]" value="">
                <input type="file" name="variants[__INDEX__][image]" accept="image/jpeg,image/jpg,image/png,image/webp" class="jb-input" data-jb-max-mb="{{ $productImageMaxMb }}" data-jb-file-label="Variant image">
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

        const reindexRows = () => {
            list.querySelectorAll('[data-product-variants-row]').forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    if (!input.name) return;
                    input.name = input.name.replace(/variants\[\d+\]/, `variants[${index}]`);
                });
            });
        };

        const bindRemove = (row) => {
            row.querySelector('[data-product-variants-remove]')?.addEventListener('click', () => {
                if (list.querySelectorAll('[data-product-variants-row]').length <= 1) {
                    row.querySelectorAll('input[type="text"], input[type="number"], input[type="hidden"]').forEach((input) => input.value = '');
                    const file = row.querySelector('input[type="file"]');
                    if (file) file.value = '';
                    return;
                }
                row.remove();
                reindexRows();
            });
        };

        list.querySelectorAll('[data-product-variants-row]').forEach(bindRemove);

        addBtn?.addEventListener('click', () => {
            if (!variantsEnabled()) return;
            const index = list.querySelectorAll('[data-product-variants-row]').length;
            const html = template.innerHTML.replaceAll('__INDEX__', String(index));
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const row = wrapper.firstElementChild;
            list.appendChild(row);
            bindRemove(row);
            syncEnabledState();
            row.querySelector('input')?.focus();
        });

        const categorySelect = form?.querySelector('#category_id');
        categorySelect?.addEventListener('change', () => {
            // Alpine updates data-variants-enabled on next tick.
            setTimeout(syncEnabledState, 0);
        });

        // Observe Alpine attribute changes.
        const host = root.closest('[data-variants-enabled]') || root.parentElement;
        if (host) {
            const observer = new MutationObserver(syncEnabledState);
            observer.observe(host, { attributes: true, attributeFilter: ['data-variants-enabled'] });
        }

        syncEnabledState();
    })();
</script>

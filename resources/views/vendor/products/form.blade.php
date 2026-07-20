@extends('vendor.layouts.app')

@section('title', ($item->exists ? 'Edit' : 'Add').' '.$typeLabel)

@section('content')
@php
    $isCreate = ! $item->exists;
    $isRentalDress = ($type ?? '') === 'rented-dress';
    $isRentalJewellery = ($type ?? '') === 'rented-jewellery';
    if ($isRentalDress) {
        $formTitle = $isCreate ? 'Add New Dress' : 'Edit Dress';
        $submitLabel = $isCreate ? 'Add Design' : 'Save changes';
    } elseif ($isRentalJewellery) {
        $formTitle = $isCreate ? 'Add New Jewelry' : 'Edit Jewelry';
        $submitLabel = $isCreate ? 'Add Design' : 'Save changes';
    } else {
        $formTitle = $isCreate ? 'Add New Design' : 'Edit Design';
        $submitLabel = $isCreate ? 'Add Design' : 'Save changes';
    }
@endphp

<div class="vp-card vp-product-form">
    <div class="vp-product-form-head">
        <a href="{{ route('vendor.products.index', ['type' => $type]) }}" class="vp-product-form-back">
            <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ $formTitle }}
        </a>
    </div>

    <div class="vp-product-form-body">
        @if ($errors->any())
            <div class="vp-alert vp-alert--error" style="margin-bottom:1rem;">
                <p style="margin:0 0 .35rem;font-weight:700;">Please fix the following:</p>
                <ul style="margin:0;padding-left:1.1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="vendor-product-form" method="POST" action="{{ $item->exists ? route('vendor.products.update', $item) : route('vendor.products.store') }}" enctype="multipart/form-data" data-vp-product-form>
            @csrf
            @if ($item->exists) @method('PUT') @endif
            @include('vendor.products._form')

            <div class="vp-product-form-actions">
                <button type="submit" class="vp-btn vp-btn--primary">{{ $submitLabel }}</button>
            </div>
        </form>

        @if ($item->exists && $item->relationLoaded('images'))
            @foreach ($item->images as $image)
                <form id="vendor-delete-gallery-{{ $image->id }}" method="POST" action="{{ route('vendor.products.images.destroy', [$item, $image]) }}" hidden
                      data-vp-confirm="This gallery image will be permanently removed."
                      data-vp-confirm-title="Remove image?"
                      data-vp-confirm-label="Remove"
                      data-vp-confirm-variant="error">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const root = document.querySelector('[data-vp-product-form]');
    if (!root) return;

    const appendTemplateRow = (template, list, index, bindRow) => {
        if (!template?.content?.firstElementChild) return;
        const row = template.content.firstElementChild.cloneNode(true);
        row.querySelectorAll('[name]').forEach((input) => {
            if (input.name) {
                input.name = input.name.replace(/__INDEX__/g, String(index));
            }
        });
        list.appendChild(row);
        bindRow(row);
    };

    const initRepeatable = (config) => {
        const section = root.querySelector(config.rootSelector);
        if (!section) return;

        const list = section.querySelector(config.listSelector);
        const template = section.querySelector(config.templateSelector);
        const addBtn = section.querySelector(config.addSelector);
        const namePattern = config.namePattern;

        const reindex = () => {
            list.querySelectorAll(config.rowSelector).forEach((row, index) => {
                row.querySelectorAll('input, select, textarea').forEach((input) => {
                    if (input.name) {
                        input.name = input.name.replace(namePattern, `${config.namePrefix}[${index}]`);
                    }
                });
            });
        };

        const bindRow = (row) => {
            config.onBind?.(row);
            const btn = row.querySelector(config.removeSelector);
            if (!btn) return;
            btn.addEventListener('click', () => {
                if (config.minRows && list.querySelectorAll(config.rowSelector).length <= config.minRows) {
                    row.querySelectorAll('input[type="text"], input[type="number"], select').forEach((input) => {
                        input.value = '';
                    });
                    const file = row.querySelector('input[type="file"]');
                    if (file) file.value = '';
                    const nameEl = row.querySelector('[data-vp-color-file-name]');
                    if (nameEl) nameEl.textContent = 'No file chosen';
                    return;
                }
                row.remove();
                reindex();
            });
        };

        list.querySelectorAll(config.rowSelector).forEach(bindRow);

        addBtn?.addEventListener('click', () => {
            const index = list.querySelectorAll(config.rowSelector).length;
            appendTemplateRow(template, list, index, bindRow);
        });
    };

    const bindColorFileName = (row) => {
        const input = row.querySelector('input[type="file"]');
        const nameEl = row.querySelector('[data-vp-color-file-name]');
        if (!input || !nameEl) return;
        input.addEventListener('change', () => {
            nameEl.textContent = input.files?.[0]?.name || 'No file chosen';
        });
    };

    root.querySelectorAll('[data-vp-dropzone]').forEach((zone) => {
        const input = zone.querySelector('input[type="file"]');
        const nameEl = zone.querySelector('[data-vp-dropzone-name]');
        if (!input) return;

        const updateName = () => {
            if (!nameEl) return;
            const files = input.files;
            if (!files || files.length === 0) {
                nameEl.textContent = zone.dataset.emptyText || 'No file chosen';
                return;
            }
            if (files.length === 1) {
                nameEl.textContent = files[0].name;
                return;
            }
            nameEl.textContent = files.length + ' files selected';
        };

        zone.addEventListener('click', (e) => {
            if (e.target === input) return;
            input.click();
        });

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('is-dragover');
        });
        zone.addEventListener('dragleave', () => zone.classList.remove('is-dragover'));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('is-dragover');
            if (!e.dataTransfer?.files?.length) return;
            try {
                const dt = new DataTransfer();
                const max = input.multiple ? e.dataTransfer.files.length : 1;
                for (let i = 0; i < max; i++) {
                    dt.items.add(e.dataTransfer.files[i]);
                }
                input.files = dt.files;
            } catch (_) {
                return;
            }
            input.dispatchEvent(new Event('change', { bubbles: true }));
            updateName();
        });
        input.addEventListener('change', updateName);
        updateName();
    });

    initRepeatable({
        rootSelector: '[data-vp-damage]',
        listSelector: '[data-vp-damage-list]',
        templateSelector: '[data-vp-damage-template]',
        addSelector: '[data-vp-damage-add]',
        rowSelector: '[data-vp-damage-row]',
        removeSelector: '[data-vp-damage-remove]',
        namePattern: /damage_deductions\[\d+]/,
        namePrefix: 'damage_deductions',
        minRows: 1,
    });

    initRepeatable({
        rootSelector: '[data-vp-colors]',
        listSelector: '[data-vp-colors-list]',
        templateSelector: '[data-vp-colors-template]',
        addSelector: '[data-vp-colors-add]',
        rowSelector: '[data-vp-colors-row]',
        removeSelector: '[data-vp-colors-remove]',
        namePattern: /colors\[\d+]/,
        namePrefix: 'colors',
        minRows: 1,
        onBind: bindColorFileName,
    });

    // Rental dress/jewelry: media preview slots
    (function initDressMedia() {
        const wrap = root.querySelector('[data-vp-dress-media]');
        if (!wrap) return;
        const input = wrap.querySelector('[data-vp-dress-media-input]');
        const slots = Array.from(wrap.querySelectorAll('[data-vp-dress-media-preview]'));
        if (!input || !slots.length) return;

        const clearNewPreviews = (slot) => {
            slot.querySelectorAll('[data-vp-new-preview]').forEach((el) => el.remove());
        };

        const render = () => {
            const files = Array.from(input.files || []);
            const emptySlots = slots.filter((slot) => !slot.querySelector('[data-vp-existing-media]'));

            slots.forEach((slot) => {
                clearNewPreviews(slot);
                const existing = slot.querySelector('[data-vp-existing-media]');
                const emptyIcon = slot.querySelector('.vp-dress-media-empty-icon');
                if (existing) {
                    existing.hidden = false;
                    slot.classList.add('has-file');
                    if (emptyIcon) emptyIcon.hidden = true;
                } else {
                    slot.classList.remove('has-file');
                    if (emptyIcon) emptyIcon.hidden = false;
                }
            });

            files.forEach((file, index) => {
                const slot = emptySlots[index];
                if (!slot) return;
                const existing = slot.querySelector('[data-vp-existing-media]');
                const emptyIcon = slot.querySelector('.vp-dress-media-empty-icon');
                if (existing) existing.hidden = true;
                if (emptyIcon) emptyIcon.hidden = true;
                slot.classList.add('has-file');

                if (file.type.startsWith('video/')) {
                    const badge = document.createElement('span');
                    badge.className = 'vp-dress-media-video-badge';
                    badge.dataset.vpNewPreview = '1';
                    badge.textContent = 'Video';
                    slot.appendChild(badge);
                    return;
                }

                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = '';
                img.dataset.vpNewPreview = '1';
                slot.appendChild(img);
            });
        };

        input.addEventListener('change', render);
        wrap.addEventListener('dragover', (e) => {
            e.preventDefault();
            wrap.classList.add('is-dragover');
        });
        wrap.addEventListener('dragleave', () => wrap.classList.remove('is-dragover'));
        wrap.addEventListener('drop', (e) => {
            e.preventDefault();
            wrap.classList.remove('is-dragover');
            if (!e.dataTransfer?.files?.length) return;
            try {
                const dt = new DataTransfer();
                Array.from(e.dataTransfer.files).forEach((file) => dt.items.add(file));
                input.files = dt.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (_) {}
        });
    })();

    // Rental dress: variant composer + cards
    (function initDressVariants() {
        const section = root.querySelector('[data-vp-dress-variants]');
        if (!section) return;

        const toggle = section.querySelector('[data-vp-dress-variants-toggle]');
        const composer = section.querySelector('[data-vp-dress-variant-composer]');
        const list = section.querySelector('[data-vp-dress-variant-list]');
        const template = section.querySelector('[data-vp-dress-variant-template]');
        const addBtn = section.querySelector('[data-vp-dress-variant-add]');
        const sizeEl = section.querySelector('[data-vp-variant-size]');
        const colorEl = section.querySelector('[data-vp-variant-color]');
        const priceEl = section.querySelector('[data-vp-variant-price]');
        const fileEl = section.querySelector('[data-vp-variant-file]');
        const filePreview = section.querySelector('[data-vp-variant-file-preview]');
        const fileEmpty = section.querySelector('[data-vp-variant-file-empty]');
        const colorCss = {
            black: '#111111', white: '#ffffff', red: '#e11d48', blue: '#2563eb',
            green: '#16a34a', pink: '#ec4899', gold: '#ca8a04', silver: '#a8a29e',
            maroon: '#9f1239', ivory: '#fffff0', 'navy blue': '#1e3a8a', 'rose gold': '#b76e79',
        };

        let editingRow = null;
        let draftObjectUrl = null;

        const reindex = () => {
            list.querySelectorAll('[data-vp-dress-variant-row]').forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    if (!input.name) return;
                    input.name = input.name.replace(/variants\[(?:\d+|__INDEX__)\]/, `variants[${index}]`);
                });
            });
        };

        const resetComposer = () => {
            editingRow = null;
            if (sizeEl) sizeEl.value = '';
            if (colorEl) colorEl.value = '';
            if (priceEl) priceEl.value = '';
            if (fileEl) fileEl.value = '';
            if (draftObjectUrl) {
                URL.revokeObjectURL(draftObjectUrl);
                draftObjectUrl = null;
            }
            if (filePreview) {
                filePreview.hidden = true;
                filePreview.style.display = 'none';
                const img = filePreview.querySelector('img');
                if (img) {
                    img.removeAttribute('src');
                    img.alt = '';
                }
            }
            if (fileEmpty) {
                fileEmpty.hidden = false;
                fileEmpty.style.display = '';
            }
            if (addBtn) addBtn.textContent = 'Add Variant';
        };

        const updateCardLabels = (row, size, color, price) => {
            const sizeLabel = row.querySelector('[data-vp-variant-size-label]');
            const colorLabel = row.querySelector('[data-vp-variant-color-label]');
            const priceLabel = row.querySelector('[data-vp-variant-price-label]');
            if (sizeLabel) sizeLabel.textContent = (size || '—').toUpperCase();
            if (colorLabel) {
                colorLabel.textContent = (color || '—').toUpperCase();
                colorLabel.style.color = colorCss[(color || '').toLowerCase()] || '#ec4899';
            }
            if (priceLabel) {
                const amount = Number(price || 0);
                priceLabel.textContent = '₹' + (Number.isFinite(amount) ? amount.toLocaleString('en-IN', { maximumFractionDigits: 0 }) : '0');
            }
        };

        const setCardThumb = (row, objectUrl) => {
            const thumb = row.querySelector('[data-vp-variant-thumb]');
            const empty = row.querySelector('[data-vp-variant-thumb-empty]');
            if (!thumb) return;
            if (objectUrl) {
                thumb.src = objectUrl;
                thumb.hidden = false;
                thumb.style.display = 'block';
                if (empty) {
                    empty.hidden = true;
                    empty.style.display = 'none';
                }
            } else {
                thumb.removeAttribute('src');
                thumb.hidden = true;
                thumb.style.display = 'none';
                if (empty) {
                    empty.hidden = false;
                    empty.style.display = '';
                }
            }
        };

        const assignFileToInput = (fileInput, file) => {
            if (!fileInput || !file) return false;
            try {
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
                return fileInput.files.length > 0;
            } catch (_) {
                return false;
            }
        };

        const assignImageToRow = (row, file) => {
            if (!row || !file) return Promise.resolve(false);

            const imageInput = row.querySelector('[data-vp-variant-image-input]');
            const base64Input = row.querySelector('[data-vp-variant-image-base64]');
            const storedInput = row.querySelector('[data-vp-variant-stored-path]');
            const assigned = assignFileToInput(imageInput, file);

            // Prefer real file upload. Only fall back to base64 when DataTransfer fails
            // (sending both often exceeds post_max_size and drops the update).
            if (assigned) {
                if (base64Input) base64Input.value = '';
                if (storedInput) storedInput.value = '';
                setCardThumb(row, URL.createObjectURL(file));
                return Promise.resolve(true);
            }

            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = () => {
                    const dataUrl = String(reader.result || '');
                    if (!dataUrl) {
                        resolve(false);
                        return;
                    }
                    if (base64Input) base64Input.value = dataUrl;
                    if (storedInput) storedInput.value = '';
                    setCardThumb(row, dataUrl);
                    resolve(true);
                };
                reader.onerror = () => resolve(false);
                reader.readAsDataURL(file);
            });
        };

        let imageAssignQueue = Promise.resolve();
        const queueImageAssign = (row, file) => {
            imageAssignQueue = imageAssignQueue.then(() => assignImageToRow(row, file));
            return imageAssignQueue;
        };

        const bindRow = (row) => {
            if (row.dataset.vpVariantBound === '1') return;
            row.dataset.vpVariantBound = '1';

            row.querySelector('[data-vp-dress-variant-remove]')?.addEventListener('click', () => {
                if (editingRow === row) resetComposer();
                row.remove();
                reindex();
            });

            row.querySelector('[data-vp-dress-variant-edit]')?.addEventListener('click', () => {
                editingRow = row;
                composer.hidden = false;
                if (sizeEl) sizeEl.value = row.querySelector('[data-vp-variant-size-input]')?.value || '';
                if (colorEl) colorEl.value = row.querySelector('[data-vp-variant-color-input]')?.value || '';
                if (priceEl) priceEl.value = row.querySelector('[data-vp-variant-price-input]')?.value || '';
                if (fileEl) fileEl.value = '';
                const thumb = row.querySelector('[data-vp-variant-thumb]');
                if (thumb && thumb.getAttribute('src') && !thumb.hidden) {
                    if (filePreview) {
                        filePreview.hidden = false;
                        filePreview.style.display = 'block';
                        const img = filePreview.querySelector('img');
                        if (img) img.src = thumb.src;
                    }
                    if (fileEmpty) {
                        fileEmpty.hidden = true;
                        fileEmpty.style.display = 'none';
                    }
                } else {
                    if (filePreview) {
                        filePreview.hidden = true;
                        filePreview.style.display = 'none';
                    }
                    if (fileEmpty) {
                        fileEmpty.hidden = false;
                        fileEmpty.style.display = '';
                    }
                }
                if (addBtn) addBtn.textContent = 'Update Variant';
                composer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        };

        list.querySelectorAll('[data-vp-dress-variant-row]').forEach((row) => {
            const thumb = row.querySelector('[data-vp-variant-thumb]');
            if (thumb && !thumb.getAttribute('src')) {
                thumb.hidden = true;
                thumb.style.display = 'none';
            }
            bindRow(row);
        });
        reindex();

        toggle?.addEventListener('click', () => {
            composer.hidden = !composer.hidden;
            if (!composer.hidden) {
                resetComposer();
            }
        });

        fileEl?.addEventListener('change', () => {
            const file = fileEl.files?.[0];
            if (draftObjectUrl) {
                URL.revokeObjectURL(draftObjectUrl);
                draftObjectUrl = null;
            }
            if (!file) {
                if (filePreview) {
                    filePreview.hidden = true;
                    filePreview.style.display = 'none';
                }
                if (fileEmpty) {
                    fileEmpty.hidden = false;
                    fileEmpty.style.display = '';
                }
                return;
            }
            draftObjectUrl = URL.createObjectURL(file);
            if (filePreview) {
                filePreview.hidden = false;
                filePreview.style.display = 'block';
                const img = filePreview.querySelector('img');
                if (img) img.src = draftObjectUrl;
            }
            if (fileEmpty) {
                fileEmpty.hidden = true;
                fileEmpty.style.display = 'none';
            }
        });

        addBtn?.addEventListener('click', () => {
            const size = (sizeEl?.value || '').trim();
            const color = (colorEl?.value || '').trim();
            const price = (priceEl?.value || '').trim();
            const file = fileEl?.files?.[0] || null;

            if (!size || !color) {
                window.alert('Please select size and color.');
                return;
            }

            let row = editingRow;
            if (!row) {
                if (!template?.content?.firstElementChild) return;
                const nextIndex = list.querySelectorAll('[data-vp-dress-variant-row]').length;
                row = template.content.firstElementChild.cloneNode(true);
                row.querySelectorAll('[name]').forEach((input) => {
                    if (input.name) {
                        input.name = input.name.replace(/__INDEX__/g, String(nextIndex));
                    }
                });
                list.appendChild(row);
                bindRow(row);
            }

            const sizeInput = row.querySelector('[data-vp-variant-size-input]');
            const colorInput = row.querySelector('[data-vp-variant-color-input]');
            const priceInput = row.querySelector('[data-vp-variant-price-input]');
            if (sizeInput) sizeInput.value = size;
            if (colorInput) colorInput.value = color;
            if (priceInput) priceInput.value = price || '0';
            updateCardLabels(row, size, color, price || '0');

            const finish = () => {
                reindex();
                resetComposer();
                composer.hidden = false;
            };

            if (file) {
                addBtn.disabled = true;
                queueImageAssign(row, file).then((ok) => {
                    addBtn.disabled = false;
                    if (!ok) {
                        window.alert('Could not attach the variant image. Please try another image.');
                        return;
                    }
                    finish();
                }).catch(() => {
                    addBtn.disabled = false;
                    window.alert('Could not attach the variant image. Please try another image.');
                });
                return;
            }

            finish();
        });

        root.closest('form')?.addEventListener('submit', (event) => {
            if (addBtn?.disabled) {
                event.preventDefault();
                window.alert('Please wait for the variant image to finish attaching.');
            }
        });

        if (list.querySelectorAll('[data-vp-dress-variant-row]').length > 0) {
            composer.hidden = false;
        }
    })();
})();
</script>
@endpush

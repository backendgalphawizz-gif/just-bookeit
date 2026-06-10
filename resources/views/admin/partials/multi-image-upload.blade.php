@php
    $name = $name ?? 'images';
    $label = $label ?? 'Images';
    $existingImages = $existingImages ?? collect();
    $removeField = $removeField ?? 'remove_'.$name.'_ids';
    $maxFiles = $maxFiles ?? 12;
@endphp

<div
    class="jb-multi-image-upload"
    x-data="{
        newPreviews: [],
        fileError: null,
        maxBytes: 4 * 1024 * 1024,
        maxFiles: {{ (int) $maxFiles }},
        pickFiles(event) {
            const input = event.target;
            const files = input.files ? Array.from(input.files) : [];
            this.fileError = null;
            this.clearNewPreviews();

            if (files.length > this.maxFiles) {
                this.fileError = 'You can add up to ' + this.maxFiles + ' photos at a time.';
                input.value = '';
                return;
            }

            for (const file of files) {
                if (file.size > this.maxBytes) {
                    this.fileError = 'One of the photos is too large. Please choose a smaller image.';
                    input.value = '';
                    this.clearNewPreviews();
                    return;
                }
            }

            this.newPreviews = files.map((file) => ({
                id: crypto.randomUUID(),
                name: file.name,
                url: URL.createObjectURL(file),
                file: file,
            }));
        },
        removeNewPreview(index) {
            const item = this.newPreviews[index];
            if (!item) {
                return;
            }
            URL.revokeObjectURL(item.url);
            this.newPreviews.splice(index, 1);
            this.syncInputFiles();
            this.fileError = null;
        },
        syncInputFiles() {
            const input = this.$refs.fileInput;
            if (!input) {
                return;
            }
            const transfer = new DataTransfer();
            this.newPreviews.forEach((item) => transfer.items.add(item.file));
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        },
        clearNewPreviews() {
            this.newPreviews.forEach((item) => URL.revokeObjectURL(item.url));
            this.newPreviews = [];
        },
    }"
>
    <label class="jb-label">{{ $label }}</label>
    <p class="mb-3 text-sm text-slate-500">Add shop photos below. Tap the small × on a photo to remove it before saving.</p>

    @if ($existingImages->isNotEmpty())
        <div class="mb-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Current images</p>
            <div class="jb-multi-image-upload-grid">
                @foreach ($existingImages as $image)
                    <div
                        class="jb-multi-image-upload-item"
                        x-data="{ marked: false }"
                        :class="{ 'jb-multi-image-upload-item--marked': marked }"
                    >
                        <div class="jb-multi-image-upload-item__media">
                            <img src="{{ $image->imageUrl() }}" alt="Shop image" class="panel-lightbox-trigger">
                            <button
                                type="button"
                                class="jb-multi-image-upload-item__dismiss"
                                :class="{ 'jb-multi-image-upload-item__dismiss--active': marked }"
                                :title="marked ? 'Undo remove' : 'Remove image'"
                                :aria-label="marked ? 'Undo remove' : 'Remove image'"
                                @click="marked = !marked; $refs.removeCheckbox.checked = marked"
                            >
                                <svg aria-hidden="true" width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1l6 6M7 1L1 7" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                        <input
                            type="checkbox"
                            x-ref="removeCheckbox"
                            name="{{ $removeField }}[]"
                            value="{{ $image->id }}"
                            class="jb-sr-only"
                        >
                        <span class="jb-multi-image-upload-item__status" x-show="marked" x-cloak>Removed</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <template x-if="newPreviews.length > 0">
        <div class="mb-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">New upload preview</p>
            <div class="jb-multi-image-upload-grid">
                <template x-for="(item, index) in newPreviews" :key="item.id">
                    <div class="jb-multi-image-upload-item jb-multi-image-upload-item--preview">
                        <div class="jb-multi-image-upload-item__media">
                            <img :src="item.url" :alt="item.name">
                            <button
                                type="button"
                                class="jb-multi-image-upload-item__dismiss"
                                title="Remove from upload"
                                aria-label="Remove from upload"
                                @click="removeNewPreview(index)"
                            >
                                <svg aria-hidden="true" width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1l6 6M7 1L1 7" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                        <span class="jb-multi-image-upload-item__label" x-text="item.name"></span>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <input
        x-ref="fileInput"
        type="file"
        name="{{ $name }}[]"
        accept="image/png,image/jpeg,image/jpg,image/webp"
        class="jb-input"
        multiple
        data-jb-max-mb="4"
        @change="pickFiles($event)"
    >
    <p class="mt-1 text-xs text-slate-500">
        @if (! empty($hint))
            {{ $hint }}
        @else
            PNG, JPG, or WebP · max 4 MB each · up to {{ $maxFiles }} images
        @endif
    </p>

    <div x-show="fileError" x-cloak class="jb-upload-hint-alert mt-2" role="alert" x-text="fileError"></div>

    @error($name)
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
    @error($name.'.*')
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>

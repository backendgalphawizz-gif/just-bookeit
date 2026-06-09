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
                this.fileError = 'You can upload up to ' + this.maxFiles + ' images at once.';
                input.value = '';
                return;
            }

            for (const file of files) {
                if (file.size > this.maxBytes) {
                    const mb = (file.size / (1024 * 1024)).toFixed(1);
                    this.fileError = file.name + ' is too large. Maximum size is 4 MB (selected ' + mb + ' MB).';
                    input.value = '';
                    this.clearNewPreviews();
                    return;
                }
            }

            this.newPreviews = files.map((file) => ({
                name: file.name,
                url: URL.createObjectURL(file),
            }));
        },
        clearNewPreviews() {
            this.newPreviews.forEach((item) => URL.revokeObjectURL(item.url));
            this.newPreviews = [];
        },
    }"
>
    <label class="jb-label">{{ $label }}</label>
    <p class="mb-3 text-sm text-slate-500">Upload one or more shop logos. New selections show a small preview before you save.</p>

    @if ($existingImages->isNotEmpty())
        <div class="mb-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Current images</p>
            <div class="jb-multi-image-upload-grid">
                @foreach ($existingImages as $image)
                    <label class="jb-multi-image-upload-item cursor-pointer">
                        <img src="{{ $image->imageUrl() }}" alt="Shop logo" class="panel-lightbox-trigger">
                        <span class="jb-multi-image-upload-item__remove">
                            <input type="checkbox" name="{{ $removeField }}[]" value="{{ $image->id }}" class="jb-checkbox-accent">
                            Remove
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    <template x-if="newPreviews.length > 0">
        <div class="mb-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">New upload preview</p>
            <div class="jb-multi-image-upload-grid">
                <template x-for="item in newPreviews" :key="item.url">
                    <div class="jb-multi-image-upload-item jb-multi-image-upload-item--preview">
                        <img :src="item.url" :alt="item.name">
                        <span class="jb-multi-image-upload-item__label" x-text="item.name"></span>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <input
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

    <div x-show="fileError" x-cloak class="jb-file-error-alert mt-2" role="alert" x-text="fileError"></div>

    @error($name)
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
    @error($name.'.*')
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>

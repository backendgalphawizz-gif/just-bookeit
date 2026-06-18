@php
    $name = $name ?? 'image';
    $currentUrl = $currentUrl ?? \App\Models\PlatformSetting::mediaUrl($name);
    $required = $required ?? false;
@endphp
<div
    class="jb-logo-upload sm:col-span-2"
    x-data="{
        current: @js($currentUrl),
        preview: null,
        fileError: null,
        maxBytes: 4 * 1024 * 1024,
        pickFile(event) {
            const input = event.target;
            const file = input.files && input.files[0];
            this.fileError = null;
            if (!file) {
                if (this.preview) {
                    URL.revokeObjectURL(this.preview);
                }
                this.preview = null;
                return;
            }
            if (file.size > this.maxBytes) {
                const mb = (file.size / (1024 * 1024)).toFixed(1);
                this.fileError = 'Image is too large. Maximum size is 4 MB (selected ' + mb + ' MB).';
                input.value = '';
                if (this.preview) {
                    URL.revokeObjectURL(this.preview);
                }
                this.preview = null;
                return;
            }
            if (this.preview) {
                URL.revokeObjectURL(this.preview);
            }
            this.preview = URL.createObjectURL(file);
        },
        displayUrl() {
            return this.preview || this.current;
        },
        hasImage() {
            return !!(this.preview || this.current);
        },
    }"
>
    <label class="jb-label">{{ $label }}@if ($required)<span class="text-rose-600"> *</span>@endif</label>

    <div class="jb-logo-preview" x-show="hasImage()" x-cloak>
        <img :src="displayUrl()" :alt="{{ json_encode($label) }}" class="jb-logo-preview-img panel-lightbox-trigger">
        <p class="text-xs text-slate-500" x-text="preview ? 'New upload preview' : 'Current image'"></p>
    </div>

    <div
        class="jb-logo-preview jb-logo-preview--empty"
        x-show="!hasImage()"
        x-cloak
    >
        <span class="text-sm text-slate-400">No image uploaded yet</span>
    </div>

    <input
        type="file"
        id="{{ $name }}"
        name="{{ $name }}"
        accept="image/png,image/jpeg,image/jpg,image/webp"
        class="jb-input mt-3"
        data-jb-max-mb="4"
        data-jb-file-alpine="1"
        @if ($required) required @endif
        @change="pickFile($event)"
    >
    <p class="mt-1 text-xs text-slate-500">
        PNG, JPG, or WebP · max 4 MB
        @unless ($required || $currentUrl)
            · leave empty to keep current
        @endunless
    </p>
    <div
        x-show="fileError"
        x-cloak
        class="jb-file-error-alert"
        role="alert"
        x-text="fileError"
    ></div>
    @error($name)
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>

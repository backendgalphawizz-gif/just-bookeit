@php
    $name = $name ?? 'profile_image';
    $currentUrl = $currentUrl ?? null;
    $label = $label ?? 'Profile photo';
    $initials = $initials ?? '?';
@endphp

<div style="margin-bottom: 10px;"
    class="jb-profile-photo-panel sm:col-span-2"
    x-data="{
        current: @js($currentUrl),
        preview: null,
        fileError: null,
        _previewUrl: null,
        maxBytes: 4 * 1024 * 1024,
        pickFile(event) {
            const input = event.target;
            const file = input.files && input.files[0];
            this.fileError = null;
            if (!file) {
                if (this._previewUrl) {
                    URL.revokeObjectURL(this._previewUrl);
                    this._previewUrl = null;
                }
                this.preview = null;
                return;
            }
            if (file.size > this.maxBytes) {
                const mb = (file.size / (1024 * 1024)).toFixed(1);
                this.fileError = 'Image is too large. Maximum size is 4 MB (selected ' + mb + ' MB).';
                input.value = '';
                if (this._previewUrl) {
                    URL.revokeObjectURL(this._previewUrl);
                    this._previewUrl = null;
                }
                this.preview = null;
                return;
            }
            if (this._previewUrl) {
                URL.revokeObjectURL(this._previewUrl);
            }
            this._previewUrl = URL.createObjectURL(file);
            this.preview = this._previewUrl;
        },
        displayUrl() {
            return this.preview || this.current;
        },
        hasImage() {
            return !!(this.preview || this.current);
        },
    }"
>
    <template x-if="hasImage()">
        <img :src="displayUrl()" alt="" class="jb-profile-avatar jb-profile-avatar--lg shrink-0 ring-4 ring-white shadow-md panel-lightbox-trigger">
    </template>
    <template x-if="!hasImage()">
        <span class="jb-profile-avatar jb-profile-avatar--lg jb-profile-avatar--initials shrink-0 ring-4 ring-white shadow-md">{{ $initials }}</span>
    </template>
    <div class="jb-profile-photo-meta">
        <p class="jb-profile-section-title">{{ $label }}</p>
        <p class="mt-1 text-sm text-slate-500">PNG, JPG, or WebP · max 4MB · leave empty to keep current</p>
        <label class="jb-profile-file-btn mt-4">
            <span>Choose image</span>
            <input
                type="file"
                name="{{ $name }}"
                accept="image/png,image/jpeg,image/jpg,image/webp"
                class="jb-profile-file-input"
                data-jb-max-mb="4"
                data-jb-file-alpine="1"
                @change="pickFile($event)"
            >
        </label>
        <div
            x-show="fileError"
            x-cloak
            class="jb-file-error-alert"
            role="alert"
            x-text="fileError"
        ></div>
        @error($name)
            <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

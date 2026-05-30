@php
    $name = $name ?? 'profile_image';
    $currentUrl = $currentUrl ?? null;
    $label = $label ?? 'Profile photo';
    $initials = $initials ?? '?';
@endphp

<div
    class="jb-profile-photo-panel sm:col-span-2"
    x-data="{
        current: @js($currentUrl),
        preview: null,
        pickFile(event) {
            const file = event.target.files[0];
            if (!file) {
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
        <img :src="displayUrl()" alt="" class="jb-profile-avatar jb-profile-avatar--lg shrink-0 ring-4 ring-white shadow-md">
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
                @change="pickFile($event)"
            >
        </label>
        @error($name)
            <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

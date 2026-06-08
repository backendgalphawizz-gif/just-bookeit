@php
    $currentUrl = $currentUrl ?? \App\Models\PlatformSetting::mediaUrl($name);
@endphp
<div
    class="jb-logo-upload"
    x-data="{
        current: @js($currentUrl),
        preview: null,
        pickFile(event) {
            const file = event.target.files[0];
            if (!file) {
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
    <label class="jb-label">{{ $label }}</label>

    <div class="jb-logo-preview" x-show="hasImage()" x-cloak>
        <img :src="displayUrl()" :alt="{{ json_encode($label) }}" class="jb-logo-preview-img panel-lightbox-trigger">
        <p class="text-xs text-slate-500" x-text="preview ? 'New upload preview' : 'Current logo'"></p>
    </div>

    <div
        class="jb-logo-preview jb-logo-preview--empty"
        x-show="!hasImage()"
        x-cloak
    >
        <span class="text-sm text-slate-400">No logo uploaded yet</span>
    </div>

    <input
        type="file"
        name="{{ $name }}"
        accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
        class="jb-input mt-3"
        @change="pickFile($event)"
    >
    <p class="mt-1 text-xs text-slate-500">PNG, JPG, or WebP · max 2MB · leave empty to keep current</p>
</div>

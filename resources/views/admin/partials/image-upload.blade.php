@php
    $name = $name ?? 'image';
    $currentUrl = $currentUrl ?? null;
    $required = $required ?? false;
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
    <label class="jb-label">{{ $label }}@if ($required)<span class="text-rose-600"> *</span>@endif</label>

    <div class="jb-logo-preview jb-logo-preview--banner" x-show="hasImage()" x-cloak>
        <img :src="displayUrl()" :alt="{{ json_encode($label) }}" class="jb-logo-preview-img jb-logo-preview-img--banner">
        <p class="text-xs text-slate-500" x-text="preview ? 'New upload preview' : 'Current banner image'"></p>
    </div>

    <div
        class="jb-logo-preview jb-logo-preview--empty jb-logo-preview--banner"
        x-show="!hasImage()"
        x-cloak
    >
        <span class="text-sm text-slate-400">No image uploaded yet</span>
    </div>

    <input
        type="file"
        name="{{ $name }}"
        accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
        class="jb-input mt-3"
        @if ($required) required @endif
        @change="pickFile($event)"
    >
    <p class="mt-1 text-xs text-slate-500">PNG, JPG, or WebP · max 4MB@if (! $required) · leave empty to keep current@endif</p>
</div>

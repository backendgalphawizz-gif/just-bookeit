{{-- Selected-file previews for admin product create/edit media inputs --}}
@php
    $productImageMaxMb = $productImageMaxMb ?? 50;
    $productVideoMaxMb = $productVideoMaxMb ?? 100;
    $isCreate = $isCreate ?? true;
    $galleryImages = $galleryImages ?? collect();
    $galleryVideos = $galleryVideos ?? collect();
@endphp

<div
    class="sm:col-span-2"
    x-data="{
        current: @js($portfolio->displayImageUrl()),
        preview: null,
        fileError: null,
        maxBytes: {{ (int) $productImageMaxMb }} * 1024 * 1024,
        pickFile(event) {
            const input = event.target;
            const file = input.files && input.files[0];
            this.fileError = null;
            if (this.preview) {
                URL.revokeObjectURL(this.preview);
                this.preview = null;
            }
            if (!file) {
                return;
            }
            if (file.size > this.maxBytes) {
                this.fileError = 'Image is too large. Maximum size is {{ $productImageMaxMb }} MB.';
                input.value = '';
                return;
            }
            if (!file.type.startsWith('image/')) {
                this.fileError = 'Please choose an image file.';
                input.value = '';
                return;
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
    <label class="jb-label">Primary image @if($isCreate)<span class="text-rose-600">*</span>@endif</label>
    <p class="mb-3 text-sm text-slate-500">Main cover photo shown in listings.</p>

    <div class="jb-product-media-preview" x-show="hasImage()" x-cloak>
        <img :src="displayUrl()" alt="Primary image preview" class="jb-product-media-preview__img panel-lightbox-trigger">
        <p class="text-xs text-slate-500" x-text="preview ? 'New upload preview' : 'Current primary image'"></p>
    </div>

    <div class="jb-product-media-preview jb-product-media-preview--empty" x-show="!hasImage()" x-cloak>
        <span class="text-sm text-slate-400">No primary image yet</span>
    </div>

    <input
        type="file"
        name="image"
        accept="{{ \App\Support\MediaUploadSupport::acceptAttribute('image') }}"
        class="jb-input mt-3"
        data-jb-max-mb="{{ $productImageMaxMb }}"
        data-jb-file-label="Primary image"
        data-jb-file-alpine="1"
        {{ $isCreate ? 'required' : '' }}
        @change="pickFile($event)"
    >
    <p class="mt-1.5 text-xs text-slate-500">JPEG, PNG or WebP — max {{ $productImageMaxMb }} MB.</p>
    <div x-show="fileError" x-cloak class="jb-file-error-alert mt-2" role="alert" x-text="fileError"></div>
    @error('image')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>

<div
    class="sm:col-span-2"
    x-data="{
        newPreviews: [],
        fileError: null,
        maxBytes: {{ (int) $productImageMaxMb }} * 1024 * 1024,
        maxFiles: 10,
        pickFiles(event) {
            const input = event.target;
            const files = input.files ? Array.from(input.files) : [];
            this.fileError = null;
            this.clearNewPreviews();

            if (files.length > this.maxFiles) {
                this.fileError = 'You can add up to ' + this.maxFiles + ' gallery images at a time.';
                input.value = '';
                return;
            }

            for (const file of files) {
                if (!file.type.startsWith('image/')) {
                    this.fileError = 'Please choose image files only.';
                    input.value = '';
                    return;
                }
                if (file.size > this.maxBytes) {
                    this.fileError = 'One of the images is too large. Maximum size is {{ $productImageMaxMb }} MB each.';
                    input.value = '';
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
            if (!item) return;
            URL.revokeObjectURL(item.url);
            this.newPreviews.splice(index, 1);
            this.syncInputFiles();
            this.fileError = null;
        },
        syncInputFiles() {
            const input = this.$refs.galleryImageInput;
            if (!input) return;
            const transfer = new DataTransfer();
            this.newPreviews.forEach((item) => transfer.items.add(item.file));
            input.files = transfer.files;
        },
        clearNewPreviews() {
            this.newPreviews.forEach((item) => URL.revokeObjectURL(item.url));
            this.newPreviews = [];
        },
    }"
>
    <label class="jb-label">Gallery images</label>
    <p class="mb-3 text-sm text-slate-500">Additional photos customers can browse when booking (up to 10).</p>

    @if ($galleryImages->isNotEmpty())
        <div class="mb-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Current images</p>
            <div class="jb-multi-image-upload-grid">
                @foreach ($galleryImages as $image)
                    <div class="jb-multi-image-upload-item">
                        <div class="jb-multi-image-upload-item__media">
                            @if ($image->imageUrl())
                                <img
                                    src="{{ $image->isImage() ? ($image->thumbUrl() ?: $image->imageUrl()) : $image->imageUrl() }}"
                                    @if ($image->isImage()) data-lightbox-src="{{ $image->imageUrl() }}" @endif
                                    alt=""
                                    class="panel-lightbox-trigger"
                                    loading="lazy"
                                    decoding="async"
                                >
                            @endif
                            @if ($portfolio->exists)
                                <button
                                    type="button"
                                    class="jb-multi-image-upload-item__dismiss"
                                    title="Remove"
                                    onclick="if (confirm('This gallery image will be permanently removed.')) document.getElementById('delete-image-{{ $image->id }}').submit()"
                                >
                                    <svg aria-hidden="true" width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1l6 6M7 1L1 7" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/></svg>
                                </button>
                            @endif
                        </div>
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
        x-ref="galleryImageInput"
        type="file"
        name="gallery_images[]"
        accept="{{ \App\Support\MediaUploadSupport::acceptAttribute('image') }}"
        multiple
        class="jb-input"
        data-jb-max-mb="{{ $productImageMaxMb }}"
        data-jb-file-label="Gallery image"
        data-jb-file-alpine="1"
        @change="pickFiles($event)"
    >
    <p class="mt-1.5 text-xs text-slate-500">Up to 10 images — max {{ $productImageMaxMb }} MB each.</p>
    <div x-show="fileError" x-cloak class="jb-upload-hint-alert mt-2" role="alert" x-text="fileError"></div>
    @error('gallery_images')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    @error('gallery_images.*')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>

<div
    class="sm:col-span-2"
    x-data="{
        newPreviews: [],
        fileError: null,
        maxBytes: {{ (int) $productVideoMaxMb }} * 1024 * 1024,
        maxFiles: 5,
        pickFiles(event) {
            const input = event.target;
            const files = input.files ? Array.from(input.files) : [];
            this.fileError = null;
            this.clearNewPreviews();

            if (files.length > this.maxFiles) {
                this.fileError = 'You can add up to ' + this.maxFiles + ' gallery videos at a time.';
                input.value = '';
                return;
            }

            for (const file of files) {
                if (!file.type.startsWith('video/')) {
                    this.fileError = 'Please choose video files only.';
                    input.value = '';
                    return;
                }
                if (file.size > this.maxBytes) {
                    this.fileError = 'One of the videos is too large. Maximum size is {{ $productVideoMaxMb }} MB each.';
                    input.value = '';
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
            if (!item) return;
            URL.revokeObjectURL(item.url);
            this.newPreviews.splice(index, 1);
            this.syncInputFiles();
            this.fileError = null;
        },
        syncInputFiles() {
            const input = this.$refs.galleryVideoInput;
            if (!input) return;
            const transfer = new DataTransfer();
            this.newPreviews.forEach((item) => transfer.items.add(item.file));
            input.files = transfer.files;
        },
        clearNewPreviews() {
            this.newPreviews.forEach((item) => URL.revokeObjectURL(item.url));
            this.newPreviews = [];
        },
    }"
>
    <label class="jb-label">Gallery videos</label>
    <p class="mb-3 text-sm text-slate-500">Product videos customers can watch (same as vendor app — up to 5).</p>

    @if ($galleryVideos->isNotEmpty())
        <div class="mb-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Current videos</p>
            <div class="jb-product-video-grid">
                @foreach ($galleryVideos as $video)
                    <div class="jb-product-video-item">
                        @if ($video->mediaUrl())
                            <video
                                src="{{ $video->mediaUrl() }}"
                                class="jb-product-video-item__media"
                                controls
                                playsinline
                                preload="metadata"
                            ></video>
                        @endif
                        @if ($portfolio->exists)
                            <button
                                type="button"
                                class="jb-multi-image-upload-item__dismiss"
                                title="Remove"
                                onclick="if (confirm('This gallery video will be permanently removed.')) document.getElementById('delete-image-{{ $video->id }}').submit()"
                            >
                                <svg aria-hidden="true" width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1l6 6M7 1L1 7" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/></svg>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <template x-if="newPreviews.length > 0">
        <div class="mb-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">New upload preview</p>
            <div class="jb-product-video-grid">
                <template x-for="(item, index) in newPreviews" :key="item.id">
                    <div class="jb-product-video-item jb-product-video-item--preview">
                        <video :src="item.url" class="jb-product-video-item__media" controls playsinline preload="metadata"></video>
                        <button
                            type="button"
                            class="jb-multi-image-upload-item__dismiss"
                            title="Remove from upload"
                            @click="removeNewPreview(index)"
                        >
                            <svg aria-hidden="true" width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1l6 6M7 1L1 7" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/></svg>
                        </button>
                        <span class="jb-multi-image-upload-item__label" x-text="item.name"></span>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <input
        x-ref="galleryVideoInput"
        type="file"
        name="gallery_videos[]"
        accept="{{ \App\Support\MediaUploadSupport::acceptAttribute('video') }}"
        multiple
        class="jb-input"
        data-jb-max-mb="{{ $productVideoMaxMb }}"
        data-jb-file-label="Gallery video"
        data-jb-file-alpine="1"
        @change="pickFiles($event)"
    >
    <p class="mt-1.5 text-xs text-slate-500">Up to 5 videos — MP4/MOV/WEBM etc., max {{ $productVideoMaxMb }} MB each.</p>
    <div x-show="fileError" x-cloak class="jb-upload-hint-alert mt-2" role="alert" x-text="fileError"></div>
    @error('gallery_videos')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    @error('gallery_videos.*')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>

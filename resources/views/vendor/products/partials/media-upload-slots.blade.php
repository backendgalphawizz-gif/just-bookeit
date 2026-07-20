@php
    use App\Support\VendorValidationRules;

    $productImageMaxMb = $productImageMaxMb ?? (int) (VendorValidationRules::MAX_IMAGE_KB / 1024);
    $mediaRequired = $mediaRequired ?? false;
    $slotCount = 3;
    $slotMedia = ($existingMedia ?? collect())->values()->take($slotCount);
    $emptyIcon = '<span class="vp-dress-media-empty-icon" aria-hidden="true"><svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 6.75h.008v.008H3.75V6.75z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75h.008v.008h-.008V6.75z"/></svg></span>';
@endphp

<div class="vp-dress-media" data-vp-dress-media>
    <label class="vp-dress-media-slot vp-dress-media-slot--upload">
        <input
            type="file"
            name="media_files[]"
            accept="image/jpeg,image/jpg,image/png,image/webp,image/svg+xml,video/mp4,video/quicktime,video/webm,.mp4,.mov,.webm"
            multiple
            hidden
            data-vp-dress-media-input
            data-vp-max-file-bytes="{{ max(VendorValidationRules::MAX_IMAGE_KB, VendorValidationRules::MAX_VIDEO_KB) * 1024 }}"
            data-vp-file-label="Media file"
            {{ $mediaRequired ? 'required' : '' }}
        >
        <span class="vp-dress-media-upload-badge" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
            </svg>
        </span>
        <span class="vp-dress-media-upload-title">Click to upload or drag and drop</span>
        <span class="vp-dress-media-upload-hint">SVG, PNG, JPG or MP4 (max. {{ $productImageMaxMb }}MB)</span>
    </label>

    @for ($i = 0; $i < $slotCount; $i++)
        @php $media = $slotMedia->get($i); @endphp
        @if ($media)
            <div class="vp-dress-media-slot has-file" data-vp-dress-media-preview data-existing="1">
                <div data-vp-existing-media>
                    @if ($media->isVideo())
                        <span class="vp-dress-media-video-badge">Video</span>
                    @else
                        <img src="{{ $media->imageUrl() }}" alt="" class="panel-lightbox-trigger">
                    @endif
                    @if ($item->exists)
                        <button type="submit" form="vendor-delete-gallery-{{ $media->id }}" class="vp-dress-media-remove" title="Remove">×</button>
                    @endif
                </div>
                {!! $emptyIcon !!}
            </div>
        @else
            <div class="vp-dress-media-slot vp-dress-media-slot--empty" data-vp-dress-media-preview>
                {!! $emptyIcon !!}
            </div>
        @endif
    @endfor
</div>

@if (($existingMedia ?? collect())->count() > $slotCount)
    <div class="vp-dress-media-existing vp-dress-media-existing--overflow">
        @foreach (($existingMedia ?? collect())->slice($slotCount) as $media)
            <div class="vp-dress-media-existing-item">
                @if ($media->isVideo())
                    <span class="vp-dress-media-video-badge">Video</span>
                @elseif ($media->imageUrl())
                    <img src="{{ $media->imageUrl() }}" alt="" class="panel-lightbox-trigger">
                @endif
                @if ($item->exists)
                    <button type="submit" form="vendor-delete-gallery-{{ $media->id }}" class="vp-dress-media-remove" title="Remove">×</button>
                @endif
            </div>
        @endforeach
    </div>
@endif

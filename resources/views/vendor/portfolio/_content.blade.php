@php
    $activeAudience = request('audience', 'women');
    if (! array_key_exists($activeAudience, $portfolioByAudience)) {
        $activeAudience = 'women';
    }
    $activeGroup = $portfolioByAudience[$activeAudience];
@endphp

<div class="vp-portfolio">
    <nav class="vp-portfolio-tabs" aria-label="Portfolio audience">
        @foreach ($portfolioByAudience as $audienceKey => $group)
            <a
                href="{{ route('vendor.settings.index', ['tab' => 'portfolio', 'audience' => $audienceKey]) }}"
                @class(['vp-portfolio-tab', 'vp-portfolio-tab--active' => $audienceKey === $activeAudience])
            >{{ $group['label'] }}</a>
        @endforeach
    </nav>

    <div class="vp-portfolio-panel">
        @if ($activeGroup['images']->isNotEmpty())
            <div class="vp-portfolio-grid">
                @foreach ($activeGroup['images'] as $image)
                    <div class="vp-portfolio-item">
                        @if ($image->imageUrl())
                            <img src="{{ $image->imageUrl() }}" alt="Portfolio — {{ $activeGroup['label'] }}" class="panel-lightbox-trigger">
                        @endif
                        <form
                            method="POST"
                            action="{{ route('vendor.portfolio.destroy', $image) }}"
                            class="vp-portfolio-delete-form"
                            data-vp-confirm="This portfolio image will be permanently removed."
                            data-vp-confirm-title="Remove portfolio image?"
                            data-vp-confirm-label="Remove"
                            data-vp-confirm-variant="error"
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="vp-portfolio-edit" title="Remove image" aria-label="Remove image">
                                @include('vendor.partials.nav-icon', ['icon' => 'delete'])
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <p class="vp-portfolio-empty">No photos for {{ $activeGroup['label'] }} yet. Add your first image below.</p>
        @endif

        <form
            method="POST"
            action="{{ route('vendor.portfolio.store') }}"
            enctype="multipart/form-data"
            class="vp-portfolio-upload"
        >
            @csrf
            <input type="hidden" name="audience" value="{{ $activeAudience }}">
            <label class="vp-portfolio-add">
                <span class="vp-portfolio-add-icon" aria-hidden="true">
                    @include('vendor.partials.nav-icon', ['icon' => 'upload'])
                </span>
                <span class="vp-portfolio-add-title">Add More</span>
                <input
                    type="file"
                    name="portfolio_image"
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                    required
                    hidden
                    data-vp-file-label="Portfolio image"
                    data-vp-max-file-bytes="{{ \App\Support\VendorValidationRules::MAX_IMAGE_KB * 1024 }}"
                    data-vp-auto-submit
                >
            </label>
            @error('portfolio_image')
                <p class="vp-field-error vp-portfolio-upload-error">{{ $message }}</p>
            @enderror
        </form>
    </div>
</div>

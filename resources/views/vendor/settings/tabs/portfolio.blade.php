<h2 class="vp-settings-panel-title">Portfolio</h2>
<p style="margin:-.75rem 0 1.25rem;color:var(--vp-muted);font-size:.88rem;">Showcase your previous work. This is separate from products listed for booking.</p>

<div class="vp-portfolio-tabs">
    @foreach (['women' => 'Women', 'men' => 'Men', 'kids' => 'Kids'] as $key => $label)
        <a href="{{ route('vendor.settings.index', ['tab' => 'portfolio', 'audience' => $key]) }}"
           class="vp-portfolio-tab {{ $portfolioAudience === $key ? 'vp-portfolio-tab--active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

@if ($portfolioImages->isNotEmpty())
    <div class="vp-portfolio-grid">
        @foreach ($portfolioImages as $image)
            <div class="vp-portfolio-item">
                @if ($image->imageUrl())
                    <img src="{{ $image->imageUrl() }}" alt="Portfolio work" class="panel-lightbox-trigger">
                @endif
                <form method="POST" action="{{ route('vendor.settings.portfolio.destroy', $image) }}" class="vp-portfolio-delete-form"
                      data-vp-confirm="This portfolio image will be permanently removed."
                      data-vp-confirm-title="Remove portfolio image?"
                      data-vp-confirm-label="Remove"
                      data-vp-confirm-variant="error">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="vp-portfolio-edit" title="Remove">
                        @include('vendor.partials.nav-icon', ['icon' => 'edit'])
                    </button>
                </form>
            </div>
        @endforeach
    </div>
@else
    <p class="vp-empty" style="padding:1rem 0 .25rem;font-size:.88rem;">No portfolio images for {{ ucfirst($portfolioAudience) }} yet.</p>
@endif

<form method="POST" action="{{ route('vendor.settings.update') }}" enctype="multipart/form-data" class="vp-portfolio-upload">
    @csrf
    <input type="hidden" name="tab" value="portfolio">
    <input type="hidden" name="audience" value="{{ $portfolioAudience }}">
    <label class="vp-portfolio-add">
        <span class="vp-portfolio-add-icon" aria-hidden="true">
            @include('vendor.partials.nav-icon', ['icon' => 'upload'])
        </span>
        <span class="vp-portfolio-add-title">Add More</span>
        <span class="vp-portfolio-add-sub">Upload portfolio image (max 20 MB)</span>
        <input type="file" name="portfolio_image" accept="image/jpeg,image/jpg,image/png,image/webp" required hidden data-vp-file-label="Portfolio image" data-vp-max-file-bytes="{{ \App\Support\VendorValidationRules::MAX_IMAGE_KB * 1024 }}" data-vp-auto-submit>
    </label>
    @error('portfolio_image')<p class="vp-field-error" style="margin-top:.65rem;text-align:center;">{{ $message }}</p>@enderror
</form>

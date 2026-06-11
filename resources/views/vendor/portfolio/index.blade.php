@extends('vendor.layouts.app')

@section('title', 'Portfolio')

@section('content')
<div class="vp-page-head">
    <div>
        <h1 class="vp-page-title">Portfolio</h1>
        <p class="vp-page-sub">Your previous work, grouped by audience — not items you sell (use Products for those).</p>
    </div>
</div>

<div class="vp-card vp-card-pad" style="margin-bottom:1rem;border-color:#fde68a;background:#fffbeb;">
    <p style="margin:0;font-size:.88rem;color:#92400e;">
        <strong>Portfolio</strong> = photos of work you have already done.
        <strong>Products</strong> = dresses, jewellery, or services customers can book.
    </p>
</div>

<div class="vp-card">
    <div class="vp-card-count">
        {{ $photoCount }} {{ Str::plural('photo', $photoCount) }} across Women, Men, and Kids
    </div>
    <div class="vp-card-pad" style="display:flex;flex-direction:column;gap:2rem;">
        @foreach ($portfolioByAudience as $audienceKey => $group)
            <section id="audience-{{ $audienceKey }}" style="padding-bottom:1.5rem;border-bottom:1px solid var(--vp-border);">
                <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:1rem;">
                    <div>
                        <h2 style="margin:0;font-size:1rem;font-weight:700;">{{ $group['label'] }}</h2>
                        <p style="margin:.25rem 0 0;font-size:.85rem;color:var(--vp-muted);">{{ $group['images']->count() }} {{ Str::plural('photo', $group['images']->count()) }}</p>
                    </div>
                </div>

                @if ($group['images']->isNotEmpty())
                    <div class="vp-portfolio-grid">
                        @foreach ($group['images'] as $image)
                            <div class="vp-portfolio-item">
                                @if ($image->imageUrl())
                                    <img src="{{ $image->imageUrl() }}" alt="Portfolio work — {{ $group['label'] }}" class="panel-lightbox-trigger">
                                @endif
                                <form method="POST" action="{{ route('vendor.portfolio.destroy', $image) }}" class="vp-portfolio-delete-form"
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
                    <p class="vp-empty" style="padding:.5rem 0;font-size:.88rem;">No portfolio images for {{ $group['label'] }} yet.</p>
                @endif

                <form method="POST" action="{{ route('vendor.portfolio.store') }}" enctype="multipart/form-data" class="vp-portfolio-upload" style="margin-top:1rem;">
                    @csrf
                    <input type="hidden" name="audience" value="{{ $audienceKey }}">
                    <label class="vp-portfolio-add">
                        <span class="vp-portfolio-add-icon" aria-hidden="true">
                            @include('vendor.partials.nav-icon', ['icon' => 'upload'])
                        </span>
                        <span class="vp-portfolio-add-title">Add {{ $group['label'] }} photo</span>
                        <span class="vp-portfolio-add-sub">Upload portfolio image (max 20 MB)</span>
                        <input type="file" name="portfolio_image" accept="image/jpeg,image/jpg,image/png,image/webp" required hidden data-vp-file-label="Portfolio image" data-vp-max-file-bytes="{{ \App\Support\VendorValidationRules::MAX_IMAGE_KB * 1024 }}" data-vp-auto-submit>
                    </label>
                    @error('portfolio_image')
                        @if (old('audience') === $audienceKey)
                            <p class="vp-field-error" style="margin-top:.65rem;text-align:center;">{{ $message }}</p>
                        @endif
                    @enderror
                </form>
            </section>
        @endforeach
    </div>
</div>

@if (request('audience'))
    <script>
        document.getElementById('audience-{{ request('audience') }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    </script>
@endif
@endsection

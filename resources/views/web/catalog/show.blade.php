@extends('web.layouts.app')

@section('title', $item->title)

@section('content')
@php
    $fashionFallbacks = [
        'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=900&q=85',
        'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=900&q=85',
        'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=900&q=85',
        'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=900&q=85',
        'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=900&q=85',
        'https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=900&q=85',
    ];
    $fallbackImg = $fashionFallbacks[$item->id % count($fashionFallbacks)];
@endphp

<div class="jbw-container jbw-page-shell">
    <nav class="jbw-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('web.catalog.index') }}" class="jbw-breadcrumb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            Back
        </a>
    </nav>

    @php
        $galleryMedia = $item->galleryMediaItems();
        if ($galleryMedia === []) {
            $galleryMedia = [['type' => 'image', 'url' => $fallbackImg, 'poster' => null]];
        }
        $galleryUrls = array_values(array_map(fn ($m) => $m['url'], array_filter($galleryMedia, fn ($m) => $m['type'] === 'image')));
        if ($galleryUrls === []) {
            $galleryUrls = [$fallbackImg];
        }
        $initialMedia = $galleryMedia[0];
    @endphp

    <div class="jbw-product-detail">
        {{-- Gallery (images + videos) --}}
        <div class="jbw-gallery-wrap" id="jbw-product-gallery" data-product-title="{{ $item->title }}">
            @if (count($galleryMedia) > 1)
                <div class="jbw-gallery-thumbs" role="list">
                    @foreach ($galleryMedia as $index => $media)
                        <button
                            type="button"
                            class="jbw-gallery-thumb {{ $index === 0 ? 'is-active' : '' }} {{ $media['type'] === 'video' ? 'jbw-gallery-thumb--video' : '' }}"
                            data-gallery-type="{{ $media['type'] }}"
                            data-gallery-url="{{ $media['url'] }}"
                            data-gallery-poster="{{ $media['poster'] ?? ($galleryUrls[0] ?? '') }}"
                            aria-label="{{ $media['type'] === 'video' ? 'Play video' : 'View image' }} {{ $index + 1 }}"
                        >
                            @if ($media['type'] === 'video')
                                <span class="jbw-gallery-thumb-media">
                                    @if (! empty($media['poster']) || ! empty($galleryUrls[0]))
                                        <img src="{{ $media['poster'] ?: $galleryUrls[0] }}" alt="">
                                    @else
                                        <span class="jbw-gallery-thumb-fallback" aria-hidden="true"></span>
                                    @endif
                                    <span class="jbw-gallery-play" aria-hidden="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                    </span>
                                </span>
                            @else
                                <img src="{{ $media['url'] }}" alt="">
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="jbw-gallery-main">
                <img
                    id="jbw-gallery-main-image"
                    class="jbw-gallery-stage {{ $initialMedia['type'] === 'image' ? '' : 'is-hidden' }}"
                    src="{{ $initialMedia['type'] === 'image' ? $initialMedia['url'] : ($galleryUrls[0] ?? $fallbackImg) }}"
                    alt="{{ $item->title }}"
                >
                <video
                    id="jbw-gallery-main-video"
                    class="jbw-gallery-stage {{ $initialMedia['type'] === 'video' ? '' : 'is-hidden' }}"
                    controls
                    playsinline
                    preload="metadata"
                    @if ($initialMedia['type'] === 'video')
                        src="{{ $initialMedia['url'] }}"
                        @if (! empty($initialMedia['poster'])) poster="{{ $initialMedia['poster'] }}" @endif
                    @endif
                ></video>
            </div>
        </div>

        {{-- Info --}}
        <div class="jbw-detail-info">
            <p class="jbw-product-brand">{{ $item->vendor?->brand_name ?? 'Designer' }}</p>
            <h1 class="jbw-product-detail-title ">{{ $item->title }}</h1>
            <p class="jbw-detail-price" id="jbw-detail-price">{{ $item->rentalPriceLabel() }}</p>

            @include('web.catalog.partials.variant-picker', ['item' => $item, 'baseImageUrl' => $galleryUrls[0] ?? null])

            @if($item->description)
                <p class="jbw-detail-desc">{{ $item->description }}</p>
            @else
                <p class="jbw-detail-desc">Premium designer outfit available for rent. Perfect for special occasions, weddings, and events.</p>
            @endif

            @if ($item->vendor)
                <a href="{{ route('web.vendors.show', $item->vendor) }}" class="jbw-vendor-chip">
                    @if ($item->vendor->profileImageUrl() || $item->vendor->shopLogoUrl())
                        <img src="{{ $item->vendor->profileImageUrl() ?: $item->vendor->shopLogoUrl() }}" alt="{{ $item->vendor->brand_name }}" class="jbw-vendor-chip-avatar">
                    @else
                        <span class="jbw-vendor-chip-avatar jbw-designer-fallback">{{ strtoupper(substr($item->vendor->brand_name, 0, 1)) }}</span>
                    @endif
                    <div>
                        <strong style="font-size:0.9375rem">{{ $item->vendor->brand_name }} <font class="starcolor">★</font> {{ number_format($item->vendor->rating ?? 4.5, 1) }}</strong>
                        <p style="margin:0.125rem 0 0;font-size:0.8125rem;color:var(--c-muted)">
                            <!-- <font class="starcolor">★</font> {{ number_format($item->vendor->rating ?? 4.5, 1) }} -->
                            @if($item->vendor->city) <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>
</svg> {{ $item->vendor->city }} @endif
                        </p>
                    </div>
                    <svg style="margin-left:auto;color:var(--c-muted)" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            @endif

            <div class="jbw-detail-actions" id="jbw-detail-actions">

                @auth('customer')
                    @if ($webCustomer->is_guest)
                        <a href="{{ route('web.register', ['redirect' => route('web.bookings.overview', $item)]) }}" class="buttonheightjbw-btn jbw-btn--primary jbw-btn--lg" id="jbw-book-now-link">Create account to book</a>
                    @else
                        <form method="POST" action="{{ route('web.cart.store') }}" id="jbw-add-to-cart-form" style="display:inline">
                            @csrf
                            <input type="hidden" name="portfolio_item_id" value="{{ $item->id }}">
                            <input type="hidden" name="redirect" value="{{ route('web.catalog.show', $item) }}">
                            <button type="submit" class="buttonheight jbw-btn jbw-btn--outline jbw-btn--lg" id="jbw-add-to-cart-btn">Add to cart</button>
                        </form>
                        <a href="{{ route('web.bookings.overview', $item) }}" class="buttonheight jbw-btn jbw-btn--primary jbw-btn--lg" id="jbw-book-now-link">Book now</a>
                    @endif
                @else
                    <a href="{{ route('web.login', ['redirect' => route('web.bookings.overview', $item)]) }}" class="buttonheight jbw-btn jbw-btn--primary jbw-btn--lg">Sign in to book</a>
                @endauth
            </div>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="jbw-section jbw-section--tight">
            <h2 class="jbw-section-title" style="font-size:1.375rem;margin-bottom:1.25rem">More from {{ $item->vendor?->brand_name ?? 'this designer' }}</h2>
            <div class="jbw-product-grid">
                @foreach ($related as $rel)
                    @php $rf = $fashionFallbacks[$rel->id % count($fashionFallbacks)]; @endphp
                    <a href="{{ route('web.catalog.show', $rel) }}" class="jbw-product-card">
                        <div class="jbw-product-card-img"><img src="{{ $rel->displayImageUrl() ?: $rf }}" alt="{{ $rel->title }}" loading="lazy"></div>
                        <div class="jbw-product-card-body">
                            <p class="jbw-product-title">{{ $rel->title }}</p>
                            <p class="jbw-product-price">{{ $rel->rentalPriceLabel() }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
    <section class="jbw-section" style="padding-top: 0rem;">
        <div>
            <h2 class="jbw-product-detail-title">Customer Reviews</h2>
        </div>

        @if ($reviewCount > 0)
            @php
                $filledStars = (int) round(min(5, max(0, $averageRating)));
                $summaryStars = str_repeat('★', $filledStars).str_repeat('☆', 5 - $filledStars);
            @endphp
            <div class="reviews-header">
                <div class="rating-summary">
                    <span class="stars" aria-hidden="true">{{ $summaryStars }}</span>
                    <span>{{ number_format($averageRating, 1) }} ({{ $reviewCount }} {{ $reviewCount === 1 ? 'review' : 'reviews' }})</span>
                </div>
                @if ($item->vendor)
                    <a href="{{ route('web.vendors.show', $item->vendor) }}" class="view-all">VIEW ALL</a>
                @endif
            </div>

            <div class="reviews-grid">
                @foreach ($reviews as $review)
                    @php
                        $rating = (int) round(min(5, max(0, (float) $review->rating)));
                        $stars = str_repeat('★', $rating).str_repeat('☆', 5 - $rating);
                        $name = $review->customer?->name ?: 'Customer';
                        $avatar = $review->customer?->profileImageUrl();
                        $initial = strtoupper(substr($name, 0, 1));
                        $comment = trim((string) ($review->comment ?? ''));
                    @endphp
                    <div class="review-card">
                        <div class="review-top">
                            <div class="review-user">
                                @if ($avatar)
                                    <img src="{{ $avatar }}" alt="{{ $name }}">
                                @else
                                    <span class="review-avatar-fallback" aria-hidden="true">{{ $initial }}</span>
                                @endif
                                <div>
                                    <h4>{{ $name }}</h4>
                                    <div class="stars" aria-label="{{ $rating }} out of 5 stars">{{ $stars }}</div>
                                </div>
                            </div>
                            <span class="review-time">{{ $review->created_at?->diffForHumans() }}</span>
                        </div>
                        @if ($comment !== '')
                            <p>“{{ $comment }}”</p>
                        @else
                            <p class="review-card-muted">No written comment.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="reviews-empty">
                <p>No reviews yet for this designer.</p>
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const gallery = document.getElementById('jbw-product-gallery');
    const mainImage = document.getElementById('jbw-gallery-main-image');
    const mainVideo = document.getElementById('jbw-gallery-main-video');

    const showGalleryMedia = (type, url, poster) => {
        if (!mainImage || !mainVideo || !url) return;

        if (type === 'video') {
            mainImage.classList.add('is-hidden');
            mainVideo.classList.remove('is-hidden');
            if (poster) mainVideo.poster = poster;
            if (mainVideo.getAttribute('src') !== url) {
                mainVideo.src = url;
            }
            mainVideo.load();
            return;
        }

        mainVideo.pause();
        mainVideo.removeAttribute('src');
        mainVideo.load();
        mainVideo.classList.add('is-hidden');
        mainImage.classList.remove('is-hidden');
        mainImage.src = url;
    };

    window.jbwShowProductGalleryImage = (url) => showGalleryMedia('image', url, null);

    if (gallery) {
        gallery.querySelectorAll('[data-gallery-url]').forEach((btn) => {
            btn.addEventListener('click', () => {
                gallery.querySelectorAll('.jbw-gallery-thumb').forEach((thumb) => thumb.classList.remove('is-active'));
                btn.classList.add('is-active');
                showGalleryMedia(
                    btn.dataset.galleryType || 'image',
                    btn.dataset.galleryUrl,
                    btn.dataset.galleryPoster || null
                );
            });
        });
    }

    const picker = document.getElementById('jbw-variant-picker');
    if (!picker) return;

    const priceEl = document.getElementById('jbw-detail-price');
    const cartForm = document.getElementById('jbw-add-to-cart-form');
    const bookLink = document.getElementById('jbw-book-now-link');
    const bookBase = @json(route('web.bookings.overview', $item));
    const inputs = picker.querySelectorAll('.jbw-variant-input');

    const applyVariant = (input, { syncGallery = true } = {}) => {
        if (!input) return;

        picker.querySelectorAll('.jbw-variant-chip').forEach((chip) => chip.classList.remove('is-selected'));
        input.closest('.jbw-variant-chip')?.classList.add('is-selected');
        input.checked = true;

        if (priceEl && input.dataset.label) {
            priceEl.textContent = input.dataset.label;
        }

        // Only swap the main image when the chosen variant has its own photo.
        // Gallery browsing must not change / clear the selected variant.
        if (syncGallery && input.dataset.image) {
            window.jbwShowProductGalleryImage(input.dataset.image);
            gallery?.querySelectorAll('.jbw-gallery-thumb').forEach((thumb) => {
                thumb.classList.toggle(
                    'is-active',
                    thumb.dataset.galleryType === 'image' && thumb.dataset.galleryUrl === input.dataset.image
                );
            });
        }

        if (cartForm) {
            const hidden = cartForm.querySelector('input[name="portfolio_item_variant_id"]');
            if (input.value) {
                let field = hidden;
                if (!field) {
                    field = document.createElement('input');
                    field.type = 'hidden';
                    field.name = 'portfolio_item_variant_id';
                    cartForm.appendChild(field);
                }
                field.value = input.value;
            } else if (hidden) {
                hidden.remove();
            }
        }

        if (bookLink) {
            bookLink.href = input.value
                ? bookBase + '?variant=' + encodeURIComponent(input.value)
                : bookBase;
        }
    };

    inputs.forEach((input) => {
        input.addEventListener('change', () => applyVariant(input, { syncGallery: true }));
    });

    const checked = picker.querySelector('.jbw-variant-input:checked');
    if (checked) applyVariant(checked, { syncGallery: !!checked.dataset.image });
})();
</script>
@endpush

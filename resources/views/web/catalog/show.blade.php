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
    $pageHeading = match ($item->serviceCategorySlug()) {
        'rented-dress' => 'Dress Detail',
        'rented-jewellery' => 'Jewellery Detail',
        'fashion-designer' => 'Designer Detail',
        default => 'Product Detail',
    };
    $vendorRating = round((float) ($item->vendor?->rating ?? 0), 1);
    $description = trim((string) ($item->description ?: 'Premium designer outfit available for rent. Perfect for special occasions, weddings, and events.'));
    $vendorLocation = collect([
        $item->vendor?->address ? \Illuminate\Support\Str::before($item->vendor->address, ',') : null,
        $item->vendor?->city,
    ])->filter()->unique()->implode(', ');
@endphp

<div class="jbw-container jbw-page-shell jbw-product-detail-page">
    <div class="jbw-catalog-page-head jbw-detail-page-head">
        <a href="{{ route('web.catalog.index') }}" class="jbw-catalog-back" aria-label="Back to catalog">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 class="jbw-catalog-page-title">{{ $pageHeading }}</h1>
    </div>

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
            <div class="jbw-detail-title-row">
                <h2 class="jbw-product-detail-title">{{ $item->title }}</h2>
                @if ($vendorRating > 0)
                    <span class="jbw-detail-rating-pill" aria-label="Rating {{ number_format($vendorRating, 1) }}">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        {{ number_format($vendorRating, 1) }}
                    </span>
                @endif
            </div>

            <p class="jbw-detail-price" id="jbw-detail-price">{{ $item->rentalPriceLabel() }}</p>

            @include('web.catalog.partials.variant-picker', ['item' => $item, 'baseImageUrl' => $galleryUrls[0] ?? null])

            <div class="jbw-detail-desc-wrap" data-desc-wrap>
                <p class="jbw-detail-desc" data-desc-text>{{ $description }}</p>
                <button type="button" class="jbw-read-more" data-read-more hidden>
                    <span data-read-more-label>Read More</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>

            @if ($item->vendor)
                <a href="{{ route('web.vendors.show', $item->vendor) }}" class="jbw-vendor-chip">
                    @if ($item->vendor->profileImageUrl() || $item->vendor->shopLogoUrl())
                        <img src="{{ $item->vendor->profileImageUrl() ?: $item->vendor->shopLogoUrl() }}" alt="{{ $item->vendor->brand_name }}" class="jbw-vendor-chip-avatar">
                    @else
                        <span class="jbw-vendor-chip-avatar jbw-designer-fallback">{{ strtoupper(substr($item->vendor->brand_name, 0, 1)) }}</span>
                    @endif
                    <div class="jbw-vendor-chip-body">
                        <strong>
                            {{ $item->vendor->brand_name }}
                            @if ($vendorRating > 0)
                                <span class="jbw-vendor-chip-rating"><span class="starcolor">★</span> {{ number_format($vendorRating, 1) }}</span>
                            @endif
                        </strong>
                        @if ($vendorLocation !== '')
                            <p class="jbw-vendor-chip-location">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>
                                </svg>
                                {{ $vendorLocation }}
                            </p>
                        @endif
                    </div>
                    <svg class="jbw-vendor-chip-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            @endif

            <div class="jbw-detail-actions" id="jbw-detail-actions">
                @auth('customer')
                    @if ($webCustomer->is_guest)
                        @if ($item->vendor)
                            <a href="{{ route('web.register', ['redirect' => route('web.chat.start', $item->vendor)]) }}" class="jbw-detail-action-btn jbw-detail-action-btn--outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                Chat
                            </a>
                        @endif
                        <a href="{{ route('web.register', ['redirect' => route('web.catalog.show', $item)]) }}" class="jbw-detail-action-btn jbw-detail-action-btn--outline">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            Add to Cart
                        </a>
                        <a href="{{ route('web.register', ['redirect' => route('web.bookings.overview', $item)]) }}" class="jbw-detail-action-btn jbw-detail-action-btn--primary" id="jbw-book-now-link">BOOK NOW</a>
                    @else
                        @if ($item->vendor)
                            <a href="{{ route('web.chat.start', $item->vendor) }}" class="jbw-detail-action-btn jbw-detail-action-btn--outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                Chat
                            </a>
                        @endif
                        <form method="POST" action="{{ route('web.cart.store') }}" id="jbw-add-to-cart-form" class="jbw-detail-action-form">
                            @csrf
                            <input type="hidden" name="portfolio_item_id" value="{{ $item->id }}">
                            <input type="hidden" name="redirect" value="{{ route('web.catalog.show', $item) }}">
                            <button type="submit" class="jbw-detail-action-btn jbw-detail-action-btn--outline" id="jbw-add-to-cart-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                Add to Cart
                            </button>
                        </form>
                        <a href="{{ route('web.bookings.overview', $item) }}" class="jbw-detail-action-btn jbw-detail-action-btn--primary" id="jbw-book-now-link">BOOK NOW</a>
                    @endif
                @else
                    @if ($item->vendor)
                        <a href="{{ route('web.login', ['redirect' => route('web.chat.start', $item->vendor)]) }}" class="jbw-detail-action-btn jbw-detail-action-btn--outline">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            Chat
                        </a>
                    @endif
                    <a href="{{ route('web.login', ['redirect' => route('web.catalog.show', $item)]) }}" class="jbw-detail-action-btn jbw-detail-action-btn--outline">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Add to Cart
                    </a>
                    <a href="{{ route('web.login', ['redirect' => route('web.bookings.overview', $item)]) }}" class="jbw-detail-action-btn jbw-detail-action-btn--primary" id="jbw-book-now-link">BOOK NOW</a>
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
                    <a href="{{ route('web.catalog.show', $rel) }}" @class(['jbw-product-card', 'is-rental' => $rel->requiresRentalPeriod()])>
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

    <section class="jbw-section jbw-reviews-block" style="padding-top: 0;">
        <h2 class="jbw-product-detail-title">Customer Reviews</h2>

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
        const thumbsStrip = gallery.querySelector('.jbw-gallery-thumbs');

        const activateThumb = (btn) => {
            gallery.querySelectorAll('.jbw-gallery-thumb').forEach((thumb) => thumb.classList.remove('is-active'));
            btn.classList.add('is-active');
            showGalleryMedia(
                btn.dataset.galleryType || 'image',
                btn.dataset.galleryUrl,
                btn.dataset.galleryPoster || null
            );
            if (typeof btn.scrollIntoView === 'function') {
                btn.scrollIntoView({
                    behavior: 'smooth',
                    inline: 'center',
                    block: 'nearest',
                });
            }
        };

        gallery.querySelectorAll('[data-gallery-url]').forEach((btn) => {
            btn.addEventListener('click', () => activateThumb(btn));
        });

        const initialActive = gallery.querySelector('.jbw-gallery-thumb.is-active');
        if (initialActive && thumbsStrip) {
            requestAnimationFrame(() => {
                initialActive.scrollIntoView({ inline: 'center', block: 'nearest' });
            });
        }
    }

    // Read more
    const descWrap = document.querySelector('[data-desc-wrap]');
    if (descWrap) {
        const descText = descWrap.querySelector('[data-desc-text]');
        const readBtn = descWrap.querySelector('[data-read-more]');
        const readLabel = descWrap.querySelector('[data-read-more-label]');
        const full = (descText?.textContent || '').trim();
        const limit = 160;

        if (descText && readBtn && full.length > limit) {
            let expanded = false;
            const collapsed = full.slice(0, limit).replace(/\s+\S*$/, '') + '…';
            descText.textContent = collapsed;
            readBtn.hidden = false;

            readBtn.addEventListener('click', () => {
                expanded = !expanded;
                descText.textContent = expanded ? full : collapsed;
                if (readLabel) readLabel.textContent = expanded ? 'Read Less' : 'Read More';
                readBtn.classList.toggle('is-expanded', expanded);
            });
        }
    }

    const picker = document.getElementById('jbw-variant-picker');
    const priceEl = document.getElementById('jbw-detail-price');
    const cartForm = document.getElementById('jbw-add-to-cart-form');
    const bookLink = document.getElementById('jbw-book-now-link');
    const bookBase = @json(route('web.bookings.overview', $item));

    if (picker) {
        picker.addEventListener('jbw:variant-changed', (event) => {
            const { id, label, image, syncGallery } = event.detail || {};

            if (priceEl && label) {
                priceEl.textContent = label;
            }

            if (syncGallery && image && typeof window.jbwShowProductGalleryImage === 'function') {
                window.jbwShowProductGalleryImage(image);
                gallery?.querySelectorAll('.jbw-gallery-thumb').forEach((thumb) => {
                    const isMatch = thumb.dataset.galleryType === 'image' && thumb.dataset.galleryUrl === image;
                    thumb.classList.toggle('is-active', isMatch);
                });
            }

            if (cartForm) {
                const hidden = cartForm.querySelector('input[name="portfolio_item_variant_id"]');
                if (id) {
                    let field = hidden;
                    if (!field) {
                        field = document.createElement('input');
                        field.type = 'hidden';
                        field.name = 'portfolio_item_variant_id';
                        cartForm.appendChild(field);
                    }
                    field.value = String(id);
                } else if (hidden) {
                    hidden.remove();
                }
            }

            if (bookLink) {
                bookLink.href = id
                    ? bookBase + '?variant=' + encodeURIComponent(id)
                    : bookBase;
            }
        });
    }
})();
</script>
@endpush

@extends('web.layouts.app')

@section('title', $vendor->brand_name.' · Designer Profile')

@section('content')
@php
    $coverFallback = 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1400&q=85&fit=crop';
    $serviceFallbacks = [
        'https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=900&q=85&fit=crop',
        'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=900&q=85&fit=crop',
        'https://images.unsplash.com/photo-1617032210317-3b0855f047a4?w=900&q=85&fit=crop',
    ];
    $coverUrl = $vendor->coverImageUrl() ?: $coverFallback;
    $avatarUrl = $vendor->profileImageUrl() ?: $vendor->shopLogoUrl();
    $displayRating = $averageRating > 0 ? $averageRating : round((float) ($vendor->rating ?? 0), 1);
    $bio = trim((string) ($vendor->bio ?: ''));
    if ($bio === '') {
        $bio = $vendor->brand_name.' creates distinctive looks for weddings, celebrations, and everyday elegance — crafted with care and attention to detail.';
    }
    $email = $vendor->business_email ?: $vendor->email;
    $phoneRaw = preg_replace('/\D+/', '', (string) ($vendor->business_mobile ?: $vendor->mobile));
    $maskedPhone = null;
    if ($phoneRaw !== '') {
        $last = substr($phoneRaw, -3);
        $maskedPhone = '+91 *******'.$last;
    }
    $locationLine = collect([
        $vendor->address,
        $vendor->city,
    ])->filter()->unique()->implode(', ');
    if ($locationLine === '' && $vendor->city) {
        $locationLine = $vendor->city;
    }
@endphp

<div class="jbw-container jbw-page-shell jbw-designer-profile">
    <div class="jbw-catalog-page-head jbw-detail-page-head">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('web.home') }}" class="jbw-catalog-back" aria-label="Go back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 class="jbw-catalog-page-title">Designer Profile</h1>
    </div>

    <section class="jbw-dp-hero">
        <div class="jbw-dp-banner">
            <img src="{{ $coverUrl }}" alt="{{ $vendor->brand_name }} cover">
        </div>
        <div class="jbw-dp-avatar-wrap">
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $vendor->brand_name }}" class="jbw-dp-avatar">
            @else
                <span class="jbw-dp-avatar jbw-dp-avatar--fallback">{{ strtoupper(substr($vendor->brand_name, 0, 1)) }}</span>
            @endif
        </div>
    </section>

    <section class="jbw-dp-identity">
        <h2 class="jbw-dp-name">{{ $vendor->brand_name }}</h2>

        @if ($locationLine !== '')
            <p class="jbw-dp-meta-line">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>
                </svg>
                <span>{{ $locationLine }}</span>
            </p>
        @endif

        @if ($email || $maskedPhone)
            <p class="jbw-dp-contact">
                @if ($email)<span>{{ $email }}</span>@endif
                @if ($email && $maskedPhone)<span class="jbw-dp-dot">·</span>@endif
                @if ($maskedPhone)<span>{{ $maskedPhone }}</span>@endif
            </p>
        @endif
    </section>

    <section class="jbw-dp-body-row">
        <div class="jbw-dp-body-main">
            <div class="jbw-dp-bio" data-desc-wrap>
                <span class="jbw-dp-bio-text" data-desc-text>{{ $bio }}</span><button type="button" class="jbw-read-more" data-read-more hidden>
                    <span data-read-more-label>Read More</span>
                </button>
            </div>

            <div class="jbw-dp-actions-left">
                <div class="jbw-dp-rating-block">
                    @php
                        $filledStars = (int) floor(min(5, max(0, (float) $displayRating)));
                        $hasHalfStar = ($displayRating - $filledStars) >= 0.5 && $filledStars < 5;
                    @endphp
                    <div class="jbw-dp-rating-score">
                        <strong>{{ number_format($displayRating, 1) }}</strong>
                        <span class="jbw-dp-stars" aria-hidden="true">
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <= $filledStars)
                                    <span class="is-on">★</span>
                                @elseif ($i === $filledStars + 1 && $hasHalfStar)
                                    <span class="is-on is-half">★</span>
                                @else
                                    <span>☆</span>
                                @endif
                            @endfor
                        </span>
                    </div>
                    <p class="jbw-dp-reviews-count">{{ number_format($reviewCount) }} TOTAL REVIEWS</p>
                </div>
                <button type="button" class="jbw-dp-btn jbw-dp-btn--primary" onclick="openVendorProducts({{ (int) $vendor->id }})">OUR PRODUCTS</button>
            </div>
        </div>

        <div class="jbw-dp-side-actions">
            @auth('customer')
                @if (! $webCustomer->is_guest)
                    <a href="{{ route('web.chat.start', $vendor) }}" class="jbw-dp-btn jbw-dp-btn--outline">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Chat
                    </a>
                @else
                    <a href="{{ route('web.register', ['redirect' => route('web.chat.start', $vendor)]) }}" class="jbw-dp-btn jbw-dp-btn--outline">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Chat
                    </a>
                @endif
            @else
                <a href="{{ route('web.login', ['redirect' => route('web.chat.start', $vendor)]) }}" class="jbw-dp-btn jbw-dp-btn--outline">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Chat
                </a>
            @endauth
            <button type="button" class="jbw-dp-btn jbw-dp-btn--teal" onclick="openVendorPortfolio({{ (int) $vendor->id }})">View Portfolio</button>
        </div>
    </section>

    @if ($offeredServices->isNotEmpty())
        <section class="jbw-dp-section">
            <h2 class="jbw-dp-section-title">Services Offered</h2>
            <div class="jbw-dp-services">
                @foreach ($offeredServices as $index => $service)
                    <a href="{{ route('web.catalog.index', ['vendor' => $vendor->id, 'service' => $service->id]) }}" class="jbw-dp-service-card">
                        <div class="jbw-dp-service-media">
                            <img src="{{ $service->imageUrl() ?: $serviceFallbacks[$index % count($serviceFallbacks)] }}" alt="{{ $service->name }}" loading="lazy">
                        </div>
                        <p class="jbw-dp-service-label">{{ $service->name }}{{ str_contains(strtolower($service->name), 'booking') ? '' : ' Booking' }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="jbw-dp-section jbw-reviews-block">
        <h2 class="jbw-dp-section-title">Customer Reviews</h2>

        @if ($reviewCount > 0)
            @php
                $summaryFilled = (int) floor(min(5, max(0, (float) $averageRating)));
                $summaryHalf = (($averageRating - $summaryFilled) >= 0.25) && $summaryFilled < 5;
            @endphp
            <div class="reviews-header">
                <div class="rating-summary">
                    <span class="stars stars--summary" aria-hidden="true">
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= $summaryFilled)
                                <span class="is-on">★</span>
                            @elseif ($i === $summaryFilled + 1 && $summaryHalf)
                                <span class="is-on is-half">★</span>
                            @else
                                <span>☆</span>
                            @endif
                        @endfor
                    </span>
                    <span class="rating-summary-text">{{ number_format($averageRating, 1) }} ({{ number_format($reviewCount) }} {{ $reviewCount === 1 ? 'review' : 'reviews' }})</span>
                </div>
            </div>

            <div class="reviews-grid">
                @foreach ($reviews as $review)
                    @php
                        $rating = (int) round(min(5, max(0, (float) $review->rating)));
                        $name = $review->customer?->name ?: 'Customer';
                        $avatar = $review->customer?->profileImageUrl();
                        $initial = strtoupper(substr($name, 0, 1));
                        $comment = trim((string) ($review->comment ?? ''));
                    @endphp
                    <article class="review-card">
                        <div class="review-top">
                            <div class="review-user">
                                @if ($avatar)
                                    <img src="{{ $avatar }}" alt="{{ $name }}">
                                @else
                                    <span class="review-avatar-fallback" aria-hidden="true">{{ $initial }}</span>
                                @endif
                                <div class="review-user-meta">
                                    <h4>{{ $name }}</h4>
                                    <div class="stars stars--card" aria-label="{{ $rating }} out of 5 stars">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <span @class(['is-on' => $i <= $rating])>{{ $i <= $rating ? '★' : '☆' }}</span>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                            <span class="review-time">{{ $review->created_at?->diffForHumans() }}</span>
                        </div>
                        @if ($comment !== '')
                            <div class="review-comment" data-review-comment>
                                <p class="review-comment-text" data-review-text>“{{ $comment }}”</p>
                                <button type="button" class="jbw-read-more review-read-more" data-review-more hidden>
                                    <span data-review-more-label>Read More</span>
                                </button>
                            </div>
                        @else
                            <p class="review-card-muted">No written comment.</p>
                        @endif
                    </article>
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
    const wrap = document.querySelector('.jbw-designer-profile [data-desc-wrap]');
    if (! wrap) return;

    const textEl = wrap.querySelector('[data-desc-text]');
    const readBtn = wrap.querySelector('[data-read-more]');
    const readLabel = wrap.querySelector('[data-read-more-label]');
    if (! textEl || ! readBtn) return;

    const full = (textEl.textContent || '').trim();
    if (! full) return;

    const lines = 3;
    let expanded = false;
    let collapsed = full;

    function lineHeightPx() {
        const style = window.getComputedStyle(wrap);
        const lh = parseFloat(style.lineHeight);
        if (! Number.isNaN(lh) && lh > 0) return lh;
        const fs = parseFloat(style.fontSize) || 15;
        return fs * 1.7;
    }

    function maxHeightPx() {
        return lineHeightPx() * lines + 1;
    }

    function fitsCollapsed(sample) {
        textEl.textContent = sample;
        readBtn.hidden = false;
        return wrap.scrollHeight <= maxHeightPx();
    }

    function buildCollapsed() {
        textEl.textContent = full;
        readBtn.hidden = true;
        if (wrap.scrollHeight <= maxHeightPx()) {
            return null;
        }

        let low = 0;
        let high = full.length;
        let best = 0;

        while (low <= high) {
            const mid = (low + high) >> 1;
            const sample = full.slice(0, mid).replace(/\s+\S*$/, '').trimEnd() + '…';
            if (fitsCollapsed(sample)) {
                best = mid;
                low = mid + 1;
            } else {
                high = mid - 1;
            }
        }

        if (best < 20) {
            return full.slice(0, 80).replace(/\s+\S*$/, '').trimEnd() + '…';
        }

        return full.slice(0, best).replace(/\s+\S*$/, '').trimEnd() + '…';
    }

    function applyCollapsed() {
        const next = buildCollapsed();
        if (! next) {
            textEl.textContent = full;
            readBtn.hidden = true;
            wrap.classList.remove('is-expanded');
            return false;
        }
        collapsed = next;
        textEl.textContent = collapsed;
        readBtn.hidden = false;
        if (readLabel) readLabel.textContent = 'Read More';
        readBtn.classList.remove('is-expanded');
        wrap.classList.remove('is-expanded');
        return true;
    }

    function applyExpanded() {
        textEl.textContent = full;
        readBtn.hidden = false;
        if (readLabel) readLabel.textContent = 'Read Less';
        readBtn.classList.add('is-expanded');
        wrap.classList.add('is-expanded');
    }

    applyCollapsed();

    readBtn.addEventListener('click', () => {
        expanded = ! expanded;
        if (expanded) applyExpanded();
        else applyCollapsed();
    });

    window.addEventListener('resize', () => {
        if (expanded) return;
        applyCollapsed();
    });
})();

(function () {
    const lines = 3;

    document.querySelectorAll('.jbw-reviews-block [data-review-comment]').forEach((wrap) => {
        const textEl = wrap.querySelector('[data-review-text]');
        const readBtn = wrap.querySelector('[data-review-more]');
        const readLabel = wrap.querySelector('[data-review-more-label]');
        if (! textEl || ! readBtn) return;

        const full = (textEl.textContent || '').trim();
        if (! full) return;

        let expanded = false;
        let collapsed = full;

        function lineHeightPx() {
            const style = window.getComputedStyle(textEl);
            const lh = parseFloat(style.lineHeight);
            if (! Number.isNaN(lh) && lh > 0) return lh;
            return (parseFloat(style.fontSize) || 15) * 1.7;
        }

        function maxHeightPx() {
            return lineHeightPx() * lines + 1;
        }

        function fits(sample) {
            textEl.textContent = sample;
            readBtn.hidden = false;
            return wrap.scrollHeight <= maxHeightPx() + lineHeightPx() * 0.35;
        }

        function buildCollapsed() {
            textEl.textContent = full;
            readBtn.hidden = true;
            if (wrap.scrollHeight <= maxHeightPx()) {
                return null;
            }

            let low = 0;
            let high = full.length;
            let best = 0;
            while (low <= high) {
                const mid = (low + high) >> 1;
                const sample = full.slice(0, mid).replace(/\s+\S*$/, '').trimEnd() + '…';
                if (fits(sample)) {
                    best = mid;
                    low = mid + 1;
                } else {
                    high = mid - 1;
                }
            }

            return full.slice(0, Math.max(best, 40)).replace(/\s+\S*$/, '').trimEnd() + '…';
        }

        function applyCollapsed() {
            const next = buildCollapsed();
            if (! next) {
                textEl.textContent = full;
                readBtn.hidden = true;
                return;
            }
            collapsed = next;
            textEl.textContent = collapsed;
            readBtn.hidden = false;
            if (readLabel) readLabel.textContent = 'Read More';
            readBtn.classList.remove('is-expanded');
        }

        function applyExpanded() {
            textEl.textContent = full;
            readBtn.hidden = false;
            if (readLabel) readLabel.textContent = 'Read Less';
            readBtn.classList.add('is-expanded');
        }

        applyCollapsed();

        readBtn.addEventListener('click', () => {
            expanded = ! expanded;
            if (expanded) applyExpanded();
            else applyCollapsed();
        });
    });
})();
</script>
@endpush

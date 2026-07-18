@extends('web.layouts.app')

@section('title', 'Home')

@section('content')
@php
$defaultHeroImage = 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1800&q=90&fit=crop';
$defaultHeroTitle = "Your style,\nyour moment.";
$defaultHeroSubtitle = "India's premier platform for fashion designer bookings, rental dresses & jewellery. Look extraordinary - without the price tag.";
$defaultHeroUrl = route('web.catalog.index');

$heroSlides = $banners->isNotEmpty()
    ? $banners->map(fn ($banner) => [
        'title' => $banner->title ?: $defaultHeroTitle,
        'subtitle' => $banner->subtitle ?: $defaultHeroSubtitle,
        'redirect_url' => $banner->redirect_url ?: $defaultHeroUrl,
        'image_url' => $banner->image_url ?: $defaultHeroImage,
    ])
    : collect([[
        'title' => $defaultHeroTitle,
        'subtitle' => $defaultHeroSubtitle,
        'redirect_url' => $defaultHeroUrl,
        'image_url' => $defaultHeroImage,
    ]]);

$serviceFallbacks = [
'https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=900&q=85&fit=crop',
'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=900&q=85&fit=crop',
'https://images.unsplash.com/photo-1617032210317-3b0855f047a4?w=900&q=85&fit=crop',
];
$categoryFallbacks = [
'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=900&q=85&fit=crop',
'https://images.unsplash.com/photo-1617137968427-85924c800a22?w=900&q=85&fit=crop',
'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=900&q=85&fit=crop',
];
$genderModalFallbacks = [
    'women' => 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=400&q=80',
    'men' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=400&q=80',
    'kids' => 'https://images.unsplash.com/photo-1503919545889-aef636e10ad4?auto=format&fit=crop&w=400&q=80',
];
$genderModalCategories = $shopCategories->keyBy(fn ($category) => strtolower($category->slug ?? $category->name));
@endphp

{{-- ── Hero ──────────────────────────────────────────────────────── --}}
<section class="jbw-hero" data-hero-carousel>
    <div class="jbw-hero-slides" aria-hidden="true">
        @foreach ($heroSlides as $index => $slide)
            <div class="jbw-hero-slide{{ $index === 0 ? ' is-active' : '' }}">
                <img
                    src="{{ $slide['image_url'] }}"
                    alt=""
                    class="jbw-hero-slide-img"
                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                >
            </div>
        @endforeach
    </div>
    <div class="jbw-hero-overlay"></div>

    <div class="jbw-hero-content-stack">
        @foreach ($heroSlides as $index => $slide)
            <div class="jbw-container jbw-hero-content-wrap jbw-hero-content-panel{{ $index === 0 ? ' is-active' : '' }}" data-hero-panel>
                <div class="jbw-hero-content">
                    <h1 class="jbw-hero-title">{!! nl2br(e($slide['title'])) !!}</h1>
                    <p class="jbw-hero-text">{{ $slide['subtitle'] }}</p>
                    <div class="jbw-hero-actions">
                        <a href="{{ $slide['redirect_url'] }}" class="jbw-btn jbw-btn--primary jbw-btn--lg">Explore collection</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($heroSlides->count() > 1)
        <div class="jbw-hero-nav">
            <button type="button" class="jbw-hero-arrow jbw-hero-arrow--prev" data-hero-prev aria-label="Previous banner">
                &#10094;
            </button>
            <button type="button" class="jbw-hero-arrow jbw-hero-arrow--next" data-hero-next aria-label="Next banner">
                &#10095;
            </button>
        </div>
        <div class="jbw-hero-dots" role="tablist" aria-label="Banner slides">
            @foreach ($heroSlides as $index => $slide)
                <button
                    type="button"
                    class="jbw-hero-dot{{ $index === 0 ? ' is-active' : '' }}"
                    data-hero-dot="{{ $index }}"
                    aria-label="Show banner {{ $index + 1 }}"
                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                ></button>
            @endforeach
        </div>
    @endif
</section>

{{-- ── Trust strip ───────────────────────────────────────────────── --}}
<section class="jbw-trust-strip">
    <div class="jbw-container">
        <div class="jbw-trust-grid">
            <div class="jbw-trust-item">
                <span class="jbw-trust-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </span>
                <div>
                    <p class="jbw-trust-label">Verified boutiques</p>
                    <p class="jbw-trust-sub">Curated designer partners</p>
                </div>
            </div>
            <div class="jbw-trust-item">
                <span class="jbw-trust-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </span>
                <div>
                    <p class="jbw-trust-label">Doorstep delivery</p>
                    <p class="jbw-trust-sub">Per-vendor at checkout</p>
                </div>
            </div>
            <div class="jbw-trust-item">
                <span class="jbw-trust-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </span>
                <div>
                    <p class="jbw-trust-label">Designer rentals</p>
                    <p class="jbw-trust-sub">Outfits &amp; jewellery</p>
                </div>
            </div>
            <div class="jbw-trust-item">
                <span class="jbw-trust-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </span>
                <div>
                    <p class="jbw-trust-label">4.8★ rated</p>
                    <p class="jbw-trust-sub">Loved by customers</p>
                </div>
            </div>
        </div>
    </div>
</section>
@php
    $servicesCount = $services->count();
    $servicesFewClass = $servicesCount > 0 && $servicesCount <= 4 ? ' slider-is-few' : '';
@endphp
<section class="jbw-section-band">
    <div class="jbw-container">
        <div class="jbw-section-head designers-header">
            <div>
                <span class="jbw-eyebrow">What we offer</span>
                <h2 class="jbw-section-title">Our services</h2>
            </div>
            @if ($servicesCount === 0 || $servicesCount > 4)
            <div class="designer-nav">
                <button class="designer-arrow prev" onclick="slideServices(-1)" aria-label="Previous">&#10094;</button>
                <button class="designer-arrow next" onclick="slideServices(1)" aria-label="Next">&#10095;</button>
            </div>
            @endif
        </div>

        <div class="service-slider-wrapper">
            <div class="service-slider{{ $servicesFewClass }}" id="serviceSlider">
                @forelse ($services as $index => $service)
                    <div class="service-card textalign"
                         role="button" tabindex="0"
                         style="cursor: pointer;"
                         onclick="openGenderModal('{{ route('web.services.index', ['service' => $service->id]) }}')">

                        <div class="jbw-tile">
                            <img src="{{ $service->imageUrl() ?: $serviceFallbacks[$index % count($serviceFallbacks)] }}"
                                 alt="{{ $service->name }}">
                        </div>

                        <p class="jbw-step-title textalign">
                            {{ $service->name }}
                        </p>
                    </div>
                @empty
                    @foreach ([['Fashion Designer Booking','Work with a personal stylist'],['Rental Dresses Booking','Hundreds of styles to choose from'],['Rental Jewellery Booking','Complete the look']] as $i => $svc)
                        <div class="service-card textalign"
                             role="button" tabindex="0"
                             style="cursor: pointer;"
                             onclick="openGenderModal('{{ route('web.services.index') }}')">
                            <div class="jbw-tile">
                                <img src="{{ $serviceFallbacks[$i] }}" alt="{{ $svc[0] }}">
                            </div>

                            <p class="jbw-step-title textalign">
                                {{ $svc[0] }}
                            </p>
                        </div>
                    @endforeach
                @endforelse
            </div>
        </div>
    </div>
</section>

<!-- NEW: Pop-up Selection Modal matching image_64c9e4.png -->
<div id="jbwGenderModal" class="jbw-modal-overlay" style="display: none;">
    <div class="jbw-modal-content">
        <button class="jbw-modal-close" onclick="closeGenderModal()">&times;</button>

        <div class="jbw-modal-options-grid">
            @foreach (['women' => 'Women', 'men' => 'Men', 'kids' => 'Kids'] as $genderKey => $genderLabel)
                @php
                    $genderCategory = $genderModalCategories->get($genderKey);
                    $genderImage = $genderCategory?->imageUrl() ?: ($genderModalFallbacks[$genderKey] ?? null);
                @endphp
                <div class="jbw-modal-option" onclick="selectGender('{{ $genderKey }}')">
                    <div class="jbw-modal-circle-thumb">
                        @if ($genderImage)
                            <img src="{{ $genderImage }}" alt="{{ $genderLabel }}">
                        @endif
                    </div>
                    <h3>{{ strtoupper($genderLabel) }}</h3>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Shop by category ──────────────────────────────────────────── --}}
@php
    $categoriesCount = $shopCategories->count();
    $categoriesFewClass = $categoriesCount > 0 && $categoriesCount <= 4 ? ' slider-is-few' : '';
@endphp
<section class="jbw-section-band">
    <div class="jbw-container">
        <div class="jbw-section-head designers-header">
            <div>
                <span class="jbw-eyebrow">Collections</span>
                <h2 class="jbw-section-title">Shop by category</h2>
            </div>
            @if ($categoriesCount === 0 || $categoriesCount > 4)
            <div class="designer-nav">
                <button class="designer-arrow prev" onclick="slideCategories(-1)" aria-label="Previous">
                    &#10094;
                </button>

                <button class="designer-arrow next" onclick="slideCategories(1)" aria-label="Next">
                    &#10095;
                </button>
            </div>
            @endif

        </div>
        <div class="category-slider-wrapper">
            <div class="category-slider{{ $categoriesFewClass }}" id="categorySlider">
                @forelse ($shopCategories as $i => $shopCategory)
                    <a href="{{ route('web.catalog.index', ['category' => $shopCategory->id]) }}" class="category-card textalign">
                        <div class="jbw-tile jbw-tile--category">
                            <img src="{{ $shopCategory->imageUrl() ?: $categoryFallbacks[$i % count($categoryFallbacks)] }}" alt="{{ $shopCategory->name }}">
                        </div>
                        <p class="jbw-step-title textalign">
                            {{ $shopCategory->name }}
                        </p>
                    </a>
                @empty
                    @foreach (['Women', 'Men', 'Kids'] as $i => $label)
                        <a href="{{ route('web.catalog.index') }}" class="category-card textalign">
                            <div class="jbw-tile jbw-tile--category">
                                <img src="{{ $categoryFallbacks[$i] }}" alt="{{ $label }}">
                            </div>
                            <p class="jbw-step-title textalign">
                                {{ $label }}
                            </p>
                        </a>
                    @endforeach
                @endforelse
            </div>
        </div>
    </div>
</section>

{{-- ── Featured designers ───────────────────────────────────────── --}}
@if ($featuredDesigners->isNotEmpty())
@php
    // Duplicate for a seamless infinite marquee loop.
    // When designers are few, repeat them enough times to fill the viewport comfortably.
    $marqueePool = $featuredDesigners;
    if ($featuredDesigners->count() < 6) {
        $marqueePool = collect();
        $repeat = (int) ceil(6 / max(1, $featuredDesigners->count()));
        for ($r = 0; $r < $repeat; $r++) {
            $marqueePool = $marqueePool->concat($featuredDesigners);
        }
    }
@endphp
<section class="jbw-section-band jbw-designer-marquee-section">
    <div class="jbw-container">
        <div class="jbw-section-head">
            <span class="jbw-eyebrow">Curated talent</span>
            <h2 class="jbw-section-title">Featured designers</h2>
            <p class="jbw-section-sub">Verified boutique partners crafting couture, occasion wear and everyday luxe. Hover the strip to pause.</p>
        </div>
    </div>

    <div class="jbw-designer-marquee" data-designer-marquee>
        <div class="jbw-designer-marquee-track" data-designer-marquee-track>
            {{-- Three copies of the pool so backward + forward manual scroll can wrap seamlessly --}}
            @for ($copy = 0; $copy < 3; $copy++)
                @foreach ($marqueePool as $designer)
                    <a href="{{ route('web.vendors.show', $designer) }}"
                        class="jbw-designer-card"
                        data-designer-card
                        @if ($copy > 0) aria-hidden="true" tabindex="-1" @endif>
                        <span class="jbw-designer-avatar-ring">
                            @if ($designer->profileImageUrl() || $designer->shopLogoUrl())
                                <img src="{{ $designer->profileImageUrl() ?: $designer->shopLogoUrl() }}"
                                    alt="{{ $copy === 0 ? $designer->brand_name : '' }}"
                                    class="jbw-designer-avatar-img"
                                    draggable="false"
                                    loading="lazy">
                            @else
                                <span class="jbw-designer-avatar-fallback">
                                    {{ strtoupper(substr($designer->brand_name ?? 'D', 0, 1)) }}
                                </span>
                            @endif
                        </span>
                        <p class="jbw-designer-card-name">{{ $designer->brand_name }}</p>
                        @if ($designer->city)
                            <p class="jbw-designer-card-meta">{{ $designer->city }}</p>
                        @endif
                    </a>
                @endforeach
            @endfor
        </div>
    </div>
</section>
@endif

{{-- ── Promo CTA ─────────────────────────────────────────────────── --}}
<section class="jbw-section-band jbw-section-band--compact">
    <div class="jbw-container">
        <div class="jbw-promo-band">
            <div>
                <h3>Ready for your next occasion?</h3>
                <p>Browse designer rentals, add to cart from multiple boutiques, and checkout once — we handle the rest.</p>
            </div>
            <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--primary jbw-btn--lg">Shop now</a>
        </div>
    </div>
</section>

{{-- ── How it works ─────────────────────────────────────────────── --}}
<section class="jbw-section-band jbw-section-band--warm" id="how-it-works">
    <div class="jbw-container">
        <div class="jbw-section-head">
            <span class="jbw-eyebrow">Simple process</span>
            <h2 class="jbw-section-title">How it works</h2>
        </div>
        <div class="jbw-steps">
            <div class="jbw-step">
                <div class="jbw-step-num">01</div>
                <div>
                    <p class="jbw-step-title">Browse &amp; choose</p>
                    <p class="jbw-step-text">Explore hundreds of designer outfits and jewellery from verified boutiques near you.</p>
                </div>

            </div>
            <div class="jbw-step">
                <div class="jbw-step-num">02</div>
                <div>
                    <p class="jbw-step-title">Book your dates</p>
                    <p class="jbw-step-text">Select your event date, provide your measurements, and confirm your booking in minutes.</p>
                </div>
            </div>
            <div class="jbw-step">
                <div class="jbw-step-num">03</div>
                <div>
                    <p class="jbw-step-title">Wear &amp; return</p>
                    <p class="jbw-step-text">Receive your outfit, look incredible, then simply return it. No storage, no hassle.</p>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    function slideCategories(direction) {
        const slider = document.getElementById('categorySlider');
        if (!slider) return;
        slider.scrollBy({ left: direction * 320, behavior: 'smooth' });
    }

    function slideServices(direction) {
        const slider = document.getElementById('serviceSlider');
        if (!slider) return;
        slider.scrollBy({ left: direction * 320, behavior: 'smooth' });
    }

    (function () {
        const carousel = document.querySelector('[data-hero-carousel]');
        if (!carousel) return;

        const slides = Array.from(carousel.querySelectorAll('.jbw-hero-slide'));
        const panels = Array.from(carousel.querySelectorAll('[data-hero-panel]'));
        const dots = Array.from(carousel.querySelectorAll('[data-hero-dot]'));
        const total = slides.length;

        if (total <= 1) return;

        let index = 0;
        let timer = null;

        const show = (nextIndex) => {
            index = (nextIndex + total) % total;

            slides.forEach((slide, i) => slide.classList.toggle('is-active', i === index));
            panels.forEach((panel, i) => panel.classList.toggle('is-active', i === index));
            dots.forEach((dot, i) => {
                dot.classList.toggle('is-active', i === index);
                dot.setAttribute('aria-selected', i === index ? 'true' : 'false');
            });
        };

        const restartTimer = () => {
            if (timer) clearInterval(timer);
            timer = setInterval(() => show(index + 1), 6000);
        };

        carousel.querySelector('[data-hero-prev]')?.addEventListener('click', () => {
            show(index - 1);
            restartTimer();
        });

        carousel.querySelector('[data-hero-next]')?.addEventListener('click', () => {
            show(index + 1);
            restartTimer();
        });

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                show(Number(dot.dataset.heroDot));
                restartTimer();
            });
        });

        restartTimer();
    })();

    /* ── Featured designers: infinite auto-scrolling marquee w/ swipe + drag ── */
    (function () {
        const marquee = document.querySelector('[data-designer-marquee]');
        if (!marquee) return;
        const track = marquee.querySelector('[data-designer-marquee-track]');
        if (!track) return;

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Track holds 3 identical copies of the pool. We stay in the "middle"
        // copy so the user has room to swipe both directions before we teleport.
        const copyWidth = () => track.scrollWidth / 3;

        const wrap = () => {
            const w = copyWidth();
            if (w <= 0) return;
            // Stay parked in the middle copy: whenever we drift into copy 1 or copy 3,
            // silently jump back into copy 2 by +/- one copy width. Because the copies
            // are identical, the user sees no visual jump.
            if (marquee.scrollLeft >= 2 * w) {
                marquee.scrollLeft -= w;
            } else if (marquee.scrollLeft < w) {
                marquee.scrollLeft += w;
            }
        };

        const SPEED = 0.55;              // px per 16ms tick
        const RESUME_DELAY = 1400;       // ms — resume auto-scroll after user stops interacting
        const DRAG_THRESHOLD = 6;        // px — how far the pointer must move before it counts as a drag

        let paused = false;
        let resumeTimer = null;
        let rafId = null;
        let lastTs = 0;

        const scheduleResume = () => {
            if (resumeTimer) clearTimeout(resumeTimer);
            resumeTimer = setTimeout(() => { paused = false; }, RESUME_DELAY);
        };

        const step = (ts) => {
            const dt = lastTs ? (ts - lastTs) : 16;
            lastTs = ts;
            if (!paused && !prefersReducedMotion) {
                marquee.scrollLeft += SPEED * (dt / 16);
            }
            wrap();
            rafId = requestAnimationFrame(step);
        };

        // ─ Hover / focus / wheel / touch pause ─
        marquee.addEventListener('mouseenter', () => {
            paused = true;
            if (resumeTimer) { clearTimeout(resumeTimer); resumeTimer = null; }
        });
        marquee.addEventListener('mouseleave', () => { if (!isDown) paused = false; });
        marquee.addEventListener('focusin',  () => { paused = true; });
        marquee.addEventListener('focusout', () => { paused = false; });

        marquee.addEventListener('wheel',      () => { paused = true; scheduleResume(); }, { passive: true });
        marquee.addEventListener('touchstart', () => { paused = true; }, { passive: true });
        marquee.addEventListener('touchend',   () => { scheduleResume(); }, { passive: true });
        marquee.addEventListener('touchcancel',() => { scheduleResume(); }, { passive: true });

        marquee.addEventListener('scroll', wrap, { passive: true });

        // ─ Pointer drag-to-scroll (only engages after real movement, so taps still click) ─
        let isDown = false;
        let dragStarted = false;
        let justDragged = false;
        let startX = 0;
        let scrollStart = 0;
        let activePointerId = null;

        marquee.addEventListener('pointerdown', (e) => {
            // ignore right / middle mouse
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            isDown = true;
            dragStarted = false;
            justDragged = false;
            startX = e.clientX;
            scrollStart = marquee.scrollLeft;
            activePointerId = e.pointerId;
        });

        marquee.addEventListener('pointermove', (e) => {
            if (!isDown || e.pointerId !== activePointerId) return;
            const dx = e.clientX - startX;
            if (!dragStarted) {
                if (Math.abs(dx) < DRAG_THRESHOLD) return; // still could be a tap
                dragStarted = true;
                paused = true;
                marquee.classList.add('is-dragging');
                try { marquee.setPointerCapture(e.pointerId); } catch (_) {}
            }
            marquee.scrollLeft = scrollStart - dx;
        });

        const endDrag = (e) => {
            if (!isDown) return;
            const wasDrag = dragStarted;
            isDown = false;
            dragStarted = false;
            activePointerId = null;
            marquee.classList.remove('is-dragging');
            try { if (e && e.pointerId != null) marquee.releasePointerCapture(e.pointerId); } catch (_) {}
            if (wasDrag) {
                justDragged = true;
                // Click event fires synchronously after pointerup — clear on next tick.
                setTimeout(() => { justDragged = false; }, 0);
                scheduleResume();
            }
        };

        marquee.addEventListener('pointerup', endDrag);
        marquee.addEventListener('pointercancel', endDrag);

        // Only cancel a *drag* when the pointer physically leaves the container.
        marquee.addEventListener('pointerleave', (e) => {
            if (dragStarted) endDrag(e);
        });

        // If the user was dragging, swallow the subsequent click so we don't
        // accidentally navigate to a designer they were just scrubbing past.
        // For a real tap (no movement), justDragged stays false and the link fires.
        marquee.addEventListener('click', (e) => {
            if (justDragged) {
                e.preventDefault();
                e.stopPropagation();
                justDragged = false;
            }
        }, true);

        // Prevent native image drag from hijacking the pointer-drag.
        marquee.querySelectorAll('img').forEach((img) => { img.draggable = false; });

        // Seed position in the middle copy, then start the loop.
        const start = () => {
            const w = copyWidth();
            if (w > 0) marquee.scrollLeft = w; // land in the start of copy 2
            rafId = requestAnimationFrame(step);
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start, { once: true });
        } else {
            // Wait a frame so track.scrollWidth is accurate.
            requestAnimationFrame(start);
        }

        window.addEventListener('resize', () => { wrap(); });
        window.addEventListener('beforeunload', () => {
            if (rafId) cancelAnimationFrame(rafId);
        });
    })();
</script>
<script>
    let currentServiceUrl = '';

function openGenderModal(baseRouteUrl) {
    currentServiceUrl = baseRouteUrl;
    document.getElementById('jbwGenderModal').style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Lock background scrolling
}

function closeGenderModal() {
    document.getElementById('jbwGenderModal').style.display = 'none';
    document.body.style.overflow = ''; // Release scroll lock
}

function selectGender(selectedGenderKey) {
    if (currentServiceUrl) {
        // Parse current query parameters to avoid breaking existing service variables
        const separator = currentServiceUrl.includes('?') ? '&' : '?';
        const finalRedirectUrl = `${currentServiceUrl}${separator}gender=${selectedGenderKey}`;

        // Push view redirection path natively
        window.location.href = finalRedirectUrl;
    }
}

// Close the modal window gracefully if clicked anywhere outside the content box
window.onclick = function(event) {
    const modalElement = document.getElementById('jbwGenderModal');
    if (event.target === modalElement) {
        closeGenderModal();
    }
}
    </script>
@endpush

@endsection

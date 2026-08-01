@extends('web.layouts.app')

@section('title', 'Home')

@section('content')
@php
$defaultHeroImage = 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1800&q=90&fit=crop';
$defaultHeroTitle = "Your style,\nyour moment.";
$defaultHeroSubtitle = "Fashion Designer, Rented Dress & Rented Jewellery Booking App. Exclusive access to the world's most coveted wardrobes.";
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
@endphp

{{-- ── Hero ──────────────────────────────────────────────────────── --}}
<section class="jbw-hero" data-hero-carousel>
    <div class="jbw-hero-frame">
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
                            <a href="{{ $slide['redirect_url'] }}" class="jbw-btn jbw-btn--hero">Book Your Look</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($heroSlides->count() > 1)
        <div class="jbw-hero-nav">
            <button type="button" class="jbw-hero-arrow jbw-hero-arrow--prev" data-hero-prev aria-label="Previous banner">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button type="button" class="jbw-hero-arrow jbw-hero-arrow--next" data-hero-next aria-label="Next banner">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
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
                    <p class="jbw-trust-label">{{ number_format($overallRating['average'], 1) }}★ rated</p>
                    <p class="jbw-trust-sub">
                        @if ($overallRating['count'] > 0)
                            From {{ number_format($overallRating['count']) }} customer review{{ $overallRating['count'] === 1 ? '' : 's' }}
                        @else
                            Loved by customers
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@php
    $servicesCount = $services->count();
    $servicesFewClass = ($servicesCount === 0 || $servicesCount <= 4) ? ' slider-is-few' : '';
@endphp
<section class="jbw-section-band">
    <div class="jbw-container">
        <div class="jbw-section-head designers-header">
            <div>
                <h2 class="jbw-section-title">Our Services</h2>
            </div>
            @if ($servicesCount > 4)
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
                         onclick="openGenderModal({{ (int) $service->id }})">

                        <div class="jbw-tile jbw-tile--category">
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
                             onclick="openGenderModal(null)">
                            <div class="jbw-tile jbw-tile--category">
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

{{-- ── Shop by category ──────────────────────────────────────────── --}}
@php
    $categoriesCount = $shopCategories->count();
    $categoriesFewClass = ($categoriesCount === 0 || $categoriesCount <= 4) ? ' slider-is-few' : '';
@endphp
<section class="jbw-section-band">
    <div class="jbw-container">
        <div class="jbw-section-head designers-header">
            <div>
                <h2 class="jbw-section-title">Shop by Category</h2>
            </div>
            @if ($categoriesCount > 4)
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
                    @php $genderKey = strtolower((string) ($shopCategory->slug ?: $shopCategory->name)); @endphp
                    <div class="category-card textalign"
                         role="button" tabindex="0"
                         style="cursor: pointer;"
                         onclick="openServicesModal(@js($genderKey))"
                         onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openServicesModal(@js($genderKey));}">
                        <div class="jbw-tile jbw-tile--category">
                            <img src="{{ $shopCategory->imageUrl() ?: $categoryFallbacks[$i % count($categoryFallbacks)] }}" alt="{{ $shopCategory->name }}">
                        </div>
                        <p class="jbw-step-title textalign">
                            {{ $shopCategory->name }}
                        </p>
                    </div>
                @empty
                    @foreach (['Women', 'Men', 'Kids'] as $i => $label)
                        <div class="category-card textalign"
                             role="button" tabindex="0"
                             style="cursor: pointer;"
                             onclick="openServicesModal(@js(strtolower($label)))"
                             onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openServicesModal(@js(strtolower($label)));}">
                            <div class="jbw-tile jbw-tile--category">
                                <img src="{{ $categoryFallbacks[$i] }}" alt="{{ $label }}">
                            </div>
                            <p class="jbw-step-title textalign">
                                {{ $label }}
                            </p>
                        </div>
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
        <div class="jbw-section-head designers-header">
            <div>
                <h2 class="jbw-section-title">Featured Designers</h2>
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
                    </a>
                @endforeach
            @endfor
        </div>
    </div>
     </div>
</section>
@endif

{{-- ── App download banner ───────────────────────────────────────── --}}
<section class="jbw-section-band jbw-section-band--compact" id="how-it-works">
    <div class="jbw-container">
        <div class="jbw-app-band">
            <div class="jbw-app-band-info">
                <x-web.logo variant="header" class="jbw-app-band-logo" />
                <h2 class="jbw-app-band-title">Fashion. Style.<br><span>Booked.</span></h2>
                <p class="jbw-app-band-sub">Your go-to app for booking Fashion Designers,<br>Rented Dresses &amp; Rented Jewellery.</p>
                <div class="jbw-app-band-tags">
                    <span class="jbw-app-band-tag">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
                        <span>Fashion Designer<br>Booking</span>
                    </span>
                    <span class="jbw-app-band-tag">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 6a2 2 0 1 1 2-2"/><path d="M12 6 3.5 12.5a1.4 1.4 0 0 0 .85 2.5h15.3a1.4 1.4 0 0 0 .85-2.5L12 6z"/></svg>
                        <span>Rented Dress<br>Booking</span>
                    </span>
                    <span class="jbw-app-band-tag">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="6 3 18 3 22 9 12 21 2 9"/><path d="M2 9h20"/><path d="M12 21 8 9l2-6"/><path d="m12 21 4-12-2-6"/></svg>
                        <span>Rented Jewellery<br>Booking</span>
                    </span>
                </div>
                <div class="jbw-app-band-download">
                    <img class="jbw-app-band-qr"
                         src="https://api.qrserver.com/v1/create-qr-code/?size=148x148&data={{ urlencode(url('/')) }}"
                         alt="Scan to download the app" loading="lazy">
                    <div class="jbw-app-band-download-copy">
                        <p class="jbw-app-band-download-title">Download<br>the app now!</p>
                        <p class="jbw-app-band-download-sub">Book. Wear. Shine.</p>
                    </div>
                    <div class="jbw-app-band-stores">
                        <a href="#" class="jbw-store-badge" aria-label="Download on the App Store">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.365 1.43c0 1.14-.42 2.2-1.26 3.08-.9.98-2.05 1.55-3.19 1.46-.05-1.11.44-2.24 1.26-3.1.87-.92 2.19-1.52 3.19-1.44zM20.94 17.06c-.53 1.22-.78 1.76-1.46 2.84-.95 1.5-2.29 3.37-3.95 3.38-1.47.02-1.85-.96-3.85-.95-2 .01-2.42.97-3.9.96-1.66-.02-2.93-1.7-3.88-3.2-2.66-4.09-2.94-8.79-1.25-11.29 1.2-1.77 3.09-2.8 4.87-2.8 1.81 0 2.95 1 4.45 1 1.45 0 2.34-1 4.43-1 1.58 0 3.26.86 4.45 2.35-3.91 2.14-3.28 7.72.09 8.71z"/></svg>
                            <span><small>Download on the</small><strong>App Store</strong></span>
                        </a>
                        <a href="#" class="jbw-store-badge" aria-label="Get it on Google Play">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4.05 2.29a1.4 1.4 0 0 0-.55 1.12v17.18c0 .46.21.87.55 1.12l8.9-9.71-8.9-9.71zm10.1 8.4 2.86-3.12-11.4-6.42a1.5 1.5 0 0 0-.61-.18l9.15 9.72zm0 2.62-9.15 9.72c.21-.02.42-.08.61-.18l11.4-6.42-2.86-3.12zm5.9-2.62-2.1-1.18-3.1 3.38.01-1.16 3.09 3.34 2.1-1.18c1.33-.75 1.33-2.45 0-3.2z"/></svg>
                            <span><small>Get it on</small><strong>Google Play</strong></span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="jbw-app-band-visual">
                <img src="https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=1100&q=85&fit=crop" alt="Designer gown ready to book" loading="lazy">
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
@endpush

@endsection

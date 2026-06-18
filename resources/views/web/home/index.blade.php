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
@endphp

{{-- ── Hero ──────────────────────────────────────────────────────── --}}
<section class="jbw-hero borderbanner" data-hero-carousel style="margin-bottom: 20px;">
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
                        <a href="{{ $slide['redirect_url'] }}" class="jbw-btn jbw-btn--primary jbw-btn--lg lookbutton">BOOK YOUR LOOK</a>
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

{{-- ── Our services ──────────────────────────────────────────────── --}}
<section class="jbw-section-band">
    <div class="jbw-container">
        <!-- <div class="jbw-section-head alignmentheading ma"> -->
        <!-- <span class="jbw-eyebrow">Our services</span> -->
        <!-- <h2 class="jbw-section-title ">Our services</h2> -->
        <!-- <h2 class="jbw-section-title">Choose how you want to look fabulous</h2> -->
        <!-- <p class="jbw-section-sub">From complete designer consultations to rental dresses for a single evening - we have you covered.</p> -->
        <!-- </div> -->
        <div class="jbw-section-head designers-header">
            <h2 class="jbw-section-title">Our services</h2>


            <div class="designer-nav">
                <button class="designer-arrow prev" onclick="slideServices(-1)">
                    &#10094;
                </button>

                <button class="designer-arrow next" onclick="slideServices(1)">
                    &#10095;
                </button>
            </div>

        </div>
        {{-- Legacy grid layout kept for reference
        <div class="jbw-grid-3">
            @forelse ($services as $index => $service)
            ...
            @endforelse
        </div>
        --}}
        <div class="service-slider-wrapper">



    <div class="service-slider" id="serviceSlider">
        @forelse ($services as $index => $service)
            <a class="service-card textalign"
               href="{{ route('web.services.index', ['service' => $service->id]) }}">

                <div class="jbw-tile">
                    <img src="{{ $service->imageUrl() ?: $serviceFallbacks[$index % count($serviceFallbacks)] }}"
                         alt="{{ $service->name }}">
                </div>

                <p class="jbw-step-title textalign">
                    {{ $service->name }}
                </p>
            </a>
        @empty
            @foreach ([['Fashion Designer Booking','Work with a personal stylist'],['Rental Dresses Booking','Hundreds of styles to choose from'],['Rental Jewellery Booking','Complete the look']] as $i => $svc)
                <a href="{{ route('web.services.index') }}" class="service-card textalign">
                    <div class="jbw-tile">
                        <img src="{{ $serviceFallbacks[$i] }}" alt="{{ $svc[0] }}">
                    </div>

                    <p class="jbw-step-title textalign">
                        {{ $svc[0] }}
                    </p>
                </a>
            @endforeach
        @endforelse
    </div>
        </div>
    </div>
</section>

{{-- ── Shop by category ──────────────────────────────────────────── --}}
<section class="jbw-section-band">
    <div class="jbw-container">
        <div class="jbw-section-head designers-header">
            <h2 class="jbw-section-title">Shop by Category</h2>


            <div class="designer-nav">
                <button class="designer-arrow prev" onclick="slideCategories(-1)">
                    &#10094;
                </button>

                <button class="designer-arrow next" onclick="slideCategories(1)">
                    &#10095;
                </button>
            </div>

        </div>
        <div class="category-slider-wrapper">
            <div class="category-slider" id="categorySlider">
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
<section class="jbw-section-band ">
    <div class="jbw-container">
        <div class="jbw-section-head designers-header">
            <h2 class="jbw-section-title">Featured Designers</h2>

            @if($featuredDesigners->count() > 0)
            <div class="designer-nav">
                <button class="designer-arrow prev" onclick="slideDesigners(-1)">
                    &#10094;
                </button>

                <button class="designer-arrow next" onclick="slideDesigners(1)">
                    &#10095;
                </button>
            </div>
            @endif
        </div>
        <div class="designer-carousel">
            <div class="designer-slider" id="designerSlider">
                @foreach ($featuredDesigners as $designer)
                <a href="{{ route('web.vendors.show', $designer) }}" class="jbw-designer">
                    @if ($designer->profileImageUrl() || $designer->shopLogoUrl())
                    <img src="{{ $designer->profileImageUrl() ?: $designer->shopLogoUrl() }}"
                        alt="{{ $designer->brand_name }}"
                        class="jbw-designer-avatar">
                    @else
                    <span class="jbw-designer-avatar jbw-designer-fallback">
                        {{ strtoupper(substr($designer->brand_name ?? 'D', 0, 1)) }}
                    </span>
                    @endif

                    <p class="jbw-step-title textalign textlimit">
                        {{ $designer->brand_name }}
                    </p>
                </a>
                @endforeach
            </div>




        </div>
    </div>
</section>
@endif


{{-- ── Banner Section ──────────────────────────────────────────────── --}}
<section class="jbw-section-band p-0">
    <div class="jbw-container">
        <img src="../../../../assets/frontend/bannerjustbook.png" />
    </div>
</section>
{{-- ── Stats strip ──────────────────────────────────────────────── --}}
<!-- <section class="jbw-stats-strip">
    <div class="jbw-container">
        <div class="jbw-stats-grid">
            <div class="jbw-stat">
                <span class="jbw-stat-num">500+</span>
                <span class="jbw-stat-lbl">Designer outfits</span>
            </div>
            <div class="jbw-stat-div" aria-hidden="true"></div>
            <div class="jbw-stat">
                <span class="jbw-stat-num">50+</span>
                <span class="jbw-stat-lbl">Verified boutiques</span>
            </div>
            <div class="jbw-stat-div" aria-hidden="true"></div>
            <div class="jbw-stat">
                <span class="jbw-stat-num">20+</span>
                <span class="jbw-stat-lbl">Cities across India</span>
            </div>
            <div class="jbw-stat-div" aria-hidden="true"></div>
            <div class="jbw-stat">
                <span class="jbw-stat-num">4.8&#9733;</span>
                <span class="jbw-stat-lbl">Customer rating</span>
            </div>
        </div>
    </div>
</section> -->

{{-- ── How it works ─────────────────────────────────────────────── --}}
<!-- <section class="jbw-section-band jbw-section-band--warm" id="how-it-works">
    <div class="jbw-container">
        <div class="jbw-section-head">
            <span class="jbw-eyebrow">How it works</span>
            <h2 class="jbw-section-title">Three steps to your perfect look</h2>
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
</section> -->


{{-- Legacy duplicate sections removed (were HTML comments but Blade still compiled them) --}}

@push('scripts')
<script>
    function slideDesigners(direction) {
        const slider = document.getElementById('designerSlider');
        if (!slider) return;
        slider.scrollBy({ left: direction * 300, behavior: 'smooth' });
    }

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
</script>
@endpush

@endsection

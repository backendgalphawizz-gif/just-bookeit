@extends('web.layouts.app')

@section('title', 'Home')

@section('content')
@php
    $hero = $banners->first();
    $heroImage = $hero?->image_url ?: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1800&q=90&fit=crop';

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
<section class="jbw-hero">
    <div class="jbw-hero-slide" style="background-image:url('{{ $heroImage }}')"></div>
    <div class="jbw-hero-overlay"></div>
    <div class="jbw-container jbw-hero-content-wrap">
        <div class="jbw-hero-content">
            <p class="jbw-hero-kicker">{{ $webBranding['name'] ?? 'Just Book IT' }}</p>
            <h1 class="jbw-hero-title">{!! nl2br(e($hero?->title ?? "Your style,\nyour moment.")) !!}</h1>
            <p class="jbw-hero-text">{{ $hero?->subtitle ?? "India's premier platform for fashion designer bookings, rental dresses & jewellery. Look extraordinary - without the price tag." }}</p>
            <div class="jbw-hero-actions">
                <a href="{{ $hero?->redirect_url ?: route('web.catalog.index') }}" class="jbw-btn jbw-btn--primary jbw-btn--lg">Explore collection</a>
                <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--hero-secondary">Browse services</a>
            </div>
        </div>
    </div>
    <div class="jbw-hero-scroll" aria-hidden="true">
        <div class="jbw-hero-scroll-line"></div>
        <span>Scroll</span>
    </div>
</section>

{{-- ── Stats strip ──────────────────────────────────────────────── --}}
<section class="jbw-stats-strip">
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
</section>

{{-- ── How it works ─────────────────────────────────────────────── --}}
<section class="jbw-section-band jbw-section-band--warm" id="how-it-works">
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
</section>

{{-- ── Our services ──────────────────────────────────────────────── --}}
<section class="jbw-section-band">
    <div class="jbw-container">
        <div class="jbw-section-head">
            <span class="jbw-eyebrow">Our services</span>
            <h2 class="jbw-section-title">Choose how you want to look fabulous</h2>
            <p class="jbw-section-sub">From complete designer consultations to rental dresses for a single evening - we have you covered.</p>
        </div>
        <div class="jbw-grid-3">
            @forelse ($services as $index => $service)
                <a href="{{ route('web.catalog.index', ['service' => $service->id]) }}" class="jbw-tile">
                    <img src="{{ $service->imageUrl() ?: $serviceFallbacks[$index % count($serviceFallbacks)] }}" alt="{{ $service->name }}">
                    <div class="jbw-tile-overlay"></div>
                    <div class="jbw-tile-body">
                        <span class="jbw-tile-label">{{ $service->name }}</span>
                        <span class="jbw-tile-meta">Book now &rarr;</span>
                    </div>
                </a>
            @empty
                @foreach ([['Fashion Designer Booking','Work with a personal stylist'],['Rental Dresses Booking','Hundreds of styles to choose from'],['Rental Jewellery Booking','Complete the look']] as $i => $svc)
                    <a href="{{ route('web.catalog.index') }}" class="jbw-tile">
                        <img src="{{ $serviceFallbacks[$i] }}" alt="{{ $svc[0] }}">
                        <div class="jbw-tile-overlay"></div>
                        <div class="jbw-tile-body">
                            <span class="jbw-tile-label">{{ $svc[0] }}</span>
                            <span class="jbw-tile-meta">{{ $svc[1] }}</span>
                        </div>
                    </a>
                @endforeach
            @endforelse
        </div>
    </div>
</section>

{{-- ── Shop by category ──────────────────────────────────────────── --}}
<section class="jbw-section-band jbw-section-band--dark">
    <div class="jbw-container">
        <div class="jbw-section-head">
            <span class="jbw-eyebrow" style="color:rgb(255 255 255/0.5)">Collections</span>
            <h2 class="jbw-section-title" style="color:#fff">Shop by category</h2>
            <p class="jbw-section-sub" style="color:rgb(255 255 255/0.6)">Women, men &amp; kids collections</p>
        </div>
        <div class="jbw-grid-3">
            @forelse ($shopCategories as $i => $shopCategory)
                <a href="{{ route('web.catalog.index', ['category' => $shopCategory->id]) }}" class="jbw-tile" style="min-height:18rem">
                    <img src="{{ $shopCategory->imageUrl() ?: $categoryFallbacks[$i % count($categoryFallbacks)] }}" alt="{{ $shopCategory->name }}">
                    <div class="jbw-tile-overlay"></div>
                    <div class="jbw-tile-body">
                        <span class="jbw-tile-label">{{ $shopCategory->name }}</span>
                    </div>
                </a>
            @empty
                @foreach (['Women','Men','Kids'] as $i => $label)
                    <a href="{{ route('web.catalog.index') }}" class="jbw-tile" style="min-height:18rem">
                        <img src="{{ $categoryFallbacks[$i] }}" alt="{{ $label }}">
                        <div class="jbw-tile-overlay"></div>
                        <div class="jbw-tile-body">
                            <span class="jbw-tile-label">{{ $label }}</span>
                        </div>
                    </a>
                @endforeach
            @endforelse
        </div>
    </div>
</section>

{{-- ── Featured designers ───────────────────────────────────────── --}}
@if ($featuredDesigners->isNotEmpty())
<section class="jbw-section-band jbw-section-band--navy">
    <div class="jbw-container">
        <div class="jbw-section-head">
            <span class="jbw-eyebrow" style="color:rgba(242,81,35,0.85)">Designers</span>
            <h2 class="jbw-section-title" style="color:#fff">Top-rated boutiques</h2>
            <p class="jbw-section-sub" style="color:rgb(255 255 255/0.55)">Verified designers near you</p>
        </div>
        <div class="jbw-designers">
            @foreach ($featuredDesigners as $designer)
                <a href="{{ route('web.vendors.show', $designer) }}" class="jbw-designer">
                    @if ($designer->profileImageUrl() || $designer->shopLogoUrl())
                        <img src="{{ $designer->profileImageUrl() ?: $designer->shopLogoUrl() }}" alt="{{ $designer->brand_name }}" class="jbw-designer-avatar">
                    @else
                        <span class="jbw-designer-avatar jbw-designer-fallback">{{ strtoupper(substr($designer->brand_name ?? 'D', 0, 1)) }}</span>
                    @endif
                    <span class="jbw-designer-name" style="color:rgb(255 255 255/0.85)">{{ $designer->brand_name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ── CTA band ──────────────────────────────────────────────────── --}}
<section class="jbw-section-band jbw-section-band--cta">
    <div class="jbw-container" style="text-align:center;position:relative;z-index:1">
        <span class="jbw-eyebrow" style="color:rgb(255 255 255/0.5)">Just Book IT</span>
        <h2 class="jbw-section-title" style="color:#fff;margin-top:0.5rem">Fashion. Style. Booked.</h2>
        <p class="jbw-section-sub" style="color:rgb(255 255 255/0.6)">Your go-to platform for designer rentals on web &amp; mobile. Available across India.</p>
        @guest('customer')
            <div class="jbw-cta-actions">
                <a href="{{ route('web.login') }}" class="jbw-btn jbw-btn--primary jbw-btn--lg">Get started free</a>
                <a href="{{ route('web.catalog.index') }}" class="jbw-btn" style="background:rgb(255 255 255/0.12);color:#fff;border-color:rgb(255 255 255/0.25);backdrop-filter:blur(4px)">Browse catalog</a>
            </div>
        @else
            @if ($webCustomer->is_guest)
                <div class="jbw-cta-actions">
                    <a href="{{ route('web.register', ['redirect' => route('web.catalog.index')]) }}" class="jbw-btn jbw-btn--primary jbw-btn--lg">Create account</a>
                    <a href="{{ route('web.catalog.index') }}" class="jbw-btn" style="background:rgb(255 255 255/0.12);color:#fff;border-color:rgb(255 255 255/0.25);backdrop-filter:blur(4px)">Browse catalog</a>
                </div>
            @else
                <div class="jbw-cta-actions">
                    <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--primary jbw-btn--lg">Browse catalog</a>
                </div>
            @endif
        @endguest
    </div>
</section>

@endsection

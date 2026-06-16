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

<div class="jbw-container">
    <nav class="jbw-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('web.catalog.index') }}" class="jbw-breadcrumb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            Back to catalog
        </a>
    </nav>

    @php
        $galleryUrls = $item->galleryImageUrls();
        if ($galleryUrls === []) {
            $galleryUrls = [$fallbackImg];
        }
    @endphp

    <div class="jbw-product-detail">
        {{-- Gallery --}}
        <div class="jbw-gallery-main">
            <img id="jbw-gallery-main" src="{{ $galleryUrls[0] }}" alt="{{ $item->title }}">
            @if (count($galleryUrls) > 1)
                <div style="display:flex;gap:0.5rem;margin-top:0.75rem;flex-wrap:wrap">
                    @foreach ($galleryUrls as $url)
                        <button type="button" onclick="document.getElementById('jbw-gallery-main').src='{{ $url }}'" style="border:2px solid transparent;border-radius:0.5rem;padding:0;background:none;cursor:pointer">
                            <img src="{{ $url }}" alt="" style="width:4rem;height:4rem;object-fit:cover;border-radius:0.5rem">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div class="jbw-detail-info">
            <p class="jbw-product-brand">{{ $item->vendor?->brand_name ?? 'Designer' }}</p>
            <h1 class="jbw-product-detail-title">{{ $item->title }}</h1>
            <p class="jbw-detail-price">{{ $item->rentalPriceLabel() }}</p>

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
                        <strong style="font-size:0.9375rem">{{ $item->vendor->brand_name }}</strong>
                        <p style="margin:0.125rem 0 0;font-size:0.8125rem;color:var(--c-muted)">
                            ★ {{ number_format($item->vendor->rating ?? 4.5, 1) }}
                            @if($item->vendor->city) · {{ $item->vendor->city }} @endif
                        </p>
                    </div>
                    <svg style="margin-left:auto;color:var(--c-muted)" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            @endif

            <div class="jbw-detail-actions">
                @auth('customer')
                    @if ($webCustomer->is_guest)
                        <a href="{{ route('web.register', ['redirect' => route('web.bookings.overview', $item)]) }}" class="jbw-btn jbw-btn--primary jbw-btn--lg">Create account to book</a>
                    @else
                        <a href="{{ route('web.bookings.overview', $item) }}" class="jbw-btn jbw-btn--primary jbw-btn--lg">Book now</a>
                    @endif
                @else
                    <a href="{{ route('web.login', ['redirect' => route('web.bookings.overview', $item)]) }}" class="jbw-btn jbw-btn--primary jbw-btn--lg">Sign in to book</a>
                @endauth
                @auth('customer')
                    @unless ($webCustomer->is_guest)
                        @if ($item->vendor)
                            <a href="{{ route('web.chat.start', $item->vendor) }}" class="jbw-btn jbw-btn--outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                Chat
                            </a>
                        @endif
                    @else
                        @if ($item->vendor)
                            <a href="{{ route('web.register', ['redirect' => route('web.chat.start', $item->vendor)]) }}" class="jbw-btn jbw-btn--outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                Chat
                            </a>
                        @endif
                    @endunless
                @else
                    @if ($item->vendor)
                        <a href="{{ route('web.login', ['redirect' => route('web.chat.start', $item->vendor)]) }}" class="jbw-btn jbw-btn--outline">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            Chat
                        </a>
                    @endif
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
</div>
@endsection

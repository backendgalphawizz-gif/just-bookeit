@extends('web.layouts.app')

@section('title', $vendor->brand_name)

@section('content')
<div class="jbw-container">
    <div class="jbw-vendor-hero">
        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200&q=80" alt="{{ $vendor->brand_name }} cover">
    </div>

    <div class="jbw-vendor-head">
        @if ($vendor->profileImageUrl() || $vendor->shopLogoUrl())
            <img src="{{ $vendor->profileImageUrl() ?: $vendor->shopLogoUrl() }}" alt="" class="jbw-vendor-head-avatar">
        @else
            <span class="jbw-vendor-head-avatar jbw-designer-fallback" style="display:grid;place-items:center;font-size:1.5rem">{{ strtoupper(substr($vendor->brand_name, 0, 1)) }}</span>
        @endif
        <div>
            <h2 class="jbw-section-title" style="font-family:var(--font-serif); margin-bottom:0rem !important; margin-top:1.5rem; font-size:1.25rem; margin-bottom:1rem;">{{ $vendor->brand_name }}</h2>
            <p class="jbw-page-subtitle" style="margin-top: 0rem;">
                <font style="color:#f5a623;">★</font> {{ number_format($vendor->rating, 1) }}
                @if($vendor->city) · {{ $vendor->city }} @endif
                <!-- @if($vendor->mobile) · {{ $vendor->mobile }} @endif -->
            </p>
            <div class="jbw-detail-actions" style="margin-top:0.75rem">
                @auth('customer')
                    @unless ($webCustomer->is_guest)
                        <a href="{{ route('web.chat.start', $vendor) }}" class="jbw-product-price jbw-btn jbw-btn--outline jbw-btn--sm">
                            <img src="../../../../assets/frontend/chat11.png"/>
                            Chat
                        </a>
                    @else
                        <a href="{{ route('web.register', ['redirect' => route('web.chat.start', $vendor)]) }}" class="jbw-btn jbw-btn--outline jbw-btn--sm">
                            <img src="../../../../assets/frontend/chat11.png"/>
                            Chat
                        </a>
                    @endunless
                @else
                    <a href="{{ route('web.login', ['redirect' => route('web.chat.start', $vendor)]) }}" class="jbw-btn jbw-btn--outline jbw-btn--sm">
                        <img src="../../../../assets/frontend/chat11.png"/>
                        Chat
                    </a>
                @endauth
            </div>
        </div>
    </div>

    @if ($vendor->address)
        <div class="jbw-card" style="margin-bottom:1.5rem">
            <p style="margin:0;line-height:1.6;color:var(--c-muted)">{{ $vendor->address }}</p>
        </div>
    @endif

    <h2 class="jbw-section-title" style="font-size:1.25rem;margin-bottom:1rem">Portfolio</h2>
    <div class="jbw-product-grid">
        @forelse ($portfolio as $item)
            <a href="{{ route('web.catalog.show', $item) }}" class="jbw-product-card">
                <div class="jbw-product-card-img">
                    <img src="{{ $item->displayImageUrl() ?: 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=600&q=80' }}" alt="{{ $item->title }}" loading="lazy">
                </div>
                <div class="jbw-product-card-body">
                    <p class="jbw-product-title">{{ $item->title }}</p>
                    <p class="jbw-product-price">{{ $item->rentalPriceLabel() }}</p>
                </div>
            </a>
        @empty
            <div class="jbw-card" style="grid-column:1/-1">
                <p style="color:var(--c-muted);text-align:center;margin:0">No portfolio items yet.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

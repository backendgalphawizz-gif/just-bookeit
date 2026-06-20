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
            Details
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
        <!-- <div class="jbw-gallery-main">
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
        </div> -->
<div class="jbw-gallery-wrap">

    @if (count($galleryUrls) > 1)
        <div class="jbw-gallery-thumbs">
            @foreach ($galleryUrls as $url)
                <button type="button"
                    onclick="document.getElementById('jbw-gallery-main').src='{{ $url }}'">
                    <img src="{{ $url }}" alt="">
                </button>
            @endforeach
        </div>
    @endif

    <div class="jbw-gallery-main">
        <img id="jbw-gallery-main" src="{{ $galleryUrls[0] }}" alt="{{ $item->title }}">
    </div>

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

            <div class="jbw-detail-actions">

                @auth('customer')
                    @unless ($webCustomer->is_guest)
                        @if ($item->vendor)
                            <a href="{{ route('web.chat.start', $item->vendor) }}" class="buttonheight jbw-btn jbw-btn--outline">
                               <img src="../../../../assets/frontend/chat-1 1.png"/> Chat
                            </a>
                             <a href="{{ route('web.chat.start', $item->vendor) }}" class="buttonheight jbw-btn jbw-btn--outline">
                                <img src="../../../../assets/frontend/Container.png"/>   Video Call
                            </a>
                        @endif
                    @else
                        @if ($item->vendor)
                            <a href="{{ route('web.register', ['redirect' => route('web.chat.start', $item->vendor)]) }}" class="buttonheight jbw-btn jbw-btn--outline">
                                 <img src="../../../../assets/frontend/chat-1 1.png"/> Chat
                            </a>
                        @endif
                    @endunless
                @else
                    @if ($item->vendor)
                        <a href="{{ route('web.login', ['redirect' => route('web.chat.start', $item->vendor)]) }}" class="buttonheight jbw-btn jbw-btn--outline">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            Chat
                        </a>
                    @endif
                @endauth
           @auth('customer')
                    @if ($webCustomer->is_guest)
                        <a href="{{ route('web.register', ['redirect' => route('web.bookings.overview', $item)]) }}" class="buttonheightjbw-btn jbw-btn--primary jbw-btn--lg">Create account to book</a>
                    @else
                        <a href="{{ route('web.bookings.overview', $item) }}" class="buttonheight jbw-btn jbw-btn--primary jbw-btn--lg">Book now</a>
                    @endif
                @else
                    <a href="{{ route('web.login', ['redirect' => route('web.bookings.overview', $item)]) }}" class="buttonheight jbw-btn jbw-btn--primary jbw-btn--lg">Sign in to book</a>
                @endauth
          <p class="textalignment"> <svg xmlns="http://www.w3.org/2000/svg"
         width="16"
         height="16"
         viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="2"
         stroke-linecap="round"
         stroke-linejoin="round"
         style="vertical-align:middle;margin-right:6px;">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
    </svg> Video calling is limited to 1 minute per session</p>
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


  <div class="reviews-header">
            <div class="rating-summary">
                ★★★★★ <span>4.8 (124 reviews)</span>
            </div>


        <a href="#" class="view-all">VIEW ALL</a>
    </div>
     </div>

    <div class="reviews-grid">

        <div class="review-card">
            <div class="review-top">
                <div class="review-user">
                    <img src="https://i.pravatar.cc/80?img=5" alt="">
                    <div>
                        <h4>Veronika</h4>
                        <div class="stars">★★★★★</div>
                    </div>
                </div>

                <span class="review-time">2 days ago</span>
            </div>

            <p>
                "The quality of the velvet is exceptional. It fits perfectly
                and looked stunning at the evening gala. Highly recommend
                Valentino's collection for special occasions."
            </p>
        </div>

        <div class="review-card">
            <div class="review-top">
                <div class="review-user">
                    <img src="https://i.pravatar.cc/80?img=12" alt="">
                    <div>
                        <h4>Aayush</h4>
                        <div class="stars">★★★★★</div>
                    </div>
                </div>

                <span class="review-time">1 week ago</span>
            </div>

            <p>
                "Booked this for my sister's wedding. The color was even better
                in person—a deep, rich pink that caught the light beautifully.
                Great service!"
            </p>
        </div>

    </div>
</section>
</div>
@endsection

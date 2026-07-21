@extends('admin.layouts.app')

@php
    use App\Support\ProductOptionCatalog;

    $typeSlug = $portfolio->category?->slug ?? '';
    $isFashion = $typeSlug === 'fashion-designer';
    $isRentalDress = $typeSlug === 'rented-dress';
    $isRental = in_array($typeSlug, ['rented-dress', 'rented-jewellery'], true);

    $mediaItems = collect($portfolio->galleryMediaItems());
    $photoCount = $mediaItems->where('type', 'image')->count();
    $videoCount = $mediaItems->where('type', 'video')->count();
    $hero = $mediaItems->first();

    $priceAmount = $portfolio->rentalPriceAmount();
    $priceSuffix = $isFashion ? '' : ' / day';
    $priceLabel = $isFashion ? 'Selling price' : 'Price per day';

    $colorCssMap = ProductOptionCatalog::colorCssMap();
    $resolveColorCss = function (?string $name) use ($colorCssMap): string {
        $key = strtolower(trim((string) $name));

        return $key !== '' ? ($colorCssMap[$key] ?? '#94a3b8') : '#94a3b8';
    };
@endphp

@section('title', $portfolio->title)
@section('page_title')
    <span class="block max-w-full truncate" title="{{ $portfolio->title }}">{{ $portfolio->title }}</span>
@endsection
@section('page_subtitle', 'Product · '.$portfolio->vendor->brand_name)

@section('back_href', route('admin.portfolio.index'))
@section('header_actions')
    @if (auth('admin')->user()->hasPermission('portfolio', 'edit'))
        <x-admin.button variant="secondary" :href="route('admin.portfolio.edit', $portfolio)">Edit product</x-admin.button>
    @endif
    @if (in_array($portfolio->status, ['pending', 'rejected'], true) && auth('admin')->user()->hasPermission('portfolio', 'edit'))
        <form method="POST" action="{{ route('admin.portfolio.approve', $portfolio) }}" class="inline-flex">@csrf
            <x-admin.button variant="success" type="submit">{{ $portfolio->status === 'rejected' ? 'Approve again' : 'Approve' }}</x-admin.button>
        </form>
    @endif
@endsection

@section('content')
    <div class="jb-product-view" data-jb-product-view>
        <div class="jb-product-view-layout">
            <div class="jb-product-view-main">
                <div class="jb-product-view-card jb-product-view-media-card">
                    <div class="jb-product-view-stage">
                        @if ($hero)
                            @if (($hero['type'] ?? '') === 'video')
                                <video
                                    class="jb-product-view-hero"
                                    src="{{ url($hero['url']) }}"
                                    controls
                                    playsinline
                                    preload="metadata"
                                    data-jb-product-hero
                                ></video>
                            @else
                                <img
                                    class="jb-product-view-hero panel-lightbox-trigger"
                                    src="{{ url($hero['url']) }}"
                                    alt="{{ $portfolio->title }}"
                                    data-jb-product-hero
                                >
                            @endif
                        @else
                            <div class="jb-product-view-hero jb-product-view-hero--empty" data-jb-product-hero>
                                <svg class="size-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
                                </svg>
                                <span>No media uploaded</span>
                            </div>
                        @endif
                    </div>

                    @if ($mediaItems->count() > 1)
                        <div class="jb-product-view-thumbs" data-jb-product-thumbs>
                            @foreach ($mediaItems as $index => $thumb)
                                <button
                                    type="button"
                                    class="jb-product-view-thumb{{ $index === 0 ? ' is-active' : '' }}{{ ($thumb['type'] ?? '') === 'video' ? ' jb-product-view-thumb--video' : '' }}"
                                    data-jb-thumb-url="{{ url($thumb['url']) }}"
                                    data-jb-thumb-type="{{ $thumb['type'] ?? 'image' }}"
                                    aria-label="Show {{ ($thumb['type'] ?? '') === 'video' ? 'video' : 'image' }} {{ $index + 1 }}"
                                >
                                    @if (($thumb['type'] ?? '') === 'video')
                                        <video src="{{ url($thumb['url']) }}" muted playsinline preload="metadata"></video>
                                        <span class="jb-product-view-thumb-label">Video</span>
                                    @else
                                        <img src="{{ url($thumb['url']) }}" alt="">
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div class="jb-product-view-media-meta">
                        <span>{{ $photoCount }} photo{{ $photoCount === 1 ? '' : 's' }}</span>
                        <span aria-hidden="true">·</span>
                        <span>{{ $videoCount }} video{{ $videoCount === 1 ? '' : 's' }}</span>
                    </div>
                </div>

                <div class="jb-product-view-card">
                    <div class="jb-product-view-summary">
                        <div class="jb-product-view-badges">
                            @include('admin.components.status-badge', ['status' => $portfolio->status, 'label' => ucfirst((string) $portfolio->status)])
                            @if ($portfolio->category?->name)
                                <span class="jb-product-type-pill">{{ $portfolio->category->name }}</span>
                            @endif
                            <span class="jb-product-type-pill">{{ ucfirst($portfolio->audience ?? 'women') }}</span>
                            @if ($portfolio->is_listing_active === false)
                                <span class="jb-product-type-pill jb-product-type-pill--muted">Listing off</span>
                            @endif
                        </div>

                        <h2 class="jb-product-view-title">{{ $portfolio->title }}</h2>

                        <p class="jb-product-view-price">
                            ₹{{ number_format((float) $priceAmount, 0) }}
                            @if ($priceSuffix !== '')
                                <span>{{ $priceSuffix }}</span>
                            @endif
                        </p>
                        <p class="jb-product-view-price-label">{{ $priceLabel }}</p>

                        <p class="jb-product-view-vendor">
                            Sold by
                            <a href="{{ route('admin.vendors.show', $portfolio->vendor) }}">{{ $portfolio->vendor->brand_name }}</a>
                            @if ($portfolio->vendor->city)
                                <span>· {{ $portfolio->vendor->city }}</span>
                            @endif
                        </p>

                        <div class="jb-product-view-desc">
                            <h3>Description</h3>
                            <p>{{ $portfolio->description ?: 'No description added.' }}</p>
                        </div>

                        @if ($portfolio->rejection_reason)
                            <div class="jb-product-reject-box">
                                <strong>Rejection reason</strong>
                                <p>{{ $portfolio->rejection_reason }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($portfolio->variants->isNotEmpty())
                    <div class="jb-product-view-card">
                        <div class="jb-product-view-card-head">
                            <h3>Variants ({{ $portfolio->variants->count() }})</h3>
                            <p>Size, color, price{{ $isRentalDress ? ', advance & stock' : '' }} for this product.</p>
                        </div>

                        <div class="jb-product-variant-grid">
                            @foreach ($portfolio->variants as $variant)
                                @php
                                    $colorName = trim((string) ($variant->color ?? ''));
                                    $colorCss = $resolveColorCss($colorName);
                                    $colorCode = ProductOptionCatalog::hexForName($colorName);
                                @endphp
                                <article class="jb-product-variant-card">
                                    <div class="jb-product-variant-media">
                                        @if ($variant->imageUrl())
                                            <img src="{{ $variant->imageUrl() }}" alt="" class="panel-lightbox-trigger">
                                        @else
                                            <div class="jb-product-variant-media--empty">No image</div>
                                        @endif
                                    </div>
                                    <div class="jb-product-variant-body">
                                        <div class="jb-product-variant-row">
                                            <span class="jb-product-variant-label">Size</span>
                                            <strong>{{ $variant->size ?: '—' }}</strong>
                                        </div>
                                        <div class="jb-product-variant-row">
                                            <span class="jb-product-variant-label">Color</span>
                                            <strong class="jb-product-variant-color">
                                                @if ($colorName !== '')
                                                    <span class="jb-product-variant-swatch" style="background-color: {{ $colorCss }};"></span>
                                                    <span>{{ $colorName }}</span>
                                                    @if ($colorCode)
                                                        <span class="jb-product-variant-hex">{{ strtoupper($colorCode) }}</span>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </strong>
                                        </div>
                                        <div class="jb-product-variant-row">
                                            <span class="jb-product-variant-label">Price</span>
                                            <strong>₹{{ number_format((float) $variant->price, 0) }}{{ $isFashion ? '' : '/day' }}</strong>
                                        </div>
                                        @if ($isRentalDress)
                                            <div class="jb-product-variant-row">
                                                <span class="jb-product-variant-label">Advance</span>
                                                <strong>
                                                    @if ($variant->advance_amount !== null)
                                                        ₹{{ number_format((float) $variant->advance_amount, 0) }}
                                                    @else
                                                        —
                                                    @endif
                                                </strong>
                                            </div>
                                            <div class="jb-product-variant-row">
                                                <span class="jb-product-variant-label">Qty</span>
                                                <strong>{{ $variant->quantity !== null ? (int) $variant->quantity : '—' }}</strong>
                                            </div>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($portfolio->damageDeductions->isNotEmpty())
                    <div class="jb-product-view-card">
                        <div class="jb-product-view-card-head">
                            <h3>Damage deductions</h3>
                            <p>Rules applied when a rental item is returned damaged.</p>
                        </div>
                        <div class="jb-product-damage-list">
                            @foreach ($portfolio->damageDeductions as $rule)
                                <div class="jb-product-damage-item">
                                    <span>{{ $rule->damage_type }}</span>
                                    <strong>{{ rtrim(rtrim(number_format((float) $rule->percent, 2), '0'), '.') }}%</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <aside class="jb-product-view-aside">
                <div class="jb-product-view-card jb-product-view-card--aside">
                    <h3 class="jb-product-view-aside-title">Product details</h3>
                    <dl class="jb-product-facts">
                        <div class="jb-product-fact">
                            <dt>Vendor</dt>
                            <dd>
                                <a href="{{ route('admin.vendors.show', $portfolio->vendor) }}" class="jb-booking-link">{{ $portfolio->vendor->brand_name }}</a>
                            </dd>
                        </div>
                        <div class="jb-product-fact">
                            <dt>Product type</dt>
                            <dd>{{ $portfolio->category?->name ?? '—' }}</dd>
                        </div>
                        <div class="jb-product-fact">
                            <dt>Category</dt>
                            <dd>{{ $portfolio->subcategory?->parent?->name ?? '—' }}</dd>
                        </div>
                        <div class="jb-product-fact">
                            <dt>Sub-category</dt>
                            <dd>{{ $portfolio->subcategory?->name ?? '—' }}</dd>
                        </div>
                        <div class="jb-product-fact">
                            <dt>Audience</dt>
                            <dd>{{ ucfirst($portfolio->audience ?? 'women') }}</dd>
                        </div>
                        <div class="jb-product-fact">
                            <dt>{{ $priceLabel }}</dt>
                            <dd>₹{{ number_format((float) $priceAmount, 2) }}{{ $priceSuffix }}</dd>
                        </div>
                        @unless ($isRentalDress)
                            <div class="jb-product-fact">
                                <dt>Advance amount</dt>
                                <dd>{{ $portfolio->advance_amount !== null ? '₹'.number_format((float) $portfolio->advance_amount, 2) : '—' }}</dd>
                            </div>
                        @endunless
                        <div class="jb-product-fact">
                            <dt>Status</dt>
                            <dd>@include('admin.components.status-badge', ['status' => $portfolio->status, 'label' => ucfirst((string) $portfolio->status)])</dd>
                        </div>
                        <div class="jb-product-fact">
                            <dt>Listing</dt>
                            <dd>{{ ($portfolio->is_listing_active ?? true) ? 'Active' : 'Inactive' }}</dd>
                        </div>
                        <div class="jb-product-fact">
                            <dt>Media</dt>
                            <dd>{{ $photoCount }} photos · {{ $videoCount }} videos</dd>
                        </div>
                        @if ($portfolio->variants->isNotEmpty())
                            <div class="jb-product-fact">
                                <dt>Variants</dt>
                                <dd>{{ $portfolio->variants->count() }}</dd>
                            </div>
                        @endif
                        <div class="jb-product-fact">
                            <dt>Submitted</dt>
                            <dd>{{ $portfolio->created_at->format('M d, Y · h:i A') }}</dd>
                        </div>
                        @if ($portfolio->reviewed_at)
                            <div class="jb-product-fact">
                                <dt>Reviewed</dt>
                                <dd>{{ $portfolio->reviewed_at->format('M d, Y · h:i A') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                @if (in_array($portfolio->status, ['pending', 'rejected'], true) && auth('admin')->user()->hasPermission('portfolio', 'edit'))
                    <div class="jb-product-view-card jb-product-view-card--aside">
                        <h3 class="jb-product-view-aside-title">Moderation</h3>
                        @if ($portfolio->status === 'rejected')
                            <p class="jb-product-view-moderation-note">This product was rejected. Approve again if the vendor has fixed the issues.</p>
                        @endif
                        <div class="jb-product-view-moderation-actions">
                            <form method="POST" action="{{ route('admin.portfolio.approve', $portfolio) }}">@csrf
                                <x-admin.button variant="primary" type="submit" class="w-full">{{ $portfolio->status === 'rejected' ? 'Approve again' : 'Approve product' }}</x-admin.button>
                            </form>
                            @if ($portfolio->status === 'pending')
                                <form method="POST" action="{{ route('admin.portfolio.reject', $portfolio) }}" class="space-y-3">
                                    @csrf
                                    @include('admin.partials.form-input', ['label' => 'Rejection reason', 'name' => 'rejection_reason', 'type' => 'textarea', 'value' => old('rejection_reason'), 'required' => true, 'full' => true])
                                    <x-admin.button variant="danger" type="submit" class="w-full">Reject product</x-admin.button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const root = document.querySelector('[data-jb-product-view]');
    if (!root) return;

    const productTitle = @json($portfolio->title);

    const setHero = (url, type) => {
        let hero = root.querySelector('[data-jb-product-hero]');
        if (!hero || !url) return;

        if (type === 'video') {
            if (hero.tagName !== 'VIDEO') {
                const video = document.createElement('video');
                video.src = url;
                video.controls = true;
                video.playsInline = true;
                video.preload = 'metadata';
                video.className = 'jb-product-view-hero';
                video.setAttribute('data-jb-product-hero', '');
                hero.replaceWith(video);
            } else {
                hero.src = url;
            }
            return;
        }

        if (hero.tagName === 'IMG') {
            hero.src = url;
            return;
        }

        const img = document.createElement('img');
        img.src = url;
        img.alt = productTitle;
        img.className = 'jb-product-view-hero panel-lightbox-trigger';
        img.setAttribute('data-jb-product-hero', '');
        hero.replaceWith(img);
    };

    root.querySelectorAll('[data-jb-thumb-url]').forEach((button) => {
        button.addEventListener('click', () => {
            const url = button.getAttribute('data-jb-thumb-url');
            const type = button.getAttribute('data-jb-thumb-type') || 'image';
            setHero(url, type);
            root.querySelectorAll('.jb-product-view-thumb.is-active').forEach((el) => el.classList.remove('is-active'));
            button.classList.add('is-active');
        });
    });
})();
</script>
@endpush

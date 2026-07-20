@extends('vendor.layouts.app')

@section('title', 'View '.$typeLabel)

@section('content')
@php
    $sellingPrice = $item->rentalPriceAmount();
    $isApproved = $item->status === 'approved';
    $isActive = $isApproved && (bool) ($item->is_listing_active ?? true);
    $approvalClass = match ($item->status) {
        'approved' => 'done',
        'rejected' => 'failed',
        default => 'pending',
    };
    $isRental = in_array($type, ['rented-dress', 'rented-jewellery'], true);
    $isRentalDress = $type === 'rented-dress';
    $viewTitle = match ($type) {
        'rented-dress' => 'View Dress',
        'rented-jewellery' => 'View Jewelry',
        default => 'View Design',
    };
    $priceLabel = $type === 'fashion-designer' ? 'Selling Price' : 'Price/Day';
    $galleryImages = $item->images->filter(fn ($image) => $image->isImage() && $image->imageUrl());
    $galleryVideos = $item->images->filter(fn ($image) => $image->isVideo() && $image->mediaUrl());
    $variants = $isRentalDress ? $item->availableVariants() : collect();
    $availableSizes = $variants->pluck('size')->filter()->map(fn ($s) => trim((string) $s))->unique()->values();
    $colorCssMap = [
        'black' => '#111111', 'white' => '#ffffff', 'red' => '#e11d48', 'blue' => '#2563eb',
        'navy' => '#1e3a8a', 'navy blue' => '#1e3a8a', 'green' => '#16a34a', 'yellow' => '#eab308',
        'orange' => '#ea580c', 'pink' => '#ec4899', 'purple' => '#9333ea', 'brown' => '#92400e',
        'grey' => '#6b7280', 'gray' => '#6b7280', 'gold' => '#ca8a04', 'silver' => '#a8a29e',
        'maroon' => '#9f1239', 'ivory' => '#fffff0', 'rose gold' => '#b76e79',
    ];
    $resolveColorCss = function (string $name) use ($colorCssMap): string {
        $key = strtolower(trim($name));

        return $colorCssMap[$key] ?? '#94a3b8';
    };
    $availableColors = $isRentalDress
        ? $variants
            ->groupBy(fn ($variant) => trim((string) $variant->color))
            ->filter(fn ($group, $color) => $color !== '')
            ->map(function ($group, $color) use ($resolveColorCss) {
                $withImage = $group->first(fn ($variant) => filled($variant->imageUrl()));

                return [
                    'name' => $color,
                    'css' => $resolveColorCss($color),
                    'image_url' => $withImage?->imageUrl(),
                ];
            })
            ->values()
        : collect();
    $heroImageUrl = $item->displayImageUrl()
        ?: data_get($availableColors->first(fn ($color) => filled($color['image_url'] ?? null)), 'image_url');

    $mediaThumbs = collect();
    $seenMediaPaths = [];

    $pushMediaThumb = function (string $type, ?string $path, ?string $url) use (&$mediaThumbs, &$seenMediaPaths): void {
        if (! $url) {
            return;
        }

        $key = is_string($path) ? ltrim(str_replace('\\', '/', $path), '/') : '';
        if ($key !== '' && (str_starts_with($key, 'http://') || str_starts_with($key, 'https://'))) {
            $key = '';
        }
        if ($key === '') {
            $key = preg_replace('#^.*/storage/#', '', parse_url($url, PHP_URL_PATH) ?? $url) ?: $url;
            $key = ltrim((string) $key, '/');
        }
        if ($key === '' || isset($seenMediaPaths[$key])) {
            return;
        }

        $seenMediaPaths[$key] = true;
        $mediaThumbs->push(['type' => $type, 'url' => $url]);
    };

    foreach ($galleryImages as $image) {
        $pushMediaThumb('image', $image->image_path, $image->imageUrl());
    }

    if ($item->image_url) {
        $pushMediaThumb(
            'image',
            $item->image_url,
            str_starts_with((string) $item->image_url, 'http')
                ? $item->image_url
                : \App\Support\StoresUploadedFiles::url($item->image_url)
        );
    }

    if ($mediaThumbs->isEmpty() && $heroImageUrl) {
        $pushMediaThumb('image', $item->image_url, $heroImageUrl);
    }

    foreach ($galleryVideos as $video) {
        $pushMediaThumb('video', $video->image_path, $video->mediaUrl());
    }
@endphp

<div class="vp-card vp-product-view {{ $isRental ? 'vp-product-view--rental' : '' }}">
    <div class="vp-product-view-head">
        <a href="{{ route('vendor.products.index', ['type' => $type]) }}" class="vp-product-view-back">
            <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ $viewTitle }}
        </a>
        <div class="vp-product-view-actions">
            <a href="{{ route('vendor.products.edit', $item) }}" class="vp-btn vp-btn--view-edit">
                @include('vendor.partials.nav-icon', ['icon' => 'edit'])
                Edit
            </a>
            <form method="POST" action="{{ route('vendor.products.destroy', $item) }}"
                  data-vp-confirm="This product will be permanently deleted."
                  data-vp-confirm-title="Delete product?"
                  data-vp-confirm-label="Delete"
                  data-vp-confirm-variant="error">
                @csrf @method('DELETE')
                <button type="submit" class="vp-btn vp-btn--view-delete">
                    <svg class="vp-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                    Delete
                </button>
            </form>
        </div>
    </div>

    @if ($isRental)
        <div class="vp-product-view-body">
            <div class="vp-product-view-media">
                @if ($heroImageUrl)
                    <img src="{{ url($heroImageUrl) }}" alt="{{ $item->title }}" class="vp-product-view-hero panel-lightbox-trigger" data-vp-product-hero>
                @else
                    <div class="vp-product-view-hero vp-product-view-hero--empty" data-vp-product-hero>No primary image</div>
                @endif

                @if ($mediaThumbs->count() > 1)
                    <div class="vp-product-view-thumbs" data-vp-product-thumbs>
                        @foreach ($mediaThumbs as $index => $thumb)
                            @if (($thumb['type'] ?? '') === 'video')
                                <button
                                    type="button"
                                    class="vp-product-view-thumb vp-product-view-thumb--video{{ $index === 0 ? ' is-active' : '' }}"
                                    data-vp-thumb-url="{{ url($thumb['url']) }}"
                                    data-vp-thumb-type="video"
                                    title="Video"
                                    aria-label="Show video {{ $index + 1 }}"
                                >
                                    <span>Video</span>
                                </button>
                            @else
                                <button
                                    type="button"
                                    class="vp-product-view-thumb{{ $index === 0 ? ' is-active' : '' }}"
                                    data-vp-thumb-url="{{ url($thumb['url']) }}"
                                    data-vp-thumb-type="image"
                                    aria-label="Show image {{ $index + 1 }}"
                                >
                                    <img src="{{ url($thumb['url']) }}" alt="">
                                </button>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="vp-product-view-info">
                <div class="vp-product-view-title-row">
                    <h1 class="vp-product-view-title">{{ $item->title }}</h1>
                    @include('vendor.products.partials.availability-switch', ['item' => $item, 'isApproved' => $isApproved, 'isActive' => $isActive])
                </div>

                @if ($item->audience)
                    <div class="vp-product-view-tags vp-product-view-tags--inline">
                        <span class="vp-product-view-tag">{{ ucfirst($item->audience) }}</span>
                    </div>
                @endif

                <div class="vp-product-view-meta vp-product-view-meta--rental">
                    <div>
                        <div class="vp-product-view-meta-label">{{ $priceLabel }}</div>
                        <div class="vp-product-view-meta-value">₹{{ number_format($sellingPrice, 0) }}/day</div>
                    </div>
                    <div>
                        <div class="vp-product-view-meta-label">Advance Amount</div>
                        <div class="vp-product-view-meta-value">
                            @if ($item->advance_amount !== null)
                                ₹{{ number_format((float) $item->advance_amount, 0) }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="vp-product-view-meta-label">Rating</div>
                        <div class="vp-product-view-meta-value vp-product-view-rating vp-product-view-rating--gold">
                            <svg class="vp-rating-star" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            {{ number_format($item->vendor?->rating ?? 0, 1) }}
                        </div>
                    </div>
                </div>

                @if ($isRentalDress && $availableColors->isNotEmpty())
                    <div class="vp-product-view-options">
                        <div class="vp-product-view-options-label">Available colors</div>
                        <div class="vp-product-view-color-cards" role="list">
                            @foreach ($availableColors as $color)
                                <button
                                    type="button"
                                    class="vp-product-view-color-card{{ filled($color['image_url']) ? '' : ' vp-product-view-color-card--swatch-only' }}"
                                    role="listitem"
                                    title="{{ $color['name'] }}"
                                    aria-label="{{ $color['name'] }}"
                                    @if (filled($color['image_url']))
                                        data-vp-color-image="{{ url($color['image_url']) }}"
                                    @endif
                                >
                                    @if (filled($color['image_url']))
                                        <img src="{{ url($color['image_url']) }}" alt="{{ $color['name'] }}" class="vp-product-view-color-card-img">
                                    @else
                                        <span
                                            class="vp-product-view-swatch{{ in_array(strtolower($color['css']), ['#ffffff', '#fffff0'], true) ? ' vp-product-view-swatch--light' : '' }}"
                                            style="background-color: {{ $color['css'] }};"
                                        ></span>
                                    @endif
                                    <span class="vp-product-view-color-card-name">{{ $color['name'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($isRentalDress && $availableSizes->isNotEmpty())
                    <div class="vp-product-view-options">
                        <div class="vp-product-view-options-label">Available sizes</div>
                        <div class="vp-product-view-sizes" role="list">
                            @foreach ($availableSizes as $sizeName)
                                <span class="vp-product-view-size" role="listitem">{{ $sizeName }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="vp-product-view-desc">
                    <h2>Description</h2>
                    <p>{{ $item->description ?: 'No description added.' }}</p>
                </div>
            </div>
        </div>
        @push('scripts')
            <script>
                (function () {
                    const setHero = (url, type) => {
                        let hero = document.querySelector('[data-vp-product-hero]');
                        if (!hero || !url) return;

                        if (type === 'video') {
                            if (hero.tagName !== 'VIDEO') {
                                const video = document.createElement('video');
                                video.src = url;
                                video.controls = true;
                                video.playsInline = true;
                                video.className = 'vp-product-view-hero';
                                video.setAttribute('data-vp-product-hero', '');
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
                        img.alt = @json($item->title);
                        img.className = 'vp-product-view-hero panel-lightbox-trigger';
                        img.setAttribute('data-vp-product-hero', '');
                        hero.replaceWith(img);
                    };

                    document.querySelectorAll('[data-vp-thumb-url]').forEach((button) => {
                        button.addEventListener('click', () => {
                            const url = button.getAttribute('data-vp-thumb-url');
                            const type = button.getAttribute('data-vp-thumb-type') || 'image';
                            setHero(url, type);
                            document.querySelectorAll('[data-vp-product-thumbs] .vp-product-view-thumb.is-active')
                                .forEach((el) => el.classList.remove('is-active'));
                            button.classList.add('is-active');
                        });
                    });

                    document.querySelectorAll('[data-vp-color-image]').forEach((button) => {
                        button.addEventListener('click', () => {
                            const url = button.getAttribute('data-vp-color-image');
                            setHero(url, 'image');
                            document.querySelectorAll('.vp-product-view-color-card.is-active').forEach((el) => el.classList.remove('is-active'));
                            button.classList.add('is-active');
                        });
                    });
                })();
            </script>
        @endpush
    @else
        <div class="vp-product-view-body">
            <div class="vp-product-view-media">
                @if ($item->displayImageUrl())
                    <img src="{{ url($item->displayImageUrl()) }}" alt="{{ $item->title }}" class="vp-product-view-hero panel-lightbox-trigger" data-vp-product-hero>
                @elseif ($mediaThumbs->isNotEmpty())
                    <img src="{{ url($mediaThumbs->first()['url']) }}" alt="{{ $item->title }}" class="vp-product-view-hero panel-lightbox-trigger" data-vp-product-hero>
                @else
                    <div class="vp-product-view-hero vp-product-view-hero--empty" data-vp-product-hero>No primary image</div>
                @endif
            </div>

            <div class="vp-product-view-info">
                <h1 class="vp-product-view-title">{{ $item->title }}</h1>

                <div class="vp-product-view-tags">
                    @if ($item->audience)
                        <span class="vp-product-view-tag">{{ ucfirst($item->audience) }}</span>
                    @endif
                    @if ($item->category?->name)
                        <span class="vp-product-view-tag vp-product-view-tag--muted">{{ $item->category->name }}</span>
                    @endif
                    @if ($item->subcategory?->name)
                        <span class="vp-product-view-tag vp-product-view-tag--muted">{{ $item->subcategory->name }}</span>
                    @endif
                </div>

                <div class="vp-product-view-meta">
                    <div>
                        <div class="vp-product-view-meta-label">Category</div>
                        <div class="vp-product-view-meta-value vp-product-view-meta-value--sm">{{ $item->subcategory?->parent?->name ?? $item->category?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="vp-product-view-meta-label">Sub-category</div>
                        <div class="vp-product-view-meta-value vp-product-view-meta-value--sm">{{ $item->subcategory?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="vp-product-view-meta-label">{{ $priceLabel }}</div>
                        <div class="vp-product-view-meta-value">₹{{ number_format($sellingPrice, 0) }}</div>
                    </div>
                    <div>
                        <div class="vp-product-view-meta-label">Rating</div>
                        <div class="vp-product-view-meta-value vp-product-view-rating">
                            {{ number_format($item->vendor?->rating ?? 0, 1) }}
                            <svg class="vp-rating-star" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </div>
                    </div>
                </div>

                <div class="vp-product-view-status-row">
                    <span class="vp-badge vp-badge--{{ $approvalClass }}">{{ strtoupper($item->status) }}</span>
                    <span @class(['vp-listing-toggle-label', 'is-active' => $isActive])>
                        {{ $isActive ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="vp-product-view-desc">
                    <h2>Description</h2>
                    <p>{{ $item->description ?: 'No description added.' }}</p>
                </div>
            </div>
        </div>

        <div class="vp-product-view-sections">
            <div class="vp-product-view-section">
                <div class="vp-product-view-section-head">
                    <div>
                        <h2>Gallery images</h2>
                        <p>Click a photo to preview it above.</p>
                    </div>
                </div>
                @if ($mediaThumbs->isNotEmpty())
                    <div class="vp-product-view-gallery" data-vp-design-gallery>
                        @foreach ($mediaThumbs as $index => $thumb)
                            <button
                                type="button"
                                class="vp-product-view-gallery-btn{{ $index === 0 ? ' is-active' : '' }}"
                                data-vp-gallery-url="{{ url($thumb['url']) }}"
                                data-vp-gallery-type="{{ $thumb['type'] ?? 'image' }}"
                                aria-label="Show image {{ $index + 1 }}"
                            >
                                @if (($thumb['type'] ?? '') === 'video')
                                    <span class="vp-product-view-gallery-video">Video</span>
                                @else
                                    <img src="{{ url($thumb['url']) }}" alt="" class="vp-product-view-gallery-img">
                                @endif
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="vp-product-view-empty">No gallery images added.</p>
                @endif
            </div>
        </div>
        @push('scripts')
            <script>
                (function () {
                    const setHero = (url, type) => {
                        let hero = document.querySelector('[data-vp-product-hero]');
                        if (!hero || !url) return;

                        if (type === 'video') {
                            if (hero.tagName !== 'VIDEO') {
                                const video = document.createElement('video');
                                video.src = url;
                                video.controls = true;
                                video.playsInline = true;
                                video.className = 'vp-product-view-hero';
                                video.setAttribute('data-vp-product-hero', '');
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
                        img.alt = @json($item->title);
                        img.className = 'vp-product-view-hero panel-lightbox-trigger';
                        img.setAttribute('data-vp-product-hero', '');
                        hero.replaceWith(img);
                    };

                    document.querySelectorAll('[data-vp-gallery-url]').forEach((button) => {
                        button.addEventListener('click', () => {
                            const url = button.getAttribute('data-vp-gallery-url');
                            const type = button.getAttribute('data-vp-gallery-type') || 'image';
                            setHero(url, type);
                            document.querySelectorAll('[data-vp-design-gallery] .vp-product-view-gallery-btn.is-active')
                                .forEach((el) => el.classList.remove('is-active'));
                            button.classList.add('is-active');
                        });
                    });
                })();
            </script>
        @endpush
    @endif
</div>
@endsection

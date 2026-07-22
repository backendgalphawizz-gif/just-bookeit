@extends('web.layouts.app')

@section('title', ($browseMode ?? 'categories') === 'services' ? 'Services' : 'Categories')

@section('content')
    @php
        $browseMode = $browseMode ?? \App\Support\Api\CatalogFilter::BROWSE_CATEGORIES;
        $isServicesBrowse = $browseMode === \App\Support\Api\CatalogFilter::BROWSE_SERVICES;
        $browseRoute = $isServicesBrowse ? 'web.services.index' : 'web.catalog.index';

        $selectedSizes = collect((array) request('sizes', []))
            ->when(request()->filled('size'), fn ($c) => $c->push(request('size')))
            ->map(fn ($v) => (string) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $selectedColors = collect((array) request('colors', []))
            ->when(request()->filled('color'), fn ($c) => $c->push(request('color')))
            ->map(fn ($v) => (string) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $filterParams = fn (array $extra = []) => array_filter(array_merge([
            'service' => request('service'),
            'category' => request('category'),
            'subcategory' => request('subcategory'),
            'designer' => request('designer'),
            'city' => request('city'),
            'search' => request('search'),
            'min_price' => request('min_price'),
            'max_price' => request('max_price'),
            'min_rating' => request('min_rating'),
            'sizes' => $selectedSizes ?: null,
            'colors' => $selectedColors ?: null,
        ], $extra), fn ($value) => $value !== null && $value !== '' && $value !== []);

        $hasFilters = (bool) (
            request('search') || request('designer') || request('city') || request('service')
            || request('category') || request('subcategory')
            || request('min_price') || request('max_price') || request('min_rating')
            || $selectedSizes || $selectedColors
        );

        $serviceTitle = $appliedFilters['service']['name'] ?? null;
        $categoryTitle = $appliedFilters['category']['name'] ?? null;
        $pageTitle = $serviceTitle
            ?: ($categoryTitle ?: ($isServicesBrowse ? 'Our services' : 'Shop by category'));

        $genderFallbacks = [
            'women' => 'https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?auto=format&fit=crop&w=300&q=80',
            'men' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=300&q=80',
            'kids' => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=300&q=80',
        ];

        $minPrice = (int) (request('min_price') ?: 500);
        $maxPrice = (int) (request('max_price') ?: 50000);
        $minPrice = max(500, min(50000, $minPrice));
        $maxPrice = max(500, min(50000, $maxPrice));
        if ($minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        $filterSizes = $filterSizes ?? \App\Support\ProductOptionCatalog::sizeNames(true);
        $filterColors = $filterColors ?? \App\Support\ProductOptionCatalog::colorApiItems(true);

        $fashionFallbacks = [
            'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=600&q=80',
            'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?w=600&q=80',
            'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=600&q=80',
            'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=600&q=80',
        ];
    @endphp

<div class="jbw-container jbw-page-shell jbw-catalog-page">
    <div class="jbw-catalog-page-head">
        <a href="{{ route('web.home') }}" class="jbw-catalog-back" aria-label="Go to home">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 class="jbw-catalog-page-title">{{ $pageTitle }}</h1>
    </div>

    <div
        class="jbw-catalog-layout"
        x-data="{
            filterOpen: false,
            sizes: {{ Js::from($selectedSizes) }},
            colors: {{ Js::from($selectedColors) }},
            minRating: {{ Js::from(request('min_rating') ? (string) request('min_rating') : '') }},
            minPrice: {{ $minPrice }},
            maxPrice: {{ $maxPrice }},
            priceMinBound: 500,
            priceMaxBound: 50000,
            textTimer: null,
            applyFilters() {
                this.$nextTick(() => {
                    const form = this.$refs.filterForm;
                    if (form) form.requestSubmit();
                });
            },
            toggle(list, value) {
                const i = list.indexOf(value);
                if (i >= 0) list.splice(i, 1);
                else list.push(value);
                this.applyFilters();
            },
            setRating(value) {
                this.minRating = (this.minRating === value ? '' : value);
                this.applyFilters();
            },
            syncMin() {
                if (Number(this.minPrice) > Number(this.maxPrice)) this.minPrice = this.maxPrice;
            },
            syncMax() {
                if (Number(this.maxPrice) < Number(this.minPrice)) this.maxPrice = this.minPrice;
            },
            priceLabel(v) {
                const n = Number(v);
                if (n >= this.priceMaxBound) return '₹50,000+';
                return '₹' + n.toLocaleString('en-IN');
            },
            debounceText() {
                clearTimeout(this.textTimer);
                this.textTimer = setTimeout(() => this.applyFilters(), 500);
            }
        }"
    >
        <button
            type="button"
            class="jbw-filter-toggle"
            x-on:click="filterOpen = !filterOpen"
            :aria-expanded="filterOpen"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
            <span x-text="filterOpen ? 'Hide Filters' : 'Show Filters'">Show Filters</span>
            @if ($hasFilters)
                <span class="jbw-filter-badge">!</span>
            @endif
        </button>

        <aside class="jbw-filters jbw-filters--listing" :class="{ 'is-open': filterOpen }">
            <p class="jbw-filter-title">Filter by</p>

            <form method="GET" action="{{ route($browseRoute) }}" class="jbw-filter-form" x-ref="filterForm">
                @if (request('service'))
                    <input type="hidden" name="service" value="{{ request('service') }}">
                @endif
                @if (request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif

                <div class="jbw-field">
                    <div class="jbw-gender-filter">
                        @foreach ($mainCategories as $cat)
                            @php
                                $key = strtolower((string) ($cat->slug ?: $cat->name));
                                $img = $cat->imageUrl() ?: ($genderFallbacks[$key] ?? $genderFallbacks['women']);
                                $active = (int) request('category') === (int) $cat->id;
                            @endphp
                            <a
                                href="{{ route($browseRoute, $filterParams(['category' => $cat->id, 'subcategory' => null])) }}"
                                @class(['jbw-gender-option', 'is-active' => $active])
                            >
                                <span class="jbw-gender-thumb">
                                    <span class="jbw-gender-thumb-inner">
                                        <img src="{{ $img }}" alt="">
                                    </span>
                                    @if ($active)
                                        <span class="jbw-gender-check" aria-hidden="true">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5"><path d="M20 6L9 17l-5-5"/></svg>
                                        </span>
                                    @endif
                                </span>
                                <span class="jbw-gender-label">{{ strtoupper($cat->name) }}</span>
                            </a>
                        @endforeach
                    </div>
                    @if (request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    @endif
                </div>

                <div class="jbw-field">
                    <label class="jbw-label" for="subcategory">Sub Categories</label>
                    <div class="jbw-select-wrap">
                        <select id="subcategory" name="subcategory" class="jbw-input jbw-select" x-on:change="applyFilters()">
                            <option value="">Select</option>
                            @foreach ($subcategories as $sub)
                                <option value="{{ $sub->id }}" @selected((int) request('subcategory') === (int) $sub->id)>{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="jbw-field">
                    <label class="jbw-label">Price Range</label>
                    <div class="jbw-price-range jbw-price-range--listing">
                        <div class="jbw-price-sliders" aria-hidden="false">
                            <input type="range" min="500" max="50000" step="100" x-model.number="minPrice" x-on:input="syncMin()" x-on:change="applyFilters()" aria-label="Minimum price">
                            <input type="range" min="500" max="50000" step="100" x-model.number="maxPrice" x-on:input="syncMax()" x-on:change="applyFilters()" aria-label="Maximum price">
                        </div>
                        <div class="jbw-price-range-labels">
                            <span x-text="priceLabel(minPrice)">₹{{ number_format($minPrice) }}</span>
                            <span x-text="priceLabel(maxPrice)">₹{{ number_format($maxPrice) }}{{ $maxPrice >= 50000 ? '+' : '' }}</span>
                        </div>
                        <template x-if="Number(minPrice) > 500">
                            <input type="hidden" name="min_price" :value="minPrice">
                        </template>
                        <template x-if="Number(maxPrice) < 50000">
                            <input type="hidden" name="max_price" :value="maxPrice">
                        </template>
                    </div>
                </div>

                <div class="jbw-field">
                    <label class="jbw-label" for="designer">Designer</label>
                    <input id="designer" type="search" name="designer" class="jbw-input" value="{{ request('designer') }}" placeholder="Enter designers..." x-on:input="debounceText()" x-on:keydown.enter.prevent="applyFilters()">
                </div>

                <div class="jbw-field">
                    <label class="jbw-label" for="city">Location</label>
                    <input id="city" type="search" name="city" class="jbw-input" value="{{ request('city') }}" placeholder="Enter location..." x-on:input="debounceText()" x-on:keydown.enter.prevent="applyFilters()">
                </div>

                <div class="jbw-field">
                    <label class="jbw-label">Size</label>
                    <div class="jbw-size-grid">
                        @forelse ($filterSizes as $size)
                            <button
                                type="button"
                                class="jbw-size-chip"
                                x-on:click="toggle(sizes, @js($size))"
                                :class="{ 'is-active': sizes.includes(@js($size)) }"
                            >{{ $size }}</button>
                        @empty
                            <p class="jbw-filter-empty">No sizes available.</p>
                        @endforelse
                    </div>
                    <template x-for="size in sizes" :key="'size-'+size">
                        <input type="hidden" name="sizes[]" :value="size">
                    </template>
                </div>

                <div class="jbw-field">
                    <label class="jbw-label">Color</label>
                    <div class="jbw-color-row">
                        @forelse ($filterColors as $color)
                            @php
                                $colorName = $color['name'] ?? '';
                                $hex = $color['hex_code'] ?? '#ccc';
                                $isLight = in_array(strtoupper($hex), ['#FFFFFF', '#FFFFF0', '#F5F5DC', '#FFF', '#D4B896', '#FFFFF0'], true)
                                    || (strlen($hex) === 7 && hexdec(substr($hex, 1)) > 0xE0E0E0);
                            @endphp
                            <button
                                type="button"
                                class="jbw-color-swatch{{ $isLight ? ' is-light' : '' }}"
                                style="--swatch: {{ $hex }}"
                                title="{{ $colorName }}"
                                aria-label="{{ $colorName }}"
                                x-on:click="toggle(colors, @js($colorName))"
                                :class="{ 'is-active': colors.includes(@js($colorName)) }"
                            ></button>
                        @empty
                            <p class="jbw-filter-empty">No colors available.</p>
                        @endforelse
                    </div>
                    <template x-for="color in colors" :key="color">
                        <input type="hidden" name="colors[]" :value="color">
                    </template>
                </div>

                <div class="jbw-field">
                    <label class="jbw-label">Rating</label>
                    <div class="jbw-rating-grid">
                        @foreach ([5, 4, 3, 2, 1] as $rating)
                            <button
                                type="button"
                                class="jbw-rating-chip"
                                x-on:click="setRating('{{ $rating }}')"
                                :class="{ 'is-active': minRating === '{{ $rating }}' }"
                            >{{ $rating }}</button>
                        @endforeach
                    </div>
                    <template x-if="minRating">
                        <input type="hidden" name="min_rating" :value="minRating">
                    </template>
                </div>

                <a href="{{ route($browseRoute, array_filter(['service' => request('service')])) }}" class="jbw-filter-clear">Clear filters</a>
            </form>
        </aside>

        <div class="jbw-catalog-results">
            @if ($isServicesBrowse && ! request('category'))
                <div class="jbw-service-strip">
                    <a
                        href="{{ route($browseRoute, $filterParams(['service' => null])) }}"
                        @class(['jbw-service-chip', 'is-active' => ! request('service')])
                    >All</a>
                    @foreach ($serviceCategories as $service)
                        <a
                            href="{{ route($browseRoute, $filterParams(['service' => $service->id])) }}"
                            @class(['jbw-service-chip', 'is-active' => (int) request('service') === (int) $service->id])
                        >{{ $service->name }}</a>
                    @endforeach
                </div>
            @endif

            <div class="jbw-product-grid jbw-product-grid--listing">
                @forelse ($items as $item)
                    @php
                        $fallback = $fashionFallbacks[$item->id % count($fashionFallbacks)];
                        $rating = round((float) ($item->vendor?->rating ?? 0), 1);
                        $variantColors = $item->variants
                            ? $item->variants->pluck('color')->filter()->unique()->take(4)->values()
                            : collect();
                        $unavailable = ! $item->isCatalogAvailable()
                            || ($item->variants && $item->variants->isNotEmpty() && $item->variants->every(fn ($v) => (int) ($v->quantity ?? 0) <= 0));
                    @endphp
                    <a href="{{ route('web.catalog.show', $item) }}" @class([
                        'jbw-product-card',
                        'jbw-product-card--listing',
                        'is-unavailable' => $unavailable,
                        'is-rental' => $item->requiresRentalPeriod(),
                    ])>
                        <div class="jbw-product-card-img">
                            <img
                                src="{{ $item->displayImageUrl() ?: $fallback }}"
                                alt="{{ $item->title }}"
                                loading="lazy"
                            >
                            @if ($unavailable)
                                <span class="jbw-product-unavailable">Unavailable</span>
                            @endif
                        </div>
                        <div class="jbw-product-card-body">
                            <div class="brand-rating-row">
                                <p class="jbw-product-brand textlimit">{{ $item->vendor?->brand_name ?? 'Designer' }}</p>
                                <div class="jbw-product-rating">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    <span>{{ number_format($rating, 1) }}</span>
                                </div>
                            </div>
                            <p class="jbw-product-title textlimit">{{ $item->title }}</p>
                            <p class="jbw-product-price">{{ $item->rentalPriceLabel() }}</p>
                            @if ($variantColors->isNotEmpty())
                                <div class="jbw-product-colors">
                                    @foreach ($variantColors as $colorName)
                                        @php $hex = \App\Support\ProductOptionCatalog::hexForName($colorName) ?: '#c4c4c4'; @endphp
                                        <span class="jbw-product-color-dot" style="background: {{ $hex }}" title="{{ $colorName }}"></span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="jbw-catalog-empty">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        <p>{{ $isServicesBrowse ? 'No services found for your filters.' : 'No outfits found.' }}</p>
                        <a href="{{ route($browseRoute) }}" class="jbw-btn jbw-btn--outline jbw-btn--sm">Clear filters</a>
                    </div>
                @endforelse
            </div>

            @if ($items->hasPages())
                <div class="jbw-catalog-pagination">{{ $items->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

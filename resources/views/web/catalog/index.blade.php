@extends('web.layouts.app')

@section('title', 'Catalog')

@section('content')
    @php
        $filterParams = fn (array $extra = []) => array_filter(array_merge([
            'category' => request('category'),
            'subcategory' => request('subcategory'),
            'service' => request('service'),
            'designer' => request('designer'),
            'city' => request('city'),
            'search' => request('search'),
        ], $extra), fn ($value) => $value !== null && $value !== '');
        $hasFilters = request('search') || request('designer') || request('city') || request('category') || request('subcategory') || request('service');
        $activeCategoryId = request('category');
    @endphp

<div class="jbw-container">
    <div class="jbw-page-head" >
        <!-- <span class="jbw-eyebrow">Catalog</span> -->
        <h1 class="jbw-page-title">Designer Collection</h1>
        <!-- <p class="jbw-page-subtitle">Browse premium outfits for every occasion</p> -->
    </div>

    <div class="jbw-catalog-layout" x-data="{ filterOpen: false }">

        <button
            type="button"
            class="jbw-filter-toggle"
            @click="filterOpen = !filterOpen"
            :aria-expanded="filterOpen"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
            <span x-text="filterOpen ? 'Hide Filters' : 'Show Filters'">Show Filters</span>
            @if($hasFilters)
                <span class="jbw-filter-badge">!</span>
            @endif
        </button>

        <aside class="jbw-filters" :class="{ 'is-open': filterOpen }">
            <p class="jbw-filter-title">Filter By</p>

            <form method="GET" action="{{ route('web.catalog.index') }}">
                @if (request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if (request('subcategory'))
                    <input type="hidden" name="subcategory" value="{{ request('subcategory') }}">
                @endif

                <div class="jbw-field" style="margin-top:1rem">
                    <label class="jbw-label">Shop for</label>
                    <div class="jbw-subcategory-strip" style="gap:0rem; margin-bottom:0rem; padding-bottom:0rem;">
                        @foreach ($mainCategories as $cat)
                            <a
                                href="{{ route('web.catalog.index', $filterParams(['category' => $cat->id, 'subcategory' => null])) }}"
                                @class(['jbw-subcategory-chip', 'is-active' => (int) request('category') === $cat->id])
                                style="border:none !important; gap:0rem; min-width:4.5rem"
                            >
                                @if ($cat->imageUrl())
                                    <img src="{{ $cat->imageUrl() }}" alt="" class="jbw-subcategory-chip-img">
                                @endif
                                <span class="jbw-subcategory-chip-label">{{ $cat->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="jbw-field" style="margin-top:1rem">
                    <label class="jbw-label" for="service">Service type</label>
                    <select id="service" name="service" class="jbw-select">
                        <option value="">All services</option>
                        @foreach ($serviceCategories as $service)
                            <option value="{{ $service->id }}" @selected(request('service') == $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="jbw-field" style="margin-top:1rem">
                    <label class="jbw-label" for="designer">Designer</label>
                    <input id="designer" type="search" name="designer" class="jbw-input borderradius" value="{{ request('designer', request('search')) }}" placeholder="Designer name">
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="city">City</label>
                    <input id="city" type="search" name="city" class="jbw-input borderradius" value="{{ request('city') }}" placeholder="e.g. Mumbai">
                </div>

                <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--block" style="margin-top:1.25rem;border-radius:10px">Apply filters</button>
                @if($hasFilters)
                    <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--ghost jbw-btn--block" style="margin-top:0.5rem;border-radius:10px">Clear filters</a>
                @endif
            </form>
        </aside>

        <div class="jbw-catalog-results">
            @if ($subcategories->isNotEmpty() && request('category'))
                <div class="jbw-subcategory-strip">
                    <a
                        href="{{ route('web.catalog.index', $filterParams(['subcategory' => null])) }}"
                        @class(['jbw-subcategory-chip', 'is-active' => ! request('subcategory')])
                    >
                        <span class="jbw-subcategory-chip-label">All</span>
                    </a>
                    @foreach ($subcategories as $sub)
                        <a
                            href="{{ route('web.catalog.index', $filterParams(['subcategory' => $sub->id])) }}"
                            @class(['jbw-subcategory-chip', 'is-active' => request('subcategory') == $sub->id])
                        >
                            @if ($sub->imageUrl())
                                <img src="{{ $sub->imageUrl() }}" alt="" class="jbw-subcategory-chip-img">
                            @endif
                            <span class="jbw-subcategory-chip-label">{{ $sub->name }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($hasFilters)
                <p class="jbw-catalog-count" style="margin:0 0 1rem;font-size:0.875rem;color:var(--c-muted)">
                    {{ $items->total() }} result{{ $items->total() === 1 ? '' : 's' }}
                </p>
            @endif

            @php
                $fashionFallbacks = [
                    'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=600&q=80',
                    'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?w=600&q=80',
                    'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=600&q=80',
                    'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=600&q=80',
                ];
            @endphp
            <div class="jbw-product-grid">
                @forelse ($items as $item)
                    @php $fallback = $fashionFallbacks[$item->id % count($fashionFallbacks)]; @endphp
                    <a href="{{ route('web.catalog.show', $item) }}" class="jbw-product-card">
                        <div class="jbw-product-card-img">
                            <img
                                src="{{ $item->displayImageUrl() ?: $fallback }}"
                                alt="{{ $item->title }}"
                                loading="lazy"
                            >
                        </div>
                        <div class="jbw-product-card-body">
                            <div class="brand-rating-row">
    <p class="jbw-product-brand textlimit">
        {{ $item->vendor?->brand_name ?? 'Designer' }}
    </p>

    <div class="rating-wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#e95433">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
        <span>
            {{ number_format($item->reviews_avg_rating ?? 0, 1) }}
        </span>
    </div>
</div>
                            <!-- <p class="jbw-product-brand textlimit">{{ $item->vendor?->brand_name ?? 'Designer' }}</p> -->
                            <p class="jbw-product-title textlimit">{{ $item->title }}</p>
                            @if ($item->subcategory)
                                <p class="jbw-product-meta textlimit namespace">{{ $item->subcategory->name }}</p>
                            @endif
                            <p class="jbw-product-price ">{{ $item->rentalPriceLabel() }}</p>
                        </div>
                    </a>
                @empty
                    <div class="jbw-catalog-empty">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        <p>No outfits found.</p>
                        <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--outline jbw-btn--sm">Clear filters</a>
                    </div>
                @endforelse
            </div>

            @if ($items->hasPages())
                <div style="margin-top:2rem">{{ $items->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

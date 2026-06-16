@extends('web.layouts.app')

@section('title', 'Catalog')

@section('content')
@php
    $fashionFallbacks = [
        'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=600&q=80',
        'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?w=600&q=80',
        'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=600&q=80',
        'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=600&q=80',
        'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=600&q=80',
        'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&q=80',
        'https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=600&q=80',
        'https://images.unsplash.com/photo-1617627143750-d86bc21e42bb?w=600&q=80',
    ];
    $hasFilters = request('search') || request('category') || request('subcategory') || request('service');
@endphp

<div class="jbw-container">
    <div class="jbw-page-head">
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
            <p class="jbw-filter-title">Filter</p>
            <form method="GET" action="{{ route('web.catalog.index') }}">
                <div class="jbw-field">
                    <label class="jbw-label" for="search">Search</label>
                    <input id="search" type="search" name="search" class="jbw-input" value="{{ request('search') }}" placeholder="Gown, lehenga...">
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
                    <label class="jbw-label" for="category">Category</label>
                    <select id="category" name="category" class="jbw-select" onchange="this.form.submit()">
                        <option value="">All categories</option>
                        @foreach ($mainCategories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="jbw-field" style="margin-top:1rem">
                    <label class="jbw-label" for="subcategory">Sub-category</label>
                    <select id="subcategory" name="subcategory" class="jbw-select" @disabled(! request('category'))>
                        <option value="">All sub-categories</option>
                        @foreach ($subcategories as $sub)
                            <option value="{{ $sub->id }}" @selected(request('subcategory') == $sub->id)>{{ $sub->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--block" style="margin-top:1.25rem;border-radius:10px">Apply filters</button>
                @if($hasFilters)
                    <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--ghost jbw-btn--block" style="margin-top:0.5rem;border-radius:10px">Clear filters</a>
                @endif
            </form>
        </aside>

        <div class="jbw-catalog-results">
            @if($hasFilters)
                <p class="jbw-catalog-count">
                    {{ $items->total() }} result{{ $items->total() != 1 ? 's' : '' }}
                    @if(request('search')) for "<strong>{{ request('search') }}</strong>"@endif
                </p>
            @endif

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
                                <p class="jbw-product-meta textlimit">{{ $item->subcategory->name }}</p>
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

@extends('web.layouts.app')

@section('title', 'Portfolio · '.$vendor->brand_name)

@section('content')
<div class="jbw-container jbw-page-shell jbw-portfolio-page">
    <div class="jbw-catalog-page-head jbw-detail-page-head">
        <a href="{{ route('web.vendors.show', $vendor) }}" class="jbw-catalog-back" aria-label="Go back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 class="jbw-catalog-page-title">Portfolio</h1>
    </div>

    <div class="jbw-dp-image-gallery jbw-portfolio-gallery">
        @forelse ($images as $image)
            <a href="{{ $image['href'] }}" class="jbw-dp-image-tile">
                <img src="{{ $image['url'] }}" alt="{{ $image['title'] }}" loading="lazy">
            </a>
        @empty
            <div class="jbw-card" style="grid-column:1/-1">
                <p style="color:var(--c-muted);text-align:center;margin:0">No portfolio images found for this selection.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

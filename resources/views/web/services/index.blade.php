@extends('web.layouts.app')

@section('title', 'Our Services')

@section('content')
@php
    $serviceFallbacks = [
        'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=900&q=85&fit=crop',
        'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=900&q=85&fit=crop',
        'https://images.unsplash.com/photo-1617032210317-3b0855f047a4?w=900&q=85&fit=crop',
    ];
@endphp

<div class="jbw-container jbw-page-shell jbw-our-services">
    <div class="jbw-catalog-page-head jbw-detail-page-head">
        <a href="{{ route('web.home') }}" class="jbw-catalog-back" aria-label="Go back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 class="jbw-catalog-page-title">Our Services</h1>
    </div>

    <div class="jbw-our-services-grid">
        @forelse ($serviceCategories as $index => $service)
            @php
                $label = $service->name;
                if (! str_contains(strtolower($label), 'booking')) {
                    $label .= ' Booking';
                }
            @endphp
            <button
                type="button"
                class="jbw-our-service-card"
                onclick="openServiceBrowse({{ (int) $service->id }})"
            >
                <div class="jbw-our-service-media">
                    <img
                        src="{{ $service->imageUrl() ?: $serviceFallbacks[$index % count($serviceFallbacks)] }}"
                        alt="{{ $label }}"
                        loading="lazy"
                    >
                </div>
                <p class="jbw-our-service-label">{{ $label }}</p>
            </button>
        @empty
            @foreach (['Fashion Designer Booking', 'Rented Dress Booking', 'Rented Jewellery Booking'] as $i => $label)
                <button type="button" class="jbw-our-service-card" onclick="openServiceBrowse(null)">
                    <div class="jbw-our-service-media">
                        <img src="{{ $serviceFallbacks[$i] }}" alt="{{ $label }}" loading="lazy">
                    </div>
                    <p class="jbw-our-service-label">{{ $label }}</p>
                </button>
            @endforeach
        @endforelse
    </div>
</div>
@endsection

@extends('web.layouts.app')

@section('title', 'Booking Overview')

@section('content')
@php
    $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80';
@endphp

<div class="jbw-container">
    <nav class="jbw-breadcrumb">
        <a href="{{ route('web.catalog.show', $item) }}" class="jbw-breadcrumb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            Back to item
        </a>
    </nav>

    <div class="jbw-page-head" style="padding-top:1rem">
        <h1 class="jbw-page-title">Booking Overview</h1>
        <p class="jbw-page-subtitle">Review your selection before checkout</p>
    </div>

    <div class="jbw-booking-layout">
        {{-- Main --}}
        <div class="jbw-booking-main">

            {{-- Item card --}}
            <div class="jbw-overview-card">
                <p class="jbw-overview-label">Your Selection</p>
                <div class="jbw-overview-product">
                    <img
                        src="{{ $item->displayImageUrl() ?: $fallbackImg }}"
                        alt="{{ $item->title }}"
                        class="jbw-overview-img"
                    >
                    <div class="jbw-overview-product-info">
                        <p class="jbw-overview-brand">{{ $item->vendor?->brand_name ?? 'Designer' }}</p>
                        <h2 class="jbw-overview-title">{{ $item->title }}</h2>
                        <p class="jbw-overview-cat">{{ $item->category?->name ?? 'Rental dress' }}</p>
                        <p class="jbw-overview-price">{{ $item->rentalPriceLabel() }}</p>
                    </div>
                </div>
            </div>

            @if ($item->vendor)
            <div class="jbw-overview-card">
                <p class="jbw-overview-label">Designer</p>
                <div style="display:flex;align-items:center;gap:0.875rem">
                    @if($item->vendor->profileImageUrl() || $item->vendor->shopLogoUrl())
                        <img src="{{ $item->vendor->profileImageUrl() ?: $item->vendor->shopLogoUrl() }}" alt="" style="width:3rem;height:3rem;border-radius:999px;object-fit:cover;border:2px solid var(--c-border)">
                    @else
                        <span style="width:3rem;height:3rem;border-radius:999px;background:#fce7df;display:grid;place-items:center;font-weight:800;color:var(--c-primary);font-size:1.125rem;flex-shrink:0">{{ strtoupper(substr($item->vendor->brand_name, 0, 1)) }}</span>
                    @endif
                    <div>
                        <p style="margin:0;font-weight:700;font-size:0.9375rem">{{ $item->vendor->brand_name }}</p>
                        <p style="margin:0.125rem 0 0;font-size:0.8125rem;color:var(--c-muted)">★ {{ number_format($item->vendor->rating ?? 4.5, 1) }}{{ $item->vendor->city ? ' · '.$item->vendor->city : '' }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="jbw-overview-card">
                <p class="jbw-overview-label">Rental Period</p>
                <div class="jbw-overview-dates-placeholder">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <p style="margin:0;font-size:0.875rem;color:var(--c-muted)">Date selection available at checkout</p>
                </div>
            </div>

            <div class="jbw-overview-card">
                <p class="jbw-overview-label">Additional Notes</p>
                <textarea class="jbw-textarea" placeholder="Any specific requirements, fitting instructions, or customisation notes..." style="min-height:6rem"></textarea>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="jbw-booking-sidebar">
            <div class="jbw-overview-card jbw-overview-card--sticky">
                <p class="jbw-overview-label">Billing Address</p>
                <a href="{{ route('web.profile.addresses') }}" class="jbw-overview-add-address">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                    Add delivery address
                </a>
            </div>

            <div class="jbw-overview-card jbw-overview-card--accent">
                <p class="jbw-overview-label">Payment Summary</p>
                <div class="jbw-payment-lines" style="margin-bottom:0">
                    <div>
                        <span>Rental price</span>
                        <span>{{ $item->rentalPriceLabel() }}</span>
                    </div>
                    <div>
                        <span>Delivery</span>
                        <span style="color:var(--c-muted)">₹150</span>
                    </div>
                    <div>
                        <span>GST &amp; tax</span>
                        <span style="color:var(--c-muted)">Calculated at checkout</span>
                    </div>
                </div>
                <div class="jbw-payment-total">
                    <span style="font-weight:700">Estimated total</span>
                    <strong>{{ $item->rentalPriceLabel() }}</strong>
                </div>
                <button type="button" class="jbw-btn jbw-btn--primary jbw-btn--block" style="margin-top:1.25rem;border-radius:10px;padding:0.9375rem" disabled>
                    Checkout — Coming soon
                </button>
                <p style="text-align:center;font-size:0.75rem;color:var(--c-muted);margin:0.75rem 0 0">
                    🔒 Secure booking — no payment now
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

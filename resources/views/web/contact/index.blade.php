@extends('web.layouts.app')

@section('title', 'Contact Us')

@section('content')
<div class="jbw-container">
    <div class="jbw-page-head">
        <span class="jbw-eyebrow">Support</span>
        <h1 class="jbw-page-title">Contact Us</h1>
        <p class="jbw-page-subtitle">We are here to help with bookings, rentals, and account questions.</p>
    </div>

    <div class="jbw-grid-3" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));margin-bottom:0">
        @if ($supportEmail)
            <div class="jbw-card">
                <p class="jbw-label">Email</p>
                <p style="margin:0;font-weight:700"><a href="mailto:{{ $supportEmail }}" style="color:var(--c-primary);text-decoration:none">{{ $supportEmail }}</a></p>
            </div>
        @endif
        @if ($supportPhone)
            <div class="jbw-card">
                <p class="jbw-label">Phone</p>
                <p style="margin:0;font-weight:700"><a href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}" style="color:var(--c-primary);text-decoration:none">{{ $supportPhone }}</a></p>
            </div>
        @endif
        @if ($contactAddress)
            <div class="jbw-card">
                <p class="jbw-label">Address</p>
                <p style="margin:0;line-height:1.6;color:var(--c-muted)">{{ $contactAddress }}</p>
            </div>
        @endif
    </div>

    <div class="jbw-card" style="margin-top:1.5rem;text-align:center">
        <p style="margin:0 0 1rem;color:var(--c-muted)">Need to book an outfit or track an order?</p>
        <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap">
            <a href="{{ route('web.catalog.index') }}" class="jbw-btn jbw-btn--primary">Browse catalog</a>
            @auth('customer')
                @if ($webCustomer->is_guest)
                    <a href="{{ route('web.register', ['redirect' => url()->current()]) }}" class="jbw-btn jbw-btn--outline">Create account</a>
                @else
                    <a href="{{ route('web.bookings.index') }}" class="jbw-btn jbw-btn--outline">My bookings</a>
                @endif
            @else
                <a href="{{ route('web.login') }}" class="jbw-btn jbw-btn--outline">Sign in</a>
            @endauth
            <a href="{{ route('web.faq') }}" class="jbw-btn jbw-btn--outline">FAQs</a>
        </div>
    </div>
</div>
@endsection

@extends('web.layouts.app')

@section('title', 'Contact Us')

@section('content')
<div class="jbw-container">
    <!-- <div class="jbw-page-head">
        <span class="jbw-eyebrow">Support</span>
        <h1 class="jbw-page-title">Contact Us</h1>
        <p class="jbw-page-subtitle">We are here to help with bookings, rentals, and account questions.</p>
    </div> -->

    <!-- <div class="jbw-grid-3" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));margin-bottom:0">
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
    </div> -->

    <!-- <div class="jbw-card" style="margin-top:1.5rem;text-align:center">
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
    </div> -->
    <div class="contact-form-card jbw-card">
    <h2>Get in Touch</h2>

    <form>
        <div class="contact-form-grid">

            <div class="form-group">
                <label>Select Type</label>
                <select class="contact-select">
                    <option>Inquiry Type</option>
                    <option>Booking</option>
                    <option>Order Tracking</option>
                    <option>Support</option>
                </select>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email"
                       class="contact-input"
                       placeholder="julianne@example.com">
            </div>

            <div class="form-group full-width">
                <label>Subject</label>
                <input type="text"
                       class="contact-input"
                       placeholder="What is this regarding?">
            </div>

            <div class="form-group full-width">
                <label>Description</label>
                <textarea class="contact-textarea"
                          placeholder="Tell us more about your request..."></textarea>
            </div>

        </div>

        <div class="contact-submit-wrap">
            <button type="submit" class="contact-submit">
                SEND MESSAGE →
            </button>
        </div>
    </form>
</div>
</div>
@endsection

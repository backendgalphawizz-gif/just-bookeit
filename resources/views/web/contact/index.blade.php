@extends('web.layouts.app')

@section('title', 'Contact Us')

<style>
    /* 1. Responsive Grid Wrapper */
    .jbw-grid-3 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 50px 24px;
        /* Room for the absolute positioned circular labels */
        margin-top: 40px;
        /* Offset spacing so icons don't bleed out of bounds */
        margin-bottom: 25px;
        width: 100%;
        box-sizing: border-box;
    }

    /* 2. Base Contact Cards */
    .jbw-contact-card {
        background-color: #f0f4f8;
        /* Soft light-grey blue background matching layout */
        border-radius: 4px;
        padding: 45px 24px 28px 24px;
        /* Top padded to offset the absolute badge positioning */
        position: relative;
        text-align: left;
        /* Content remains left-aligned inside cards */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 160px;
        box-sizing: border-box;
        border: 1px solid #e2e8f0;
    }

    /* 3. Floating Circle Icon Badge */
    .jbw-label-icon {
        position: absolute;
        top: -30px;
        /* Pulls half the circle above the card threshold */
        left: 50%;
        transform: translateX(-50%);
        background-color: var(--c-primary);
        /* Dark blue background */
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .jbw-label-icon svg {
        flex-shrink: 0;
        stroke: #ffffff;
        /* Forces white outline inside badges */
    }

    /* Invisible label helper for accessibility, hides text from viewport */
    .jbw-label-icon span {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }

    /* 4. Card Titles */
    .jbw-contact-title {
        font-family: system-ui, -apple-system, sans-serif;
        font-size: 1.35rem;
        font-weight: 500;
        color: #1a202c;
        margin: 10px 0 12px 0;
    }

    /* Description Mock Text (Matches layout subtexts) */
    .jbw-contact-desc {
        font-family: system-ui, -apple-system, sans-serif;
        font-size: 0.9rem;
        color: #4a5568;
        line-height: 1.5;
        margin: 0 0 20px 0;
    }

    /* 5. Values & Actions */
    .jbw-contact-value {
        margin: auto 0 0 0;
        /* Auto-pushes action anchor downward if heights differ */
    }

    /* Interactive Link Elements */
    .jbw-contact-value .jbw-link {
        color: #1a73e8;
        text-decoration: none;
        font-family: system-ui, -apple-system, sans-serif;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .jbw-contact-value .jbw-link:hover {
        text-decoration: underline;
    }

    /* Button CTA Layouts (Like 'Chat Live' / 'Ask a Question') */
    .jbw-btn {
        display: inline-block;
        background-color: var(--c-primary);
        color: #ffffff !important;
        text-decoration: none;
        font-family: system-ui, -apple-system, sans-serif;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 0.50rem 1.5rem !important;
        border-radius: 4px;
        transition: background-color 0.15s ease-in-out;
    }

    .jbw-btn:hover {
        background-color: #fce7df;
        color: var(--c-primary) !important;
    }

    /* 6. Breakpoint adjustments */
    @media (max-width: 768px) {
        .jbw-grid-3 {
            grid-template-columns: 1fr !important;
            gap: 55px 0;
            /* Broadens grid spacing to manage vertical stack gracefully */
        }
        .jbw-grid-3 {
           gap: 2.25rem !important;
        }
    }
</style>
@section('content')
<div class="jbw-container">
    <div class="jbw-page-head">
        <!-- <span class="jbw-eyebrow">Support</span> -->
        <h1 class="jbw-page-title">Contact Us</h1>
        <p class="jbw-page-subtitle">We are here to help with bookings, rentals, and account questions.</p>
    </div>

    <div class="jbw-grid-3">

        @if ($supportPhone)
        <div class="jbw-card jbw-contact-card">
            <p class="jbw-label jbw-label-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="2.5">
                    <path d="M22 16.92V20a2 2 0 0 1-2.18 2A19.86 19.86 0 0 1 3 5.18 2 2 0 0 1 5 3h3.09a2 2 0 0 1 2 1.72l.38 2.67a2 2 0 0 1-.57 1.72L8.09 10.91a16 16 0 0 0 5 5l1.8-1.81a2 2 0 0 1 1.72-.57l2.67.38A2 2 0 0 1 22 16.92z" />
                </svg>
                <span>Phone Number</span>
            </p>

            <div>
                <h3 class="jbw-contact-title">Call Our Support Team</h3>
                <p class="jbw-contact-desc">
                    Speak directly with our support team for immediate assistance and answers to your questions.
                </p>
            </div>

            <div class="jbw-contact-value">
                <a class="jbw-btn" href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}">
                    Call Now
                </a>
            </div>
        </div>
        @endif

        <!-- <div class="jbw-card jbw-contact-card">
        <p class="jbw-label jbw-label-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                <path d="M8 10h.01M12 10h.01M16 10h.01" stroke-linecap="round" stroke-width="3" />
            </svg>
            <span>Chat Live</span>
        </p>

        <div>
            <h3 class="jbw-contact-title">Chat Live</h3>
            <p class="jbw-contact-desc">We're available Sun 7:00pm EST - Friday 7:00pm EST.</p>
        </div>

        <div class="jbw-contact-value">
            <a href="#" class="jbw-btn">Chat Now</a>
        </div>
    </div> -->

        @if ($supportEmail)
        <div class="jbw-card jbw-contact-card">
            <p class="jbw-label jbw-label-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="2.5">
                    <path d="M4 6H20C21.1 6 22 6.9 22 8V16C22 17.1 21.1 18 20 18H4C2.9 18 2 17.1 2 16V8C2 6.9 2.9 6 4 6Z" />
                    <path d="M22 8L12 14L2 8" />
                </svg>
                <span>Email</span>
            </p>

            <div>
                <h3 class="jbw-contact-title">Ask a Question</h3>
                <p class="jbw-contact-desc">Fill out our form and we'll get back to you in 24 hours.</p>
            </div>

            <div class="jbw-contact-value">
                <a href="mailto:{{ $supportEmail }}" class="jbw-btn">Get Started</a>
            </div>
        </div>
        @endif

        @if ($contactAddress)
        <div class="jbw-card jbw-contact-card">
            <p class="jbw-label jbw-label-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="2.5">
                    <path d="M12 21s-7-5.686-7-11a7 7 0 1 1 14 0c0 5.314-7 11-7 11z" />
                    <circle cx="12" cy="10" r="2.5" />
                </svg>
                <span>Address</span>
            </p>

            <div>
                <h3 class="jbw-contact-title">Office Address</h3>
                <p class="jbw-contact-desc">
                    Visit our office for in-person assistance, consultations, and support during business hours.
                </p>
            </div>

            <p class="jbw-contact-value jbw-link" style="color:#4a5568;">
                {{ $contactAddress }}
            </p>
        </div>
        @endif

    </div>

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
        <h1 class="jbw-page-title">Get in Touch</h1>

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

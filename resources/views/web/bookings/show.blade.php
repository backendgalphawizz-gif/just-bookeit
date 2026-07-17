@extends('web.layouts.app')

@section('title', 'Booking '.$order->order_number)

@section('content')
@php
    $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=400&q=80';
    $statusClass = match ($order->status) {
        'new', 'pending_acceptance' => 'new',
        'in_progress', 'accepted' => 'in_progress',
        'delivered' => 'delivered',
        'cancelled', 'refunded' => 'cancelled',
        default => 'default',
    };
    $paymentClass = match ($order->payment_status) {
        'paid', 'success' => 'paid',
        'failed' => 'failed',
        default => 'pending',
    };
@endphp

<div class="jbw-container jbw-booking-detail-page">
    <nav class="jbw-breadcrumb">
        <a href="{{ route('web.bookings.index') }}" class="jbw-breadcrumb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
            Back to bookings
        </a>
    </nav>

    <header class="jbw-booking-detail-header">
        <div class="jbw-booking-detail-header-main">
            <p class="jbw-booking-detail-eyebrow">Booking details</p>
            <h1 class="jbw-booking-detail-title">#{{ $order->order_number }}</h1>
            <p class="jbw-booking-detail-meta">Booked on {{ $order->created_at->format('M d, Y · h:i A') }}</p>
            <div class="jbw-booking-detail-badges">
                <span class="jbw-booking-detail-type">{{ $order->orderTypeLabel() }}</span>
                <span class="jbw-status jbw-status--{{ $statusClass }}">{{ $order->statusLabel() }}</span>
                <span class="jbw-booking-pay-badge jbw-booking-pay-badge--{{ $paymentClass }}">
                    Payment {{ ucfirst($order->payment_status) }}
                </span>
            </div>
        </div>
    </header>

    <div class="jbw-booking-layout jbw-booking-detail-layout">
        <div class="jbw-booking-main">
            <div class="jbw-overview-card jbw-booking-detail-product">
                <p class="jbw-overview-label">Your item</p>
                <div class="jbw-booking-detail-product-row">
                    <div class="jbw-booking-detail-product-media">
                        <img
                            src="{{ $order->itemImageUrl() ?: $fallbackImg }}"
                            alt="{{ $order->itemDisplayName() }}"
                            class="jbw-booking-detail-product-img"
                        >
                    </div>
                    <div class="jbw-booking-detail-product-body">
                        @if ($order->vendor)
                            <p class="jbw-overview-brand">{{ $order->vendor->brand_name }}</p>
                        @endif
                        <h2 class="jbw-overview-title">{{ $order->itemDisplayName() }}</h2>
                        @if ($order->category)
                            <p class="jbw-overview-cat">{{ $order->category->name }}</p>
                        @endif
                        <div class="jbw-booking-detail-attrs">
                            @if ($order->color)
                                <span class="jbw-booking-detail-attr">Color: {{ $order->color }}</span>
                            @endif
                            @if ($order->size)
                                <span class="jbw-booking-detail-attr">Size: {{ $order->size }}</span>
                            @endif
                            @if ($order->quantity && $order->quantity > 1)
                                <span class="jbw-booking-detail-attr">Qty: {{ $order->quantity }}</span>
                            @endif
                        </div>
                        <p class="jbw-overview-price">₹{{ number_format($order->amount, 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="jbw-booking-split jbw-booking-detail-split">
                <div class="jbw-overview-card">
                    <p class="jbw-overview-label">Designer</p>
                    @if ($order->vendor)
                        <div class="jbw-booking-designer-row">
                            @php
                                $vendorImg = $order->vendor->profileImageUrl() ?? $order->vendor->shopLogoUrl();
                            @endphp
                            @if ($vendorImg)
                                <img src="{{ $vendorImg }}" alt="" class="jbw-booking-designer-avatar">
                            @else
                                <span class="jbw-booking-designer-avatar jbw-booking-designer-avatar--fallback" aria-hidden="true">{{ mb_substr($order->vendor->brand_name, 0, 1) }}</span>
                            @endif
                            <div class="jbw-booking-designer-info">
                                <a href="{{ route('web.vendors.show', $order->vendor) }}" class="jbw-booking-designer-name">{{ $order->vendor->brand_name }}</a>
                                <p class="jbw-booking-product-meta">
                                    <font class="starcolor">★</font> {{ number_format($order->vendor->rating, 1) }}
                                    @if ($order->vendor->city) · {{ $order->vendor->city }}@endif
                                </p>
                            </div>
                        </div>
                    @else
                        <p class="jbw-booking-detail-empty">Designer not assigned yet</p>
                    @endif
                </div>

                @if ($order->isRental())
                    <div class="jbw-overview-card">
                        <p class="jbw-overview-label">Rental period</p>
                        @if ($order->rental_start_date)
                            <p class="jbw-booking-detail-rental-range">
                                {{ $order->rental_start_date->format('d M') }}
                                <span aria-hidden="true">→</span>
                                {{ $order->rental_end_date?->format('d M, Y') }}
                            </p>
                            <p class="jbw-booking-product-meta">{{ $order->rentalDurationDays() }} {{ \Illuminate\Support\Str::plural('day', $order->rentalDurationDays()) }}</p>
                        @else
                            <p class="jbw-booking-detail-empty">Rental dates not set</p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="jbw-overview-card">
                <p class="jbw-overview-label">Delivery address</p>
                <div class="jbw-booking-detail-address">
                    <div class="jbw-booking-detail-address-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 21s7-4.5 7-11a7 7 0 10-14 0c0 6.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    </div>
                    <div>
                        <p class="jbw-booking-detail-address-name">{{ $order->customer->name }}</p>
                        <p class="jbw-booking-detail-address-lines">{{ $order->delivery_address ?? '—' }}</p>
                    </div>
                </div>
            </div>

            @php
                $orderMeasurements = \App\Support\BookingMeasurementSupport::orderMeasurements($order);
                $hasMeasurements = collect($orderMeasurements)
                    ->except(['measurement_type', 'size', 'extra_measurements'])
                    ->filter(fn ($value) => filled($value))
                    ->isNotEmpty();
                $measurementSections = \App\Support\WebMeasurementForm::sections();
                $fieldMap = \App\Support\WebMeasurementForm::labelToField();
            @endphp
            @if ($hasMeasurements)
                <div class="jbw-overview-card">
                    <p class="jbw-overview-label">Measurements</p>
                    @if ($orderMeasurements['measurement_type'] ?? null)
                        <p style="margin:0 0 0.75rem;font-size:0.8125rem;color:var(--c-muted)">Type: {{ ucfirst($orderMeasurements['measurement_type']) }}</p>
                    @endif
                    @foreach ($measurementSections as $title => $fields)
                        <div style="margin-bottom:1rem">
                            <p style="margin:0 0 0.5rem;font-size:0.75rem;font-weight:700;color:var(--c-muted);text-transform:uppercase;letter-spacing:0.04em">{{ $title }}</p>
                            <div class="jbw-measures" style="grid-template-columns:repeat(auto-fill,minmax(8rem,1fr))">
                                @foreach ($fields as $label)
                                    @php $key = $fieldMap[$label]; @endphp
                                    <div class="jbw-measure">
                                        <span class="jbw-measure-label">{{ $label }}</span>
                                        <span class="jbw-measure-value">{{ $orderMeasurements[$key] ?? '—' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($order->customer_notes)
                <div class="jbw-overview-card">
                    <p class="jbw-overview-label">Custom notes</p>
                    <p class="jbw-booking-detail-notes">{{ $order->customer_notes }}</p>
                </div>
            @endif

            @if (count($order->referenceImageUrls()) > 0)
                <div class="jbw-overview-card">
                    <p class="jbw-overview-label">Reference images</p>
                    <div class="jbw-booking-ref-grid">
                        @foreach ($order->referenceImageUrls() as $url)
                            <a href="{{ $url }}" class="jbw-booking-ref-item" target="_blank" rel="noopener">
                                <img src="{{ $url }}" alt="Reference image">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <aside class="jbw-booking-sidebar">
            <div class="jbw-booking-sidebar-sticky">
                <div class="jbw-overview-card">
                    <p class="jbw-overview-label">Track booking</p>
                    <ol class="jbw-booking-track">
                        @foreach ($order->trackBookingSteps() as $step)
                            <li class="jbw-booking-track-step jbw-booking-track-step--{{ $step['state'] }}">
                                <span class="jbw-booking-track-marker" aria-hidden="true"></span>
                                <div class="jbw-booking-track-content">
                                    <p class="jbw-booking-track-label">{{ $step['label'] }}</p>
                                    @if ($step['time'])
                                        <p class="jbw-booking-track-time">{{ $step['time'] }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div @class(['jbw-overview-card', 'jbw-booking-payment-card--pending' => $order->payment_status === 'pending'])>
                    <p class="jbw-overview-label">Payment summary</p>
                    <div class="jbw-payment-lines">
                        <div><span>Subtotal</span><span>₹{{ number_format($order->subtotal(), 0) }}</span></div>
                        @if ($order->damageDeduction() > 0)
                            <div class="jbw-payment-line--deduct"><span>Damage deduction</span><span>− ₹{{ number_format($order->damageDeduction(), 0) }}</span></div>
                        @endif
                        <div><span>Shipping</span><span>₹{{ number_format($order->delivery_fee ?? 0, 0) }}</span></div>
                        <div><span>Tax (GST)</span><span>₹{{ number_format($order->tax_amount ?? 0, 0) }}</span></div>
                    </div>
                    <div class="jbw-payment-total">
                        <span>Total amount</span>
                        <strong>₹{{ number_format($order->grandTotal(), 0) }}</strong>
                    </div>
                    @if ($order->payment_status === 'pending')
                        <a href="{{ route('web.bookings.payment', $order) }}" class="jbw-btn jbw-btn--primary jbw-btn--block jbw-booking-detail-pay-btn">
                            Pay now — ₹{{ number_format($order->grandTotal(), 0) }}
                        </a>
                    @else
                        <p class="jbw-booking-detail-paid-note">
                            Paid via <strong>{{ str_replace('_', ' ', ucfirst($order->payment_method ?? 'online')) }}</strong>
                        </p>
                    @endif
                </div>

                @if ($order->vendor)
                    <div class="jbw-overview-card">
                        <p class="jbw-overview-label">Contact designer</p>
                        <p class="jbw-booking-detail-help-meta">Chat or video call with {{ $order->vendor->brand_name }} about this order.</p>
                        <div class="jbw-detail-actions" style="margin-top:0.75rem">
                            <a href="{{ route('web.chat.start', $order->vendor) }}" class="buttonheight jbw-btn jbw-btn--outline">
                                <img src="/assets/frontend/chat-1 1.png" alt=""/> Chat
                            </a>
                            <a href="{{ route('web.chat.start', $order->vendor) }}" class="buttonheight jbw-btn jbw-btn--outline">
                                <img src="/assets/frontend/Container.png" alt=""/> Video Call
                            </a>
                        </div>
                        <p class="textalignment" style="margin-top:0.5rem">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Video calling is limited to 1 minute per session
                        </p>
                    </div>
                @endif
            </div>
        </aside>
        <div class="jbw-overview-card">
                    <p class="jbw-overview-label">Need help?</p>
                    @if ($order->category)
                        <p class="jbw-booking-detail-help-meta">Category: <strong>{{ $order->category->name }}</strong></p>
                    @endif

                    @if ($order->dispute)
                        <p class="jbw-booking-detail-dispute-subject">{{ $order->dispute->subject }}</p>
                        <a href="{{ route('web.bookings.dispute.show', $order) }}" class="jbw-btn jbw-btn--primary jbw-btn--block">
                            {{ $order->dispute->isChatOpen() ? 'Open dispute chat' : 'View dispute' }}
                        </a>
                    @else
                        <form method="POST" action="{{ route('web.bookings.dispute.store', $order) }}" class="jbw-booking-detail-form">
                            @csrf
                            <div class="jbw-field">
                                <label class="jbw-label" for="dispute-subject">Issue type</label>
                                <select id="dispute-subject" name="subject" class="jbw-select" required>
                                    <option value="">Select an issue</option>
                                    @foreach (\App\Models\Dispute::subjectOptionsForCategory($order->category) as $subject)
                                        <option value="{{ $subject }}" @selected(old('subject') === $subject)>{{ $subject }}</option>
                                    @endforeach
                                </select>
                                @error('subject')<p class="jbw-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="jbw-field">
                                <label class="jbw-label" for="dispute-body">Details <span class="jbw-label-optional">(optional)</span></label>
                                <textarea id="dispute-body" name="body" rows="3" class="jbw-textarea" placeholder="Tell us what went wrong...">{{ old('body') }}</textarea>
                            </div>
                            <button type="submit" class="jbw-btn jbw-btn--danger jbw-btn--block">Raise dispute</button>
                        </form>
                    @endif
                </div>

                @if (in_array($order->status, ['new', 'pending_acceptance'], true))
                    <div class="jbw-overview-card jbw-booking-detail-cancel-card">
                        <p class="jbw-overview-label">Cancel booking</p>
                        <p class="jbw-booking-detail-cancel-hint">You can cancel while the designer has not accepted yet.</p>
                        <form method="POST" action="{{ route('web.bookings.cancel', $order) }}" class="jbw-booking-detail-form">
                            @csrf
                            <div class="jbw-field">
                                <label class="jbw-label" for="cancel-reason">Reason</label>
                                <textarea id="cancel-reason" name="reason" rows="3" class="jbw-textarea" placeholder="Why are you cancelling?" required minlength="5">{{ old('reason') }}</textarea>
                                @error('reason')<p class="jbw-field-error">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="jbw-btn jbw-btn--danger jbw-btn--block" onclick="return confirm('Cancel this booking?')">Cancel booking</button>
                        </form>
                    </div>
                @endif
    </div>
</div>
@endsection

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
        <p class="jbw-page-subtitle">Review your selection and submit your rental request</p>
    </div>

    <form method="POST" action="{{ route('web.bookings.store', $item) }}" class="jbw-booking-layout">
        @csrf

        <div class="jbw-booking-main">
            <div class="jbw-overview-card">
                <p class="jbw-overview-label">Your Selection</p>
                <div class="jbw-overview-product">
                    <img src="{{ $item->displayImageUrl() ?: $fallbackImg }}" alt="{{ $item->title }}" class="jbw-overview-img">
                    <div class="jbw-overview-product-info">
                        <p class="jbw-overview-brand">{{ $item->vendor?->brand_name ?? 'Designer' }}</p>
                        <h2 class="jbw-overview-title">{{ $item->title }}</h2>
                        <p class="jbw-overview-cat">{{ $item->category?->name ?? 'Rental' }}@if($item->subcategory) · {{ $item->subcategory->name }}@endif</p>
                        <p class="jbw-overview-price">{{ $item->rentalPriceLabel() }}</p>
                    </div>
                </div>
            </div>

            <div class="jbw-overview-card">
                <p class="jbw-overview-label">Rental Period</p>
                <div class="jbw-measure-form-grid" style="grid-template-columns:1fr 1fr">
                    <div class="jbw-field">
                        <label class="jbw-label" for="rental_start_date">Start date</label>
                        <input type="date" id="rental_start_date" name="rental_start_date" class="jbw-input" value="{{ old('rental_start_date') }}" min="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="jbw-field">
                        <label class="jbw-label" for="rental_end_date">End date</label>
                        <input type="date" id="rental_end_date" name="rental_end_date" class="jbw-input" value="{{ old('rental_end_date') }}" min="{{ now()->format('Y-m-d') }}" required>
                    </div>
                </div>
                @error('rental_start_date')<p class="jbw-field-error">{{ $message }}</p>@enderror
                @error('rental_end_date')<p class="jbw-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="jbw-overview-card">
                <p class="jbw-overview-label">Delivery address</p>
                @if ($addresses->isNotEmpty())
                    <div class="jbw-field">
                        <label class="jbw-label" for="address_id">Saved address</label>
                        <select id="address_id" name="address_id" class="jbw-select">
                            <option value="">Enter a new address below</option>
                            @foreach ($addresses as $address)
                                <option value="{{ $address->id }}" @selected(old('address_id', $defaultAddress?->id) == $address->id)>
                                    {{ $address->label }} — {{ $address->fullAddress() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="jbw-field" style="margin-top:1rem">
                    <label class="jbw-label" for="delivery_address">Full address</label>
                    <textarea id="delivery_address" name="delivery_address" class="jbw-textarea" rows="3" placeholder="House no, street, area, landmark" required>{{ old('delivery_address', $defaultAddress?->fullAddress()) }}</textarea>
                    @error('delivery_address')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="jbw-measure-form-grid" style="grid-template-columns:1fr 1fr;margin-top:1rem">
                    <div class="jbw-field">
                        <label class="jbw-label" for="city">City</label>
                        <input type="text" id="city" name="city" class="jbw-input" value="{{ old('city', $defaultAddress?->city ?? auth('customer')->user()->city) }}">
                    </div>
                    <div class="jbw-field">
                        <label class="jbw-label" for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode" class="jbw-input" value="{{ old('pincode', $defaultAddress?->pincode) }}" maxlength="10">
                    </div>
                </div>
                <p style="margin:0.75rem 0 0;font-size:0.8125rem"><a href="{{ route('web.profile.addresses') }}" style="color:var(--c-primary);font-weight:700">Manage saved addresses</a></p>
            </div>

            @if ($measurement)
                <div class="jbw-overview-card">
                    <p class="jbw-overview-label">Measurements on file</p>
                    <div class="jbw-measures">
                        <div class="jbw-measure"><span class="jbw-measure-label">Height</span><span class="jbw-measure-value">{{ $measurement->height_cm ?? '—' }}</span></div>
                        <div class="jbw-measure"><span class="jbw-measure-label">Chest</span><span class="jbw-measure-value">{{ $measurement->chest_cm ?? '—' }}</span></div>
                        <div class="jbw-measure"><span class="jbw-measure-label">Waist</span><span class="jbw-measure-value">{{ $measurement->waist_cm ?? '—' }}</span></div>
                    </div>
                    <p style="margin:0.75rem 0 0;font-size:0.8125rem"><a href="{{ route('web.profile.measurements.create') }}" style="color:var(--c-primary);font-weight:700">Update measurements</a></p>
                </div>
            @else
                <div class="jbw-overview-card">
                    <p class="jbw-overview-label">Measurements</p>
                    <p style="margin:0 0 0.75rem;color:var(--c-muted);font-size:0.875rem">Add measurements for a better fit before booking.</p>
                    <a href="{{ route('web.profile.measurements.create') }}" class="jbw-btn jbw-btn--outline jbw-btn--sm">Add measurements</a>
                </div>
            @endif

            <div class="jbw-overview-card">
                <p class="jbw-overview-label">Additional notes</p>
                <textarea name="customer_notes" class="jbw-textarea" placeholder="Fitting instructions, event details, or customisation notes..." style="min-height:6rem">{{ old('customer_notes') }}</textarea>
            </div>
        </div>

        <div class="jbw-booking-sidebar">
            <div class="jbw-overview-card jbw-overview-card--accent">
                <p class="jbw-overview-label">Payment Summary</p>
                <div class="jbw-payment-lines" style="margin-bottom:0">
                    <div><span>Rental ({{ $pricing['rental_days'] ?? 1 }} {{ Str::plural('day', $pricing['rental_days'] ?? 1) }})</span><span>₹{{ number_format($pricing['subtotal'] ?? $item->rentalPriceAmount(), 0) }}</span></div>
                    <div><span>Delivery</span><span>₹{{ number_format($pricing['shipping_fee'] ?? 150, 0) }}</span></div>
                    <div><span>GST &amp; tax</span><span>₹{{ number_format($pricing['tax_amount'] ?? 0, 0) }}</span></div>
                </div>
                <div class="jbw-payment-total">
                    <span style="font-weight:700">Estimated total</span>
                    <strong>₹{{ number_format($pricing['total_amount'] ?? $item->rentalPriceAmount(), 0) }}</strong>
                </div>
                <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--block" style="margin-top:1.25rem;border-radius:10px;padding:0.9375rem">
                    Continue to payment
                </button>
                <p style="text-align:center;font-size:0.75rem;color:var(--c-muted);margin:0.75rem 0 0">
                    You will pay securely on the next step before the designer is notified.
                </p>
            </div>
        </div>
    </form>
</div>
@endsection

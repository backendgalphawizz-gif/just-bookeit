@extends('web.layouts.app')

@section('title', 'Booking Overview')

@section('content')
@php
    $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80';
    $overviewImg = $selectedVariant?->imageUrl() ?: $item->displayImageUrl();
    $overviewPrice = $selectedVariant ? $item->rentalPriceLabelFor($selectedVariant) : $item->rentalPriceLabel();
@endphp

<div class="jbw-container">
    <nav class="jbw-breadcrumb" style="margin-bottom: 0rem;">
        <a href="{{ route('web.catalog.show', $item) }}" class="jbw-breadcrumb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            Back to item
        </a>
    </nav>

    <div class="jbw-page-head" style="padding-top:0rem; margin-bottom:0.5rem;">
        <h1 class="jbw-page-title">Booking Overview</h1>
        <p class="jbw-page-subtitle" style="margin-top: 0rem;">Review your selection and submit your rental request</p>
    </div>

    <form method="POST" action="{{ route('web.bookings.store', $item) }}" class="jbw-booking-layout" id="booking-overview-form" data-preview-url="{{ route('web.bookings.preview', $item) }}">
        @csrf

        <div class="jbw-booking-main">
            <div class="jbw-overview-card">
                <p class="jbw-overview-label">Your Selection</p>
                <div class="jbw-overview-product">
                    <img src="{{ $overviewImg ?: $fallbackImg }}" alt="{{ $item->title }}" class="jbw-overview-img" id="jbw-overview-img">
                    <div class="jbw-overview-product-info">
                        <p class="jbw-overview-brand">{{ $item->vendor?->brand_name ?? 'Designer' }}</p>
                        <h2 class="jbw-overview-title">{{ $item->title }}</h2>
                        <p class="jbw-overview-cat">{{ $item->category?->name ?? 'Rental' }}@if($item->subcategory) · {{ $item->subcategory->name }}@endif</p>
                        <p class="jbw-overview-price" id="jbw-overview-price">{{ $overviewPrice }}</p>
                        @if ($item->hasVariants())
                            <p class="jbw-overview-variant" id="jbw-overview-variant">
                                @if ($selectedVariant)
                                    {{ collect([$selectedVariant->size, $selectedVariant->color])->filter()->implode(' · ') }}
                                @else
                                    Base item
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            @if ($item->hasVariants())
                <div class="jbw-overview-card">
                    @include('web.catalog.partials.variant-picker', [
                        'item' => $item,
                        'selectedVariantId' => $selectedVariantId ?? null,
                        'baseImageUrl' => $overviewImg ?: $item->displayImageUrl(),
                    ])
                </div>
            @endif

            <div class="jbw-overview-card" >
                <p class="jbw-overview-label">Rental Period</p>
                <div class="jbw-measure-form-grids" style="grid-template-columns:1fr 1fr;">
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
                    @if ($measurement->measurement_type)
                        <p style="margin:0 0 0.75rem;font-size:0.8125rem;color:var(--c-muted)">Type: {{ ucfirst($measurement->measurement_type) }}</p>
                    @endif
                    @php $fieldMap = \App\Support\WebMeasurementForm::labelToField(); @endphp
                    @foreach ($measurementSections as $title => $fields)
                        <div style="margin-bottom:1rem">
                            <p style="margin:0 0 0.5rem;font-size:0.75rem;font-weight:700;color:var(--c-muted);text-transform:uppercase;letter-spacing:0.04em">{{ $title }}</p>
                            <div class="jbw-measures" style="grid-template-columns:repeat(auto-fill,minmax(8rem,1fr))">
                                @foreach ($fields as $label)
                                    @php $key = $fieldMap[$label]; @endphp
                                    <div class="jbw-measure">
                                        <span class="jbw-measure-label">{{ $label }}</span>
                                        <span class="jbw-measure-value">{{ $measurementValues[$key] ?? '—' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
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
            <div class="jbw-overview-card jbw-overview-card--accent" id="booking-payment-summary">
                <p class="jbw-overview-label">Payment Summary</p>
                <div class="jbw-payment-lines" style="margin-bottom:0">
                    <div><span id="booking-rental-label">Rental ({{ $pricing['rental_days'] ?? 1 }} {{ Str::plural('day', $pricing['rental_days'] ?? 1) }})</span><span id="booking-line-subtotal">₹{{ number_format($pricing['subtotal'] ?? $item->rentalPriceAmount(), 0) }}</span></div>
                    <div><span>Delivery</span><span id="booking-line-delivery">₹{{ number_format($pricing['shipping_fee'] ?? 150, 0) }}</span></div>
                    <div><span>GST &amp; tax</span><span id="booking-line-tax">₹{{ number_format($pricing['tax_amount'] ?? 0, 0) }}</span></div>
                </div>
                <div class="jbw-payment-total">
                    <span style="font-weight:700">Estimated total</span>
                    <strong id="booking-grand-total">₹{{ number_format($pricing['total_amount'] ?? $item->rentalPriceAmount(), 0) }}</strong>
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

@push('scripts')
<script>
(function () {
    const form = document.getElementById('booking-overview-form');
    if (!form) return;

    const previewUrl = form.dataset.previewUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let timer = null;

    const formatInr = (n) => '₹' + Math.round(Number(n) || 0).toLocaleString('en-IN');
    const dayLabel = (n) => n === 1 ? 'day' : 'days';

    const refreshPreview = () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const body = new FormData();
            body.append('_token', csrf);
            const start = form.querySelector('#rental_start_date')?.value;
            const end = form.querySelector('#rental_end_date')?.value;
            const variant = form.querySelector('input[name="portfolio_item_variant_id"]:checked')?.value;
            if (start) body.append('rental_start_date', start);
            if (end) body.append('rental_end_date', end);
            if (variant) body.append('portfolio_item_variant_id', variant);
            body.append('shipment_required', '1');

            try {
                const res = await fetch(previewUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const { pricing } = await res.json();
                if (!pricing) return;

                const days = pricing.rental_days || 1;
                document.getElementById('booking-rental-label').textContent = `Rental (${days} ${dayLabel(days)})`;
                document.getElementById('booking-line-subtotal').textContent = formatInr(pricing.subtotal);
                document.getElementById('booking-line-delivery').textContent = formatInr(pricing.shipping_fee);
                document.getElementById('booking-line-tax').textContent = formatInr(pricing.tax_amount);
                document.getElementById('booking-grand-total').textContent = formatInr(pricing.total_amount);
            } catch (e) { /* ignore */ }
        }, 300);
    };

    form.querySelector('#rental_start_date')?.addEventListener('change', refreshPreview);
    form.querySelector('#rental_end_date')?.addEventListener('change', refreshPreview);
    form.querySelector('#rental_start_date')?.addEventListener('input', refreshPreview);
    form.querySelector('#rental_end_date')?.addEventListener('input', refreshPreview);

    form.querySelectorAll('input[name="portfolio_item_variant_id"]').forEach((input) => {
        input.addEventListener('change', () => {
            const priceEl = document.getElementById('jbw-overview-price');
            const imgEl = document.getElementById('jbw-overview-img');
            const variantEl = document.getElementById('jbw-overview-variant');

            if (priceEl && input.dataset.label) {
                priceEl.textContent = input.dataset.label;
            }

            if (imgEl && input.dataset.image) {
                imgEl.src = input.dataset.image;
            } else if (imgEl) {
                const picker = document.getElementById('jbw-variant-picker');
                if (picker?.dataset.baseImage) {
                    imgEl.src = picker.dataset.baseImage;
                }
            }

            const picker = document.getElementById('jbw-variant-picker');
            picker?.querySelectorAll('.jbw-variant-chip').forEach((chip) => chip.classList.remove('is-selected'));
            input.closest('.jbw-variant-chip')?.classList.add('is-selected');

            if (variantEl) {
                if (!input.value) {
                    variantEl.textContent = 'Base item';
                } else {
                    const chipText = input.closest('.jbw-variant-chip')?.querySelector('strong')?.textContent?.trim();
                    if (chipText) variantEl.textContent = chipText;
                }
            }

            refreshPreview();
        });
    });
})();
</script>
@endpush

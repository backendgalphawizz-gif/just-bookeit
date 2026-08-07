@extends('web.layouts.app')

@section('title', 'Booking Overview')

@section('content')
@php
    $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80';
    $overviewImg = $selectedVariant?->imageUrl() ?: $item->displayImageUrl();
    $overviewPrice = $selectedVariant ? $item->rentalPriceLabelFor($selectedVariant) : $item->rentalPriceLabel();
    $customer = auth('customer')->user();
    $selectedAddressId = (int) old('address_id', $defaultAddress?->id ?? 0);
    $selectedAddress = $addresses->firstWhere('id', $selectedAddressId) ?? $defaultAddress;
    $billingAddressId = (int) old('billing_address_id', 0);
    $billingAddress = $billingAddressId ? $addresses->firstWhere('id', $billingAddressId) : null;
    $shipmentRequired = (bool) old('shipment_required', true);
    $activeMeasureType = old('measurement_type', $measurement?->measurement_type ?? 'women');
    $variantSpecs = $selectedVariant
        ? collect([
            $selectedVariant->color,
            $selectedVariant->size ? 'Size '.$selectedVariant->size : null,
        ])->filter()->implode(' | ')
        : ($item->category?->name ?? 'Rental');
    $rentalDaysCount = (int) ($pricing['rental_days'] ?? $pricing['billing_days'] ?? 1);
    $vendorImg = $item->vendor?->profileImageUrl() ?? $item->vendor?->shopLogoUrl();
@endphp

<div class="jbw-container jbw-bo-page">
    <header class="jbw-bo-head">
        <a href="{{ route('web.catalog.show', $item) }}" class="jbw-bo-back" aria-label="Back to item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 class="jbw-bo-title">Booking Overview</h1>
    </header>

    <form method="POST" action="{{ route('web.bookings.store', $item) }}" enctype="multipart/form-data" class="jbw-bo-layout" id="booking-overview-form" data-preview-url="{{ route('web.bookings.preview', $item) }}" data-draft-key="booking-draft-{{ $item->id }}" @if (old()) data-has-old="1" @endif>
        @csrf

        <div class="jbw-bo-main">
            <section class="jbw-bo-section">
                <h2 class="jbw-bo-label">Your Selection</h2>
                <div class="jbw-bo-card jbw-bo-selection">
                    <a href="{{ route('web.catalog.show', $item) }}" class="jbw-bo-trash" title="Remove" aria-label="Remove selection">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                    </a>
                    <div class="jbw-bo-selection-body">
                        <img src="{{ $overviewImg ?: $fallbackImg }}" alt="{{ $item->title }}" class="jbw-bo-selection-img" id="jbw-overview-img">
                        <div class="jbw-bo-selection-info">
                            <h3 class="jbw-bo-selection-title">{{ $item->title }}</h3>
                            <p class="jbw-bo-selection-specs" id="jbw-overview-variant">{{ $variantSpecs ?: ($item->category?->name ?? 'Rental') }}</p>
                            <p class="jbw-bo-selection-price" id="jbw-overview-price">{{ $overviewPrice }}</p>
                        </div>
                    </div>

                    @if ($item->hasVariants())
                        <div class="jbw-bo-variant-wrap">
                            @include('web.catalog.partials.variant-picker', [
                                'item' => $item,
                                'selectedVariantId' => $selectedVariantId ?? null,
                                'baseImageUrl' => $overviewImg ?: $item->displayImageUrl(),
                            ])
                        </div>
                    @endif
                </div>
            </section>

            <div class="jbw-bo-split">
                <section class="jbw-bo-section">
                    <h2 class="jbw-bo-label">Designer</h2>
                    <div class="jbw-bo-card jbw-bo-designer">
                        @if ($item->vendor)
                            @if ($vendorImg)
                                <img src="{{ $vendorImg }}" alt="" class="jbw-bo-designer-avatar">
                            @else
                                <span class="jbw-bo-designer-avatar jbw-bo-designer-avatar--fallback">{{ mb_substr($item->vendor->brand_name, 0, 1) }}</span>
                            @endif
                            <div>
                                <p class="jbw-bo-designer-name">{{ $item->vendor->brand_name }}</p>
                                <p class="jbw-bo-designer-meta">
                                    <span class="starcolor">★</span> {{ number_format((float) $item->vendor->rating, 1) }}
                                    @if ($item->vendor->city)
                                        <span aria-hidden="true">·</span> {{ $item->vendor->city }}
                                    @endif
                                </p>
                            </div>
                        @else
                            <p class="jbw-bo-muted">Designer details unavailable</p>
                        @endif
                    </div>
                </section>

                <section class="jbw-bo-section">
                    <h2 class="jbw-bo-label">Rental Period</h2>
                    <div class="jbw-bo-card jbw-bo-rental" data-rental-card>
                        @if ($item->requiresRentalPeriod())
                            <button type="button" class="jbw-bo-change" data-toggle-rental>Change</button>
                            <div class="jbw-bo-rental-summary" data-rental-summary>
                                <p class="jbw-bo-rental-dates" id="booking-rental-dates">
                                    @if (old('rental_start_date') && old('rental_end_date'))
                                        {{ \Carbon\Carbon::parse(old('rental_start_date'))->format('d M') }} - {{ \Carbon\Carbon::parse(old('rental_end_date'))->format('d M') }}
                                    @else
                                        Select dates
                                    @endif
                                </p>
                                <p class="jbw-bo-rental-days" id="booking-rental-hint">{{ $rentalDaysCount }} {{ \Illuminate\Support\Str::plural('Day', $rentalDaysCount) }} Duration</p>
                            </div>
                            <div class="jbw-bo-rental-fields" data-rental-fields hidden>
                                <div class="jbw-bo-rental-grid">
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
                        @else
                            <div class="jbw-field">
                                <label class="jbw-label" for="event_date">Event date (optional)</label>
                                <input type="date" id="event_date" name="event_date" class="jbw-input" value="{{ old('event_date') }}" min="{{ now()->format('Y-m-d') }}">
                            </div>
                            @error('event_date')<p class="jbw-field-error">{{ $message }}</p>@enderror
                        @endif
                    </div>
                </section>
            </div>

            <section class="jbw-bo-section">
                <h2 class="jbw-bo-label">Delivery Preference</h2>
                <div class="jbw-bo-card jbw-bo-delivery">
                    <label class="jbw-bo-check">
                        <input type="hidden" name="shipment_required" value="0">
                        <input type="checkbox" name="shipment_required" value="1" id="shipment_required" @checked($shipmentRequired)>
                        <span>Is shipment required?</span>
                    </label>

                    <div class="jbw-bo-address-box" data-delivery-box @if (! $shipmentRequired) hidden @endif>
                        @php
                            $deliveryAddressValue = old('delivery_address');
                            if (! filled($deliveryAddressValue)) {
                                $deliveryAddressValue = $selectedAddress?->fullAddress() ?? '';
                            }
                            $deliveryAddressDisplay = filled($deliveryAddressValue)
                                ? $deliveryAddressValue
                                : 'Add a delivery address';
                        @endphp
                        <input type="hidden" name="address_id" id="address_id" value="{{ $selectedAddress?->id }}">
                        <input type="hidden" name="delivery_address" id="delivery_address" value="{{ $deliveryAddressValue }}">
                        <input type="hidden" name="city" id="city" value="{{ old('city', $selectedAddress?->city ?? $customer->city) }}">
                        <input type="hidden" name="pincode" id="pincode" value="{{ old('pincode', $selectedAddress?->pincode) }}">

                        <div class="jbw-bo-address-head">
                            <div>
                                <p class="jbw-bo-address-name" data-delivery-name>{{ $selectedAddress?->name ?: $customer->name }}</p>
                                <p class="jbw-bo-address-lines" data-delivery-lines>
                                    {{ $deliveryAddressDisplay }}
                                </p>
                                @if ($selectedAddress?->mobile_number || $customer->mobile_number)
                                    <p class="jbw-bo-address-phone" data-delivery-phone>{{ $selectedAddress?->mobile_number ?: $customer->mobile_number }}</p>
                                @endif
                            </div>
                            <button type="button" class="jbw-bo-change" data-toggle-delivery>Change</button>
                        </div>

                        <div class="jbw-bo-address-picker" data-delivery-picker hidden>
                            @if ($addresses->isNotEmpty())
                                <div class="jbw-field">
                                    <label class="jbw-label" for="address_id_select">Saved address</label>
                                    <select id="address_id_select" class="jbw-select" data-delivery-select>
                                        @foreach ($addresses as $address)
                                            <option
                                                value="{{ $address->id }}"
                                                data-name="{{ $address->name ?: $customer->name }}"
                                                data-lines="{{ $address->fullAddress() }}"
                                                data-city="{{ $address->city }}"
                                                data-pincode="{{ $address->pincode }}"
                                                data-phone="{{ $address->mobile_number }}"
                                                @selected($selectedAddress?->id == $address->id)
                                            >{{ $address->label }} — {{ $address->fullAddress() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <p class="jbw-bo-address-manage"><a href="{{ route('web.profile.addresses') }}" data-save-draft>Manage saved addresses</a></p>
                        </div>
                    </div>
                    @error('delivery_address')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
            </section>

            <section class="jbw-bo-section">
                <h2 class="jbw-bo-label">Measurements</h2>
                <div class="jbw-bo-measure-tabs" role="tablist" aria-label="Measurement type">
                    @foreach (['women' => 'Women', 'men' => 'Men', 'kid' => 'Kid'] as $type => $label)
                        <button
                            type="button"
                            class="jbw-bo-measure-tab{{ $activeMeasureType === $type ? ' is-active' : '' }}"
                            data-measure-tab="{{ $type }}"
                            role="tab"
                            aria-selected="{{ $activeMeasureType === $type ? 'true' : 'false' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>
                <input type="hidden" name="measurement_type" id="measurement_type" value="{{ $activeMeasureType }}">
                @if ($measurement)
                    <input type="hidden" name="measurement_profile_id" id="measurement_profile_id" value="{{ $measurement->id }}" data-measure-profile-input>
                    <div class="jbw-bo-measure-meta">
                        <span>Using {{ $measurement->name ?: 'saved profile' }}</span>
                        <a href="{{ route('web.profile.measurements.create', ['redirect' => request()->fullUrl()]) }}" data-save-draft>Update</a>
                    </div>
                @else
                    <div class="jbw-bo-measure-empty">
                        <p>Add measurements for a better fit before booking.</p>
                        <a href="{{ route('web.profile.measurements.create', ['redirect' => request()->fullUrl()]) }}" data-save-draft class="jbw-btn jbw-btn--outline jbw-btn--sm">Add measurements</a>
                    </div>
                @endif
            </section>

            <section class="jbw-bo-section">
                <h2 class="jbw-bo-label">Additional Notes</h2>
                <textarea name="customer_notes" class="jbw-bo-notes" placeholder="Any specific requirements or fitting instructions...">{{ old('customer_notes') }}</textarea>
            </section>

            <section class="jbw-bo-section">
                <div class="jbw-ref-images" data-ref-images data-max="5">
                    <div class="jbw-ref-images-head">
                        <h2 class="jbw-bo-label" style="margin:0">Reference images</h2>
                        <span class="jbw-ref-images-hint">You can upload maximum 5 images</span>
                    </div>
                    <div class="jbw-ref-images-grid" data-ref-grid>
                        <label class="jbw-ref-add" data-ref-add>
                            <input type="file" name="reference_images[]" accept="{{ \App\Support\MediaUploadSupport::acceptAttribute('image') }}" multiple hidden data-ref-input>
                            <span class="jbw-ref-add-icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                    <circle cx="12" cy="13" r="4"/>
                                </svg>
                                <span class="jbw-ref-add-plus">+</span>
                            </span>
                        </label>
                    </div>
                    @error('reference_images')<p class="jbw-field-error">{{ $message }}</p>@enderror
                    @error('reference_images.*')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
            </section>
        </div>

        <aside class="jbw-bo-aside">
            <section class="jbw-bo-section">
                <h2 class="jbw-bo-label">Billing Address</h2>
                <div class="jbw-bo-billing" data-billing-box>
                    <input type="hidden" name="billing_address_id" id="billing_address_id" value="{{ $billingAddress?->id }}">
                    <input type="hidden" name="billing_address" id="billing_address" value="{{ old('billing_address', $billingAddress?->fullAddress()) }}">

                    <div class="jbw-bo-billing-empty" data-billing-empty @if ($billingAddress) hidden @endif>
                        <a href="{{ route('web.profile.addresses') }}" class="jbw-bo-add-address" data-save-draft>
                            <span>+</span> Add Address
                        </a>
                        @if ($addresses->isNotEmpty())
                            <button type="button" class="jbw-bo-change jbw-bo-billing-pick" data-toggle-billing>Or choose saved</button>
                        @endif
                    </div>

                    <div class="jbw-bo-billing-selected" data-billing-selected @if (! $billingAddress) hidden @endif>
                        <div class="jbw-bo-address-head">
                            <p class="jbw-bo-address-name" data-billing-name>{{ $billingAddress?->name ?: $customer->name }}</p>
                            <button type="button" class="jbw-bo-change" data-toggle-billing>Change</button>
                        </div>
                        <p class="jbw-bo-address-lines" data-billing-lines>{{ $billingAddress?->fullAddress() }}</p>
                    </div>

                    @if ($addresses->isNotEmpty())
                        <div class="jbw-bo-address-picker" data-billing-picker hidden>
                            <div class="jbw-field">
                                <label class="jbw-label" for="billing_address_select">Saved address</label>
                                <select id="billing_address_select" class="jbw-select" data-billing-select>
                                    <option value="">Select billing address</option>
                                    @foreach ($addresses as $address)
                                        <option
                                            value="{{ $address->id }}"
                                            data-name="{{ $address->name ?: $customer->name }}"
                                            data-lines="{{ $address->fullAddress() }}"
                                            @selected($billingAddress?->id == $address->id)
                                        >{{ $address->label }} — {{ $address->fullAddress() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <section class="jbw-bo-pay-card" id="booking-payment-summary">
                <h2 class="jbw-bo-pay-title">Payment Summary</h2>
                <div class="jbw-bo-pay-lines">
                    <div class="jbw-bo-pay-line">
                        <span id="booking-rental-label">Subtotal</span>
                        <span id="booking-line-subtotal">₹{{ number_format($pricing['subtotal'] ?? $item->rentalPriceAmount(), 0) }}</span>
                    </div>
                    <div class="jbw-bo-pay-line">
                        <span>Advance Amount</span>
                        <span id="booking-line-advance">₹{{ number_format($pricing['advance_amount'] ?? 0, 0) }}</span>
                    </div>
                    <div class="jbw-bo-pay-line">
                        <span>Shipping</span>
                        <span id="booking-line-delivery">₹{{ number_format($pricing['shipping_fee'] ?? 0, 0) }}</span>
                    </div>
                    <div class="jbw-bo-pay-line">
                        <span>GST &amp; Tax</span>
                        <span id="booking-line-tax">₹{{ number_format($pricing['tax_amount'] ?? 0, 0) }}</span>
                    </div>
                </div>
                <div class="jbw-bo-pay-total">
                    <span>Total Amount</span>
                    <strong id="booking-grand-total">₹{{ number_format($pricing['total_amount'] ?? $item->rentalPriceAmount(), 0) }}</strong>
                </div>
                <p class="jbw-bo-pay-note">*Adjusted for current promotional period.</p>
                <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--block jbw-bo-checkout-btn">Checkout</button>
            </section>
        </aside>
    </form>
</div>
@endsection

@push('scripts')
@php
    $profilesByType = [];
    foreach (($measurementProfiles ?? collect()) as $p) {
        $type = $p->measurement_type ?: 'women';
        $profilesByType[$type][] = [
            'id' => $p->id,
            'name' => $p->name ?: ('Profile #'.$p->id),
        ];
    }
@endphp
<script>
(function () {
    const form = document.getElementById('booking-overview-form');
    if (!form) return;

    const previewUrl = form.dataset.previewUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let timer = null;
    const formatInr = (n) => '₹' + Math.round(Number(n) || 0).toLocaleString('en-IN');
    const dayLabel = (n) => n === 1 ? 'Day' : 'Days';

    const refreshPreview = () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const body = new FormData();
            body.append('_token', csrf);
            const start = form.querySelector('#rental_start_date')?.value;
            const end = form.querySelector('#rental_end_date')?.value;
            const variant = form.querySelector('#jbw-variant-id')?.value
                || form.querySelector('input[name="portfolio_item_variant_id"]:checked')?.value
                || form.querySelector('input[name="portfolio_item_variant_id"]')?.value;
            const shipment = form.querySelector('#shipment_required')?.checked ? '1' : '0';
            if (start) body.append('rental_start_date', start);
            if (end) body.append('rental_end_date', end);
            if (variant) body.append('portfolio_item_variant_id', variant);
            body.append('shipment_required', shipment);

            try {
                const res = await fetch(previewUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const { pricing } = await res.json();
                if (!pricing) return;

                const days = pricing.rental_days || pricing.billing_days || 1;
                const hint = document.getElementById('booking-rental-hint');
                if (hint) hint.textContent = `${days} ${dayLabel(days)} Duration`;
                const datesEl = document.getElementById('booking-rental-dates');
                if (datesEl && start && end) {
                    const fmt = (v) => {
                        const d = new Date(v + 'T00:00:00');
                        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
                    };
                    datesEl.textContent = `${fmt(start)} - ${fmt(end)}`;
                }
                document.getElementById('booking-line-subtotal').textContent = formatInr(pricing.subtotal);
                document.getElementById('booking-line-advance').textContent = formatInr(pricing.advance_amount || 0);
                document.getElementById('booking-line-delivery').textContent = formatInr(pricing.shipping_fee);
                document.getElementById('booking-line-tax').textContent = formatInr(pricing.tax_amount);
                document.getElementById('booking-grand-total').textContent = formatInr(pricing.total_amount);
            } catch (e) { /* ignore */ }
        }, 300);
    };

    const startInput = form.querySelector('#rental_start_date');
    const endInput = form.querySelector('#rental_end_date');
    const syncEndMin = () => {
        if (!startInput || !endInput) return;
        if (startInput.value) {
            endInput.min = startInput.value;
            if (endInput.value && endInput.value < startInput.value) endInput.value = '';
        }
    };
    syncEndMin();

    const rentalFields = form.querySelector('[data-rental-fields]');
    const rentalToggle = form.querySelector('[data-toggle-rental]');
    if (rentalFields && (!startInput?.value || !endInput?.value)) {
        rentalFields.hidden = false;
    }
    rentalToggle?.addEventListener('click', () => {
        if (!rentalFields) return;
        rentalFields.hidden = !rentalFields.hidden;
    });

    startInput?.addEventListener('change', () => { syncEndMin(); refreshPreview(); });
    endInput?.addEventListener('change', refreshPreview);
    startInput?.addEventListener('input', () => { syncEndMin(); refreshPreview(); });
    endInput?.addEventListener('input', refreshPreview);

    const shipment = form.querySelector('#shipment_required');
    const deliveryBox = form.querySelector('[data-delivery-box]');
    shipment?.addEventListener('change', () => {
        if (deliveryBox) deliveryBox.hidden = !shipment.checked;
        if (startInput) {
            // keep rental required state unchanged
        }
        refreshPreview();
    });

    form.querySelector('[data-toggle-delivery]')?.addEventListener('click', () => {
        const picker = form.querySelector('[data-delivery-picker]');
        if (picker) picker.hidden = !picker.hidden;
    });

    form.querySelector('[data-delivery-select]')?.addEventListener('change', function () {
        const opt = this.selectedOptions[0];
        if (!opt) return;
        form.querySelector('#address_id').value = opt.value;
        form.querySelector('#delivery_address').value = opt.dataset.lines || '';
        form.querySelector('#city').value = opt.dataset.city || '';
        form.querySelector('#pincode').value = opt.dataset.pincode || '';
        form.querySelector('[data-delivery-name]').textContent = opt.dataset.name || '';
        form.querySelector('[data-delivery-lines]').textContent = opt.dataset.lines || '';
        const phone = form.querySelector('[data-delivery-phone]');
        if (phone) phone.textContent = opt.dataset.phone || '';
    });

    // Ensure hidden delivery fields match the currently selected saved address.
    (function syncDeliveryFromSelect() {
        const select = form.querySelector('[data-delivery-select]');
        const deliveryInput = form.querySelector('#delivery_address');
        if (!select || !deliveryInput) return;
        const opt = select.selectedOptions[0];
        if (!opt || !opt.value) return;
        if (!String(deliveryInput.value || '').trim() && opt.dataset.lines) {
            deliveryInput.value = opt.dataset.lines;
            form.querySelector('#address_id').value = opt.value;
            form.querySelector('#city').value = opt.dataset.city || '';
            form.querySelector('#pincode').value = opt.dataset.pincode || '';
            const lines = form.querySelector('[data-delivery-lines]');
            if (lines) lines.textContent = opt.dataset.lines;
        }
    })();

    const billingPickers = form.querySelectorAll('[data-toggle-billing]');
    billingPickers.forEach((btn) => btn.addEventListener('click', () => {
        const picker = form.querySelector('[data-billing-picker]');
        if (picker) picker.hidden = !picker.hidden;
    }));

    form.querySelector('[data-billing-select]')?.addEventListener('change', function () {
        const opt = this.selectedOptions[0];
        const empty = form.querySelector('[data-billing-empty]');
        const selected = form.querySelector('[data-billing-selected]');
        if (!opt || !opt.value) {
            form.querySelector('#billing_address_id').value = '';
            form.querySelector('#billing_address').value = '';
            if (empty) empty.hidden = false;
            if (selected) selected.hidden = true;
            return;
        }
        form.querySelector('#billing_address_id').value = opt.value;
        form.querySelector('#billing_address').value = opt.dataset.lines || '';
        form.querySelector('[data-billing-name]').textContent = opt.dataset.name || '';
        form.querySelector('[data-billing-lines]').textContent = opt.dataset.lines || '';
        if (empty) empty.hidden = true;
        if (selected) selected.hidden = false;
    });

    form.addEventListener('jbw:draft-restored', () => {
        syncEndMin();
        refreshPreview();
    });

    form.querySelectorAll('input[name="portfolio_item_variant_id"]').forEach((input) => {
        input.addEventListener('change', refreshPreview);
    });

    document.getElementById('jbw-variant-picker')?.addEventListener('jbw:variant-changed', (event) => {
        const { label, image, variantLabel } = event.detail || {};
        const priceEl = document.getElementById('jbw-overview-price');
        const imgEl = document.getElementById('jbw-overview-img');
        const variantEl = document.getElementById('jbw-overview-variant');
        if (priceEl && label) priceEl.textContent = label;
        if (imgEl && image) imgEl.src = image;
        if (variantEl) variantEl.textContent = variantLabel || '';
        refreshPreview();
    });

    const profilesByType = @json($profilesByType);
    form.querySelectorAll('[data-measure-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            const type = tab.getAttribute('data-measure-tab');
            form.querySelectorAll('[data-measure-tab]').forEach((t) => {
                t.classList.toggle('is-active', t === tab);
                t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
            });
            form.querySelector('#measurement_type').value = type;
            const profiles = profilesByType[type] || [];
            const input = form.querySelector('[data-measure-profile-input]');
            if (input && profiles[0]) input.value = profiles[0].id;
        });
    });
})();
</script>
<script>
(function () {
    document.querySelectorAll('[data-ref-images]').forEach(function (root) {
        var max = Number(root.getAttribute('data-max') || 5);
        var grid = root.querySelector('[data-ref-grid]');
        var addBtn = root.querySelector('[data-ref-add]');
        var input = root.querySelector('[data-ref-input]');
        if (!grid || !addBtn || !input) return;
        var files = [];
        var activeIndex = 0;
        function syncInput() {
            var dt = new DataTransfer();
            files.forEach(function (file) { dt.items.add(file); });
            input.files = dt.files;
            addBtn.classList.toggle('is-hidden', files.length >= max);
        }
        function render() {
            grid.querySelectorAll('[data-ref-thumb]').forEach(function (el) { el.remove(); });
            files.forEach(function (file, index) {
                var thumb = document.createElement('div');
                thumb.className = 'jbw-ref-thumb' + (index === activeIndex ? ' is-active' : '');
                thumb.setAttribute('data-ref-thumb', String(index));
                var img = document.createElement('img');
                img.alt = file.name || ('Reference ' + (index + 1));
                img.src = URL.createObjectURL(file);
                thumb.appendChild(img);
                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'jbw-ref-thumb-remove';
                remove.setAttribute('aria-label', 'Remove image');
                remove.textContent = '×';
                remove.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    files.splice(index, 1);
                    if (activeIndex >= files.length) activeIndex = Math.max(0, files.length - 1);
                    syncInput();
                    render();
                });
                thumb.appendChild(remove);
                thumb.addEventListener('click', function () {
                    activeIndex = index;
                    render();
                });
                grid.insertBefore(thumb, addBtn);
            });
        }
        input.addEventListener('change', function () {
            Array.from(input.files || []).forEach(function (file) {
                if (files.length >= max) return;
                if (!file.type || file.type.indexOf('image/') !== 0) return;
                files.push(file);
            });
            activeIndex = Math.max(0, files.length - 1);
            syncInput();
            render();
        });
    });
})();
</script>
@endpush

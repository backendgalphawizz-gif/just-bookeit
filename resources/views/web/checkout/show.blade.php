@extends('web.layouts.app')

@section('title', 'Booking Overview')

@section('content')
@php
    $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80';
    $customer = auth('customer')->user();
    $selectedAddressId = (int) old('address_id', $defaultAddress?->id ?? 0);
    $selectedAddress = $addresses->firstWhere('id', $selectedAddressId) ?? $defaultAddress;
    $billingAddressId = (int) old('billing_address_id', 0);
    $billingAddress = $billingAddressId ? $addresses->firstWhere('id', $billingAddressId) : null;
    $activeMeasureType = old('measurement_type', $measurement?->measurement_type ?? 'women');
    $baseDeliveryFee = \App\Services\Booking\BookingPricingService::shippingFee(true);
    $vendors = collect($preview['vendors'] ?? []);
    $primaryVendor = $cartItems->first()?->vendor;
    $shipmentAny = collect(old('vendor_shipments', []))->contains(fn ($row) => filter_var($row['shipment_required'] ?? true, FILTER_VALIDATE_BOOLEAN));
    if (old('vendor_shipments') === null) {
        $shipmentAny = true;
    }
    $summaryData = $preview['summary'] ?? [];
    $subtotalAll = (float) ($summaryData['amount'] ?? $vendors->sum('subtotal'));
    $advanceAll = (float) ($summaryData['advance_amount'] ?? $vendors->sum('advance_amount'));
    $shippingAll = (float) ($summaryData['delivery_fee'] ?? $vendors->sum('delivery_fee'));
    $taxAll = (float) ($summaryData['tax_amount'] ?? $vendors->sum('tax_amount'));
    $grandAll = (float) ($summaryData['grand_total'] ?? $vendors->sum('grand_total'));
    $rentalItems = $cartItems->filter(fn ($ci) => $ci->portfolioItem?->requiresRentalPeriod());
@endphp

<div class="jbw-container jbw-bo-page">
    <header class="jbw-bo-head">
        <a href="{{ route('web.cart.index') }}" class="jbw-bo-back" aria-label="Back to cart">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 class="jbw-bo-title">Booking Overview</h1>
    </header>

    <form method="POST" action="{{ route('web.checkout.store') }}" enctype="multipart/form-data" class="jbw-bo-layout" id="checkout-form" data-preview-url="{{ route('web.checkout.preview') }}" data-draft-key="checkout-draft" @if (old()) data-has-old="1" @endif>
        @csrf

        <div class="jbw-bo-main">
            <section class="jbw-bo-section">
                <h2 class="jbw-bo-label">Your Selection</h2>
                <div class="jbw-bo-selection-list">
                    @foreach ($cartItems as $cartItem)
                        @php
                            $product = $cartItem->portfolioItem;
                            $variant = $cartItem->variant;
                            $specs = $variant
                                ? collect([$variant->color, $variant->size ? 'Size '.$variant->size : null])->filter()->implode(' | ')
                                : ($product?->category?->name ?? 'Item');
                            $unitRate = $product?->dailyRateFor($variant) ?? 0;
                            $needsRental = (bool) $product?->requiresRentalPeriod();
                            $priceLabel = $needsRental
                                ? '₹'.number_format($unitRate, 0).' / day'
                                : '₹'.number_format($unitRate, 0);
                            $oldItem = collect(old('items', []))->first(
                                fn ($row) => (int) ($row['cart_item_id'] ?? 0) === (int) $cartItem->id
                            ) ?? [];
                        @endphp
                        <article class="jbw-bo-card jbw-bo-selection">
                            <button type="button" class="jbw-bo-trash" title="Remove" aria-label="Remove item" data-remove-url="{{ route('web.cart.destroy', $cartItem) }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                            </button>
                            <div class="jbw-bo-selection-body">
                                <img src="{{ $variant?->imageUrl() ?: $product?->displayImageUrl() ?: $fallbackImg }}" alt="" class="jbw-bo-selection-img">
                                <div class="jbw-bo-selection-info">
                                    <h3 class="jbw-bo-selection-title">{{ $product?->title ?? 'Product' }}</h3>
                                    <p class="jbw-bo-selection-specs">{{ $specs }}@if($cartItem->quantity > 1) · Qty {{ $cartItem->quantity }}@endif</p>
                                    <p class="jbw-bo-selection-price">{{ $priceLabel }}</p>
                                </div>
                            </div>
                            <input type="hidden" name="items[{{ $cartItem->id }}][cart_item_id]" value="{{ $cartItem->id }}">
                            <input type="hidden" name="items[{{ $cartItem->id }}][portfolio_item_id]" value="{{ $product?->id }}">
                            @if ($needsRental)
                                <div class="checkout-item-rental jbw-bo-inline-rental" data-cart-item-id="{{ $cartItem->id }}" hidden>
                                    <div class="jbw-bo-rental-grid">
                                        <div class="jbw-field">
                                            <label class="jbw-label" for="item_rental_start_{{ $cartItem->id }}">Rental start</label>
                                            <input type="date" id="item_rental_start_{{ $cartItem->id }}" name="items[{{ $cartItem->id }}][rental_start_date]" class="jbw-input checkout-item-rental-start" value="{{ $oldItem['rental_start_date'] ?? '' }}" min="{{ now()->format('Y-m-d') }}" required>
                                        </div>
                                        <div class="jbw-field">
                                            <label class="jbw-label" for="item_rental_end_{{ $cartItem->id }}">Rental end</label>
                                            <input type="date" id="item_rental_end_{{ $cartItem->id }}" name="items[{{ $cartItem->id }}][rental_end_date]" class="jbw-input checkout-item-rental-end" value="{{ $oldItem['rental_end_date'] ?? '' }}" min="{{ now()->format('Y-m-d') }}" required>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
                @error('items')<p class="jbw-field-error">{{ $message }}</p>@enderror
            </section>

            <div class="jbw-bo-split">
                <section class="jbw-bo-section">
                    <h2 class="jbw-bo-label">Designer</h2>
                    <div class="jbw-bo-card jbw-bo-designer">
                        @if ($primaryVendor)
                            @php $vendorImg = $primaryVendor->profileImageUrl() ?? $primaryVendor->shopLogoUrl(); @endphp
                            @if ($vendorImg)
                                <img src="{{ $vendorImg }}" alt="" class="jbw-bo-designer-avatar">
                            @else
                                <span class="jbw-bo-designer-avatar jbw-bo-designer-avatar--fallback">{{ mb_substr($primaryVendor->brand_name, 0, 1) }}</span>
                            @endif
                            <div>
                                <p class="jbw-bo-designer-name">{{ $primaryVendor->brand_name }}</p>
                                <p class="jbw-bo-designer-meta">
                                    @if ((float) ($primaryVendor->rating ?? 0) > 0)
                                        <span class="starcolor">★</span> {{ number_format((float) $primaryVendor->rating, 1) }}
                                        @if ($primaryVendor->city || $cartItems->pluck('vendor_id')->unique()->count() > 1)
                                            <span aria-hidden="true">·</span>
                                        @endif
                                    @endif
                                    @if ($primaryVendor->city)
                                        {{ $primaryVendor->city }}
                                    @endif
                                    @if ($cartItems->pluck('vendor_id')->unique()->count() > 1)
                                        <span aria-hidden="true">·</span> +{{ $cartItems->pluck('vendor_id')->unique()->count() - 1 }} more
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
                        @if ($rentalItems->isNotEmpty())
                            <button type="button" class="jbw-bo-change" data-toggle-rental>Change</button>
                            <div class="jbw-bo-rental-summary" data-rental-summary>
                                <p class="jbw-bo-rental-dates" id="checkout-rental-dates">Select dates</p>
                                <p class="jbw-bo-rental-days" id="checkout-rental-hint">Set rental period</p>
                            </div>
                            <div class="jbw-bo-rental-fields" data-rental-fields hidden>
                                <p class="jbw-bo-rental-help">Choose rental dates for each item</p>
                            </div>
                        @else
                            <p class="jbw-bo-rental-dates">No rental period needed</p>
                            <p class="jbw-bo-rental-days">Service / ready-to-wear items</p>
                        @endif
                    </div>
                </section>
            </div>

            <section class="jbw-bo-section">
                <h2 class="jbw-bo-label">Delivery Preference</h2>
                <div class="jbw-bo-card jbw-bo-delivery">
                    <label class="jbw-bo-check">
                        <input type="checkbox" id="shipment_master" @checked($shipmentAny)>
                        <span>Is shipment required?</span>
                    </label>

                    @foreach ($vendors as $index => $vendorGroup)
                        @php $enabled = (bool) old("vendor_shipments.$index.shipment_required", $vendorGroup['shipment_required'] ?? true); @endphp
                        <div class="checkout-vendor-row hidden" data-vendor-id="{{ $vendorGroup['vendor_id'] }}" data-delivery-fee="{{ (float) $baseDeliveryFee }}" aria-hidden="true">
                            <input type="hidden" name="vendor_shipments[{{ $index }}][vendor_id]" value="{{ $vendorGroup['vendor_id'] }}">
                            <input type="hidden" class="checkout-shipment-toggle" name="vendor_shipments[{{ $index }}][shipment_required]" value="{{ $enabled ? '1' : '0' }}">
                        </div>
                    @endforeach

                <div class="jbw-bo-address-box" data-delivery-box @if (! $shipmentAny) hidden @endif>
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
                <div class="jbw-bo-measure-tabs" role="tablist">
                    @foreach (['women' => 'Women', 'men' => 'Men', 'kid' => 'Kid'] as $type => $label)
                        <button type="button" class="jbw-bo-measure-tab{{ $activeMeasureType === $type ? ' is-active' : '' }}" data-measure-tab="{{ $type }}" role="tab" aria-selected="{{ $activeMeasureType === $type ? 'true' : 'false' }}">{{ $label }}</button>
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
                        <p>Add measurements for a better fit.</p>
                        <a href="{{ route('web.profile.measurements.create', ['redirect' => request()->fullUrl()]) }}" data-save-draft class="jbw-btn jbw-btn--outline jbw-btn--sm">Add measurements</a>
                    </div>
                @endif
            </section>

            <section class="jbw-bo-section">
                <h2 class="jbw-bo-label">Additional Notes</h2>
                <textarea name="customer_notes" class="jbw-bo-notes" placeholder="Any specific requirements or fitting instructions...">{{ old('customer_notes') }}</textarea>
            </section>

            <section class="jbw-bo-section">
                <div class="jbw-ref-images-head">
                    <h2 class="jbw-bo-label" style="margin:0">Reference images</h2>
                    <span class="jbw-ref-images-hint">You can upload maximum 5 images{{ $cartItems->count() > 1 ? ' per item' : '' }}</span>
                </div>

                <div class="jbw-bo-ref-list">
                    @foreach ($cartItems as $index => $cartItem)
                        @php $product = $cartItem->portfolioItem; @endphp
                        <div class="jbw-ref-images" data-ref-images data-max="5">
                            @if ($cartItems->count() > 1)
                                <p class="jbw-bo-ref-item-title">{{ $product?->title ?? 'Item' }}</p>
                            @endif
                            <div class="jbw-ref-images-grid" data-ref-grid>
                                <label class="jbw-ref-add" data-ref-add>
                                    <input
                                        type="file"
                                        name="reference_images[{{ $cartItem->id }}][]"
                                        accept="{{ \App\Support\MediaUploadSupport::acceptAttribute('image') }}"
                                        multiple
                                        hidden
                                        data-ref-input
                                    >
                                    <span class="jbw-ref-add-icon" aria-hidden="true">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                            <circle cx="12" cy="13" r="4"/>
                                        </svg>
                                        <span class="jbw-ref-add-plus">+</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('reference_images')<p class="jbw-field-error">{{ $message }}</p>@enderror
                @error('reference_images.*')<p class="jbw-field-error">{{ $message }}</p>@enderror
                @error('reference_images.*.*')<p class="jbw-field-error">{{ $message }}</p>@enderror
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
                                        <option value="{{ $address->id }}" data-name="{{ $address->name ?: $customer->name }}" data-lines="{{ $address->fullAddress() }}" @selected($billingAddress?->id == $address->id)>
                                            {{ $address->label }} — {{ $address->fullAddress() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <section class="jbw-bo-pay-card" id="checkout-summary">
                <h2 class="jbw-bo-pay-title">Payment Summary</h2>
                <div id="checkout-summary-vendors" hidden>
                    @foreach ($vendors as $vendorGroup)
                        <div class="checkout-summary-vendor" data-vendor-id="{{ $vendorGroup['vendor_id'] }}">
                            <span class="js-line-subtotal">₹{{ number_format($vendorGroup['subtotal'], 0) }}</span>
                            <span class="js-line-delivery">₹{{ number_format($vendorGroup['delivery_fee'], 0) }}</span>
                            <span class="js-line-tax">₹{{ number_format($vendorGroup['tax_amount'], 0) }}</span>
                            <span class="js-line-advance">₹{{ number_format($vendorGroup['advance_amount'] ?? 0, 0) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="jbw-bo-pay-lines">
                    <div class="jbw-bo-pay-line"><span>Subtotal</span><span id="checkout-line-subtotal">₹{{ number_format($subtotalAll, 0) }}</span></div>
                    <div class="jbw-bo-pay-line" id="checkout-line-advance-row" @if ($advanceAll <= 0) hidden @endif>
                        <span>Advance Amount</span>
                        <span id="checkout-line-advance">₹{{ number_format($advanceAll, 0) }}</span>
                    </div>
                    <div class="jbw-bo-pay-line"><span>Shipping</span><span id="checkout-line-shipping">₹{{ number_format($shippingAll, 0) }}</span></div>
                    <div class="jbw-bo-pay-line"><span>GST &amp; Tax</span><span id="checkout-line-tax">₹{{ number_format($taxAll, 0) }}</span></div>
                </div>
                <div class="jbw-bo-pay-total">
                    <span>Total Amount</span>
                    <strong id="checkout-grand-total">₹{{ number_format($grandAll, 0) }}</strong>
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
        $profilesByType[$type][] = ['id' => $p->id, 'name' => $p->name ?: ('Profile #'.$p->id)];
    }
@endphp
<script>
(function () {
    const form = document.getElementById('checkout-form');
    if (!form) return;

    const previewUrl = form.dataset.previewUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let timer = null;
    const formatInr = (n) => '₹' + Math.round(Number(n) || 0).toLocaleString('en-IN');
    const parseInr = (str) => Number(String(str || '').replace(/[^0-9.-]/g, '')) || 0;

    // Move per-item rental fields into rental period panel
    const rentalFields = form.querySelector('[data-rental-fields]');
    form.querySelectorAll('.jbw-bo-inline-rental').forEach((row) => {
        if (rentalFields) {
            row.hidden = false;
            rentalFields.appendChild(row);
        }
    });

    const syncRentalSummary = () => {
        const starts = Array.from(form.querySelectorAll('.checkout-item-rental-start')).map((el) => el.value).filter(Boolean);
        const ends = Array.from(form.querySelectorAll('.checkout-item-rental-end')).map((el) => el.value).filter(Boolean);
        const datesEl = document.getElementById('checkout-rental-dates');
        const hintEl = document.getElementById('checkout-rental-hint');
        if (!datesEl || !hintEl) return;
        if (!starts.length || !ends.length) {
            datesEl.textContent = 'Select dates';
            hintEl.textContent = 'Set rental period';
            return;
        }
        const start = starts.sort()[0];
        const end = ends.sort().slice(-1)[0];
        const fmt = (v) => {
            const d = new Date(v + 'T00:00:00');
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
        };
        datesEl.textContent = `${fmt(start)} - ${fmt(end)}`;
        const days = Math.max(1, Math.round((new Date(end) - new Date(start)) / 86400000) + 1);
        hintEl.textContent = `${days} Day${days === 1 ? '' : 's'} Duration`;
    };

    form.querySelector('[data-toggle-rental]')?.addEventListener('click', () => {
        if (!rentalFields) return;
        rentalFields.hidden = !rentalFields.hidden;
    });
    if (rentalFields && form.querySelectorAll('.checkout-item-rental-start').length) {
        const anyEmpty = Array.from(form.querySelectorAll('.checkout-item-rental-start, .checkout-item-rental-end')).some((el) => !el.value);
        rentalFields.hidden = !anyEmpty;
    }

    const collectShipments = () => Array.from(form.querySelectorAll('.checkout-vendor-row')).map((row) => ({
        vendor_id: row.dataset.vendorId,
        shipment_required: row.querySelector('.checkout-shipment-toggle')?.value === '1' ? 1 : 0,
    }));

    const recomputeTotalsFromVendors = () => {
        let subtotal = 0, shipping = 0, tax = 0, advance = 0;
        form.querySelectorAll('.checkout-summary-vendor').forEach((block) => {
            subtotal += parseInr(block.querySelector('.js-line-subtotal')?.textContent);
            shipping += parseInr(block.querySelector('.js-line-delivery')?.textContent);
            tax += parseInr(block.querySelector('.js-line-tax')?.textContent);
            advance += parseInr(block.querySelector('.js-line-advance')?.textContent);
        });
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = formatInr(val); };
        set('checkout-line-subtotal', subtotal);
        set('checkout-line-shipping', shipping);
        set('checkout-line-tax', tax);
        set('checkout-line-advance', advance);
        const advanceRow = document.getElementById('checkout-line-advance-row');
        if (advanceRow) advanceRow.hidden = !(advance > 0);
        set('checkout-grand-total', subtotal + shipping);
    };

    const applyLocalShipmentState = () => {
        form.querySelectorAll('.checkout-vendor-row').forEach((row) => {
            const checked = row.querySelector('.checkout-shipment-toggle')?.value === '1';
            const fee = Number(row.dataset.deliveryFee || 0);
            const block = document.querySelector(`.checkout-summary-vendor[data-vendor-id="${row.dataset.vendorId}"]`);
            if (!block) return;
            const deliveryEl = block.querySelector('.js-line-delivery');
            if (deliveryEl) deliveryEl.textContent = formatInr(checked ? fee : 0);
        });
        recomputeTotalsFromVendors();
    };

    const updateSummary = (data) => {
        (data.vendors || []).forEach((group) => {
            const block = document.querySelector(`.checkout-summary-vendor[data-vendor-id="${group.vendor_id}"]`);
            const row = document.querySelector(`.checkout-vendor-row[data-vendor-id="${group.vendor_id}"]`);
            if (row && group.delivery_fee != null) row.dataset.deliveryFee = group.delivery_fee;
            if (!block) return;
            block.querySelector('.js-line-subtotal').textContent = formatInr(group.subtotal);
            block.querySelector('.js-line-delivery').textContent = formatInr(group.delivery_fee);
            block.querySelector('.js-line-tax').textContent = formatInr(group.tax_amount);
            const adv = block.querySelector('.js-line-advance');
            if (adv) adv.textContent = formatInr(group.advance_amount || 0);
        });
        if (data.summary) {
            document.getElementById('checkout-line-subtotal').textContent = formatInr(data.summary.amount ?? data.summary.subtotal ?? 0);
            document.getElementById('checkout-line-shipping').textContent = formatInr(data.summary.delivery_fee ?? 0);
            document.getElementById('checkout-line-tax').textContent = formatInr(data.summary.tax_amount ?? 0);
            const advanceAmount = Number(data.summary.advance_amount ?? 0);
            const advanceEl = document.getElementById('checkout-line-advance');
            const advanceRow = document.getElementById('checkout-line-advance-row');
            if (advanceEl) advanceEl.textContent = formatInr(advanceAmount);
            if (advanceRow) advanceRow.hidden = !(advanceAmount > 0);
            document.getElementById('checkout-grand-total').textContent = formatInr(data.summary.grand_total ?? 0);
        } else {
            recomputeTotalsFromVendors();
        }
    };

    const refreshPreview = () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const body = new FormData();
            body.append('_token', csrf);
            let rentalLinesComplete = true;
            form.querySelectorAll('.checkout-item-rental').forEach((row) => {
                const start = row.querySelector('.checkout-item-rental-start')?.value;
                const end = row.querySelector('.checkout-item-rental-end')?.value;
                const cartItemId = row.dataset.cartItemId;
                if (!start || !end) { rentalLinesComplete = false; return; }
                body.append(`items[${cartItemId}][cart_item_id]`, cartItemId);
                body.append(`items[${cartItemId}][rental_start_date]`, start);
                body.append(`items[${cartItemId}][rental_end_date]`, end);
            });
            syncRentalSummary();
            if (form.querySelectorAll('.checkout-item-rental').length > 0 && !rentalLinesComplete) return;
            collectShipments().forEach((row, i) => {
                body.append(`vendor_shipments[${i}][vendor_id]`, row.vendor_id);
                if (row.shipment_required) body.append(`vendor_shipments[${i}][shipment_required]`, '1');
            });
            try {
                const res = await fetch(previewUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                updateSummary(await res.json());
            } catch (e) {}
        }, 300);
    };

    const syncItemEndMin = (row) => {
        const startInput = row.querySelector('.checkout-item-rental-start');
        const endInput = row.querySelector('.checkout-item-rental-end');
        if (!startInput || !endInput) return;
        if (startInput.value) {
            endInput.min = startInput.value;
            if (endInput.value && endInput.value < startInput.value) endInput.value = '';
        }
    };

    form.querySelectorAll('.checkout-item-rental').forEach((row) => {
        syncItemEndMin(row);
        row.querySelector('.checkout-item-rental-start')?.addEventListener('change', () => { syncItemEndMin(row); refreshPreview(); });
        row.querySelector('.checkout-item-rental-end')?.addEventListener('change', refreshPreview);
    });

    const master = form.querySelector('#shipment_master');
    const deliveryBox = form.querySelector('[data-delivery-box]');
    const syncMasterShipment = () => {
        const on = !!master?.checked;
        form.querySelectorAll('.checkout-shipment-toggle').forEach((el) => { el.value = on ? '1' : '0'; });
        if (deliveryBox) deliveryBox.hidden = !on;
        applyLocalShipmentState();
        refreshPreview();
    };
    master?.addEventListener('change', syncMasterShipment);

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

    form.querySelectorAll('[data-toggle-billing]').forEach((btn) => btn.addEventListener('click', () => {
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

    applyLocalShipmentState();
    syncRentalSummary();

    form.addEventListener('jbw:draft-restored', () => {
        form.querySelectorAll('.checkout-item-rental').forEach(syncItemEndMin);
        syncRentalSummary();
        refreshPreview();
    });

    form.querySelectorAll('[data-remove-url]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Remove this item from cart?')) return;
            const body = new FormData();
            body.append('_token', csrf);
            body.append('_method', 'DELETE');
            try {
                await fetch(btn.getAttribute('data-remove-url'), {
                    method: 'POST',
                    body,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
            } catch (e) {}
            window.location.reload();
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

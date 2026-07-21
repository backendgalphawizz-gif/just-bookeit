@extends('web.layouts.app')

@section('title', 'Checkout')

@section('content')
@php $fallbackImg = 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=300&q=80'; @endphp

<div class="jbw-container jbw-page-shell">
    <nav class="jbw-breadcrumb" style="margin-bottom:0.5rem">
        <a href="{{ route('web.cart.index') }}" class="jbw-breadcrumb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            Back to cart
        </a>
    </nav>

    <div class="jbw-page-head" style="padding-top:0;margin-bottom:0.75rem">
        <h1 class="jbw-page-title">Checkout</h1>
        <p class="jbw-page-subtitle">One payment · {{ $summary['vendor_count'] }} vendor{{ $summary['vendor_count'] === 1 ? '' : 's' }} · {{ $summary['items_count'] }} item{{ $summary['items_count'] === 1 ? '' : 's' }}</p>
    </div>

    <form method="POST" action="{{ route('web.checkout.store') }}" class="jbw-booking-layout" id="checkout-form" data-preview-url="{{ route('web.checkout.preview') }}" data-draft-key="checkout-draft" @if (old()) data-has-old="1" @endif>
        @csrf

        <div class="jbw-booking-main">
            <section class="jbw-overview-card">
                <p class="jbw-overview-label">Items in your order</p>
                @foreach ($cartItems->groupBy('vendor_id') as $vendorItems)
                    @php $vendor = $vendorItems->first()?->vendor; @endphp
                    <div class="jbw-checkout-vendor-block">
                        <p class="jbw-checkout-vendor-name">{{ $vendor?->brand_name ?? 'Designer' }}</p>
                        <div class="jbw-line-item-list">
                            @foreach ($vendorItems as $cartItem)
                                @php
                                    $product = $cartItem->portfolioItem;
                                    $variant = $cartItem->variant;
                                    $variantLabel = $variant ? collect([$variant->size, $variant->color])->filter()->implode(' · ') : null;
                                    $unitRate = $product?->dailyRateFor($variant) ?? 0;
                                    $needsRental = (bool) $product?->requiresRentalPeriod();
                                    $oldItem = collect(old('items', []))->first(
                                        fn ($row) => (int) ($row['cart_item_id'] ?? 0) === (int) $cartItem->id
                                    ) ?? [];
                                @endphp
                                @include('web.partials.line-item-row', [
                                    'image' => $variant?->imageUrl() ?: $product?->displayImageUrl(),
                                    'fallback' => $fallbackImg,
                                    'title' => $product?->title ?? 'Product',
                                    'category' => $product?->category?->name,
                                    'variantLabel' => $variantLabel,
                                    'showBaseVariant' => $product?->hasVariants() && ! $variant,
                                    'quantity' => $cartItem->quantity,
                                    'unitPrice' => $needsRental
                                        ? '₹'.number_format($unitRate, 0).' / day'
                                        : '₹'.number_format($unitRate, 0),
                                    'compact' => true,
                                ])
                                <input type="hidden" name="items[{{ $cartItem->id }}][cart_item_id]" value="{{ $cartItem->id }}">
                                <input type="hidden" name="items[{{ $cartItem->id }}][portfolio_item_id]" value="{{ $product?->id }}">
                                @if ($needsRental)
                                    <div class="jbw-measure-form-grids checkout-item-rental" data-cart-item-id="{{ $cartItem->id }}" style="grid-template-columns:1fr 1fr;margin:0.75rem 0 1.25rem;padding-left:0.25rem">
                                        <div class="jbw-field">
                                            <label class="jbw-label" for="item_rental_start_{{ $cartItem->id }}">Rental start <span class="jbw-required">*</span></label>
                                            <input type="date"
                                                   id="item_rental_start_{{ $cartItem->id }}"
                                                   name="items[{{ $cartItem->id }}][rental_start_date]"
                                                   class="jbw-input checkout-item-rental-start"
                                                   value="{{ $oldItem['rental_start_date'] ?? '' }}"
                                                   min="{{ now()->format('Y-m-d') }}"
                                                   required>
                                        </div>
                                        <div class="jbw-field">
                                            <label class="jbw-label" for="item_rental_end_{{ $cartItem->id }}">Rental end <span class="jbw-required">*</span></label>
                                            <input type="date"
                                                   id="item_rental_end_{{ $cartItem->id }}"
                                                   name="items[{{ $cartItem->id }}][rental_end_date]"
                                                   class="jbw-input checkout-item-rental-end"
                                                   value="{{ $oldItem['rental_end_date'] ?? '' }}"
                                                   min="{{ now()->format('Y-m-d') }}"
                                                   required>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @error('items')<p class="jbw-field-error">{{ $message }}</p>@enderror
            </section>

            <section class="jbw-overview-card">
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
                    <textarea id="delivery_address" name="delivery_address" class="jbw-textarea" rows="3" required>{{ old('delivery_address', $defaultAddress?->fullAddress()) }}</textarea>
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
            </section>

            <section class="jbw-overview-card">
                <p class="jbw-overview-label">Delivery fee per vendor</p>
                <p class="jbw-overview-help">Toggle delivery for each designer. The delivery fee is only added when enabled.</p>
                @php
                    $baseDeliveryFee = \App\Services\Booking\BookingPricingService::shippingFee(true);
                @endphp
                @foreach ($preview['vendors'] ?? [] as $index => $vendorGroup)
                    @php $enabled = (bool) old("vendor_shipments.$index.shipment_required", $vendorGroup['shipment_required'] ?? true); @endphp
                    <div class="checkout-vendor-row" data-vendor-id="{{ $vendorGroup['vendor_id'] }}" data-delivery-fee="{{ (float) $baseDeliveryFee }}">
                        <div>
                            <input type="hidden" name="vendor_shipments[{{ $index }}][vendor_id]" value="{{ $vendorGroup['vendor_id'] }}">
                            <strong>{{ $vendorGroup['vendor_name'] }}</strong>
                            <p class="checkout-vendor-delivery-hint">₹{{ number_format($baseDeliveryFee, 0) }} delivery when enabled</p>
                        </div>
                        <label class="jbw-toggle-switch">
                            <input type="checkbox" class="checkout-shipment-toggle" name="vendor_shipments[{{ $index }}][shipment_required]" value="1" @checked($enabled)>
                            <span class="jbw-toggle-track"><span class="jbw-toggle-thumb"></span></span>
                            <span class="jbw-toggle-label" data-toggle-on="Delivery on" data-toggle-off="No delivery">{{ $enabled ? 'Delivery on' : 'No delivery' }}</span>
                        </label>
                    </div>
                @endforeach
            </section>

            @if ($measurement)
                <section class="jbw-overview-card">
                    <p class="jbw-overview-label">Measurements</p>
                    @if (($measurementProfiles ?? collect())->count() > 1)
                        <div class="jbw-field" style="margin-bottom:0.5rem">
                            <label class="jbw-label" for="measurement_profile_id">Select measurement profile</label>
                            <select id="measurement_profile_id" name="measurement_profile_id" class="jbw-select">
                                @foreach ($measurementProfiles as $profile)
                                    <option value="{{ $profile->id }}" @selected($measurement->id === $profile->id)>
                                        {{ $profile->name ?: 'Profile #'.$profile->id }}@if ($profile->measurement_type) — {{ ucfirst($profile->measurement_type) }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <p style="margin:0;font-size:0.875rem;color:var(--c-muted)">This profile's measurements will be used for this checkout.</p>
                    @else
                        <input type="hidden" name="measurement_profile_id" value="{{ $measurement->id }}">
                        <p style="margin:0;font-size:0.875rem;color:var(--c-muted)">Using your saved profile measurements for this checkout.</p>
                    @endif
                    <p style="margin:0.75rem 0 0;font-size:0.8125rem"><a href="{{ route('web.profile.measurements.create', ['redirect' => request()->fullUrl()]) }}" data-save-draft style="color:var(--c-primary);font-weight:700">Update measurements</a></p>
                </section>
            @else
                <section class="jbw-overview-card">
                    <p class="jbw-overview-label">Measurements</p>
                    <p style="margin:0 0 0.75rem;color:var(--c-muted);font-size:0.875rem">Add measurements for a better fit.</p>
                    <a href="{{ route('web.profile.measurements.create', ['redirect' => request()->fullUrl()]) }}" data-save-draft class="jbw-btn jbw-btn--outline jbw-btn--sm">Add measurements</a>
                </section>
            @endif

            <section class="jbw-overview-card">
                <p class="jbw-overview-label">Order notes</p>
                <textarea name="customer_notes" class="jbw-textarea" placeholder="Fitting instructions or event details..." style="min-height:5rem">{{ old('customer_notes') }}</textarea>
            </section>
        </div>

        <div class="jbw-booking-sidebar">
            <div class="jbw-overview-card jbw-overview-card--accent" id="checkout-summary">
                <p class="jbw-overview-label">Order summary</p>
                <div id="checkout-summary-vendors">
                    @foreach ($preview['vendors'] ?? [] as $vendorGroup)
                        <div class="checkout-summary-vendor" data-vendor-id="{{ $vendorGroup['vendor_id'] }}">
                            <p class="checkout-summary-vendor-name">{{ $vendorGroup['vendor_name'] }}</p>
                            <div class="jbw-payment-lines">
                                <div><span>Items</span><span class="js-line-subtotal">₹{{ number_format($vendorGroup['subtotal'], 0) }}</span></div>
                                <div><span>Delivery</span><span class="js-line-delivery">₹{{ number_format($vendorGroup['delivery_fee'], 0) }}</span></div>
                                <div><span>GST</span><span class="js-line-tax">₹{{ number_format($vendorGroup['tax_amount'], 0) }}</span></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="jbw-payment-total">
                    <span style="font-weight:700">Estimated total</span>
                    <strong id="checkout-grand-total">₹{{ number_format($preview['summary']['grand_total'] ?? 0, 0) }}</strong>
                </div>
                <p class="jbw-cart-summary-note">Select rental dates above to calculate the final total.</p>
                <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--block" style="margin-top:1.25rem;border-radius:10px;padding:0.9375rem">
                    Place order &amp; pay
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('checkout-form');
    if (!form) return;

    const previewUrl = form.dataset.previewUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let timer = null;

    const formatInr = (n) => '₹' + Math.round(Number(n) || 0).toLocaleString('en-IN');

    const parseInr = (str) => Number(String(str || '').replace(/[^0-9.-]/g, '')) || 0;

    const collectShipments = () => Array.from(form.querySelectorAll('.checkout-vendor-row')).map((row) => {
        const vendorId = row.dataset.vendorId;
        const checked = row.querySelector('.checkout-shipment-toggle')?.checked;
        return { vendor_id: vendorId, shipment_required: checked ? 1 : 0 };
    });

    const applyLocalShipmentState = () => {
        let grand = 0;
        form.querySelectorAll('.checkout-vendor-row').forEach((row) => {
            const checked = row.querySelector('.checkout-shipment-toggle')?.checked;
            const fee = Number(row.dataset.deliveryFee || 0);
            const block = document.querySelector(`.checkout-summary-vendor[data-vendor-id="${row.dataset.vendorId}"]`);
            const label = row.querySelector('.jbw-toggle-label');
            if (label) {
                label.textContent = checked ? label.dataset.toggleOn : label.dataset.toggleOff;
            }
            if (!block) return;
            const deliveryEl = block.querySelector('.js-line-delivery');
            const subtotal = parseInr(block.querySelector('.js-line-subtotal')?.textContent);
            const tax = parseInr(block.querySelector('.js-line-tax')?.textContent);
            const delivery = checked ? fee : 0;
            if (deliveryEl) deliveryEl.textContent = formatInr(delivery);
            grand += subtotal + tax + delivery;
        });
        const total = document.getElementById('checkout-grand-total');
        if (total) total.textContent = formatInr(grand);
    };

    const updateSummary = (data) => {
        const vendors = data.vendors || [];
        vendors.forEach((group) => {
            const block = document.querySelector(`.checkout-summary-vendor[data-vendor-id="${group.vendor_id}"]`);
            const row = document.querySelector(`.checkout-vendor-row[data-vendor-id="${group.vendor_id}"]`);
            if (row && group.delivery_fee) row.dataset.deliveryFee = group.delivery_fee;
            if (!block) return;
            block.querySelector('.js-line-subtotal').textContent = formatInr(group.subtotal);
            block.querySelector('.js-line-delivery').textContent = formatInr(group.delivery_fee);
            block.querySelector('.js-line-tax').textContent = formatInr(group.tax_amount);
        });
        const total = document.getElementById('checkout-grand-total');
        if (total && data.summary) {
            total.textContent = formatInr(data.summary.grand_total);
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
                if (!start || !end) {
                    rentalLinesComplete = false;
                    return;
                }
                body.append(`items[${cartItemId}][cart_item_id]`, cartItemId);
                body.append(`items[${cartItemId}][rental_start_date]`, start);
                body.append(`items[${cartItemId}][rental_end_date]`, end);
            });

            // Wait until every rental line has both dates before hitting the priced preview.
            if (form.querySelectorAll('.checkout-item-rental').length > 0 && !rentalLinesComplete) {
                return;
            }

            collectShipments().forEach((row, i) => {
                body.append(`vendor_shipments[${i}][vendor_id]`, row.vendor_id);
                if (row.shipment_required) {
                    body.append(`vendor_shipments[${i}][shipment_required]`, '1');
                }
            });

            try {
                const res = await fetch(previewUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const json = await res.json();
                updateSummary(json);
            } catch (e) { /* ignore */ }
        }, 300);
    };

    const syncItemEndMin = (row) => {
        const startInput = row.querySelector('.checkout-item-rental-start');
        const endInput = row.querySelector('.checkout-item-rental-end');
        if (!startInput || !endInput) return;
        const start = startInput.value;
        if (start) {
            endInput.min = start;
            if (endInput.value && endInput.value < start) {
                endInput.value = '';
            }
        }
    };

    form.querySelectorAll('.checkout-item-rental').forEach((row) => {
        syncItemEndMin(row);
        row.querySelector('.checkout-item-rental-start')?.addEventListener('change', () => {
            syncItemEndMin(row);
            refreshPreview();
        });
        row.querySelector('.checkout-item-rental-end')?.addEventListener('change', refreshPreview);
    });

    form.querySelectorAll('.checkout-shipment-toggle').forEach((el) => el.addEventListener('change', () => {
        applyLocalShipmentState();
        refreshPreview();
    }));

    applyLocalShipmentState();

    // After draft restore (returning from measurements), re-sync date mins + totals.
    form.addEventListener('jbw:draft-restored', () => {
        form.querySelectorAll('.checkout-item-rental').forEach(syncItemEndMin);
        refreshPreview();
    });
})();
</script>
@endpush

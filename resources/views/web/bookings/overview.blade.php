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

    <form method="POST" action="{{ route('web.bookings.store', $item) }}" enctype="multipart/form-data" class="jbw-booking-layout" id="booking-overview-form" data-preview-url="{{ route('web.bookings.preview', $item) }}" data-draft-key="booking-draft-{{ $item->id }}" @if (old()) data-has-old="1" @endif>
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

            @if ($item->requiresRentalPeriod())
                <div class="jbw-overview-card">
                    <div class="jbw-overview-card-head">
                        <p class="jbw-overview-label">Rental Period</p>
                        <span class="jbw-overview-hint" id="booking-rental-hint">
                            {{ ($pricing['rental_days'] ?? $pricing['billing_days'] ?? 1) }} {{ Str::plural('day', $pricing['rental_days'] ?? $pricing['billing_days'] ?? 1) }}
                        </span>
                    </div>
                    <div class="jbw-booking-grid-2">
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
                <div class="jbw-overview-card">
                    <p class="jbw-overview-label">Event date (optional)</p>
                    <div class="jbw-field">
                        <label class="jbw-label" for="event_date">When do you need this?</label>
                        <input type="date" id="event_date" name="event_date" class="jbw-input" value="{{ old('event_date') }}" min="{{ now()->format('Y-m-d') }}">
                    </div>
                    @error('event_date')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
            @endif

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
                <div class="jbw-booking-grid-2" style="margin-top:1rem">
                    <div class="jbw-field">
                        <label class="jbw-label" for="city">City</label>
                        <input type="text" id="city" name="city" class="jbw-input" value="{{ old('city', $defaultAddress?->city ?? auth('customer')->user()->city) }}">
                    </div>
                    <div class="jbw-field">
                        <label class="jbw-label" for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode" class="jbw-input" value="{{ old('pincode', $defaultAddress?->pincode) }}" maxlength="10">
                    </div>
                </div>
                <p class="jbw-overview-foot-link"><a href="{{ route('web.profile.addresses') }}">Manage saved addresses</a></p>
            </div>

            @if ($measurement)
                @php
                    $fieldMap = \App\Support\WebMeasurementForm::labelToField();
                    $filledCount = 0;
                    foreach ($measurementSections as $fields) {
                        foreach ($fields as $label) {
                            $k = $fieldMap[$label] ?? null;
                            if ($k && ($measurementValues[$k] ?? null) !== null && ($measurementValues[$k] ?? '') !== '') {
                                $filledCount++;
                            }
                        }
                    }
                @endphp
                <div class="jbw-overview-card jbw-measure-block" data-expanded="false">
                    <div class="jbw-measure-head">
                        <span class="jbw-overview-label" style="margin:0">Measurements on file</span>
                        <span class="jbw-measure-head-summary">
                            <span class="jbw-measure-pill" data-measure-type-label>{{ ucfirst($measurement->measurement_type ?? '—') }}</span>
                            <span class="jbw-measure-count" data-measure-count>{{ $filledCount }} value{{ $filledCount === 1 ? '' : 's' }}</span>
                        </span>
                    </div>

                    @if (($measurementProfiles ?? collect())->count() > 1)
                        <div class="jbw-field jbw-measure-profile-select">
                            <label class="jbw-label" for="measurement_profile_id">Select measurement profile</label>
                            <select id="measurement_profile_id" name="measurement_profile_id" class="jbw-select" data-measure-profile-select>
                                @foreach ($measurementProfiles as $profile)
                                    <option value="{{ $profile->id }}" @selected($measurement->id === $profile->id)>
                                        {{ $profile->name ?: 'Profile #'.$profile->id }}@if ($profile->measurement_type) — {{ ucfirst($profile->measurement_type) }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="jbw-measure-body" data-measure-body>
                        @foreach ($measurementSections as $title => $fields)
                            <div class="jbw-measure-section">
                                <p class="jbw-measure-section-title">{{ $title }}</p>
                                <div class="jbw-measures" style="grid-template-columns:repeat(auto-fill,minmax(8rem,1fr))">
                                    @foreach ($fields as $label)
                                        @php $key = $fieldMap[$label]; @endphp
                                        <div class="jbw-measure">
                                            <span class="jbw-measure-label">{{ $label }}</span>
                                            <span class="jbw-measure-value" data-measure-key="{{ $key }}">{{ $measurementValues[$key] ?? '—' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="jbw-measure-actions-row">
                        <button type="button" class="jbw-measure-toggle-btn" data-measure-toggle aria-expanded="false">
                            <span data-measure-toggle-label>View all measurement details</span>
                            <span class="jbw-measure-chevron" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </span>
                        </button>
                        <a href="{{ route('web.profile.measurements.create', ['redirect' => request()->fullUrl()]) }}" data-save-draft class="jbw-measure-update-link">Update measurements</a>
                    </div>
                </div>
            @else
                <div class="jbw-overview-card">
                    <p class="jbw-overview-label">Measurements</p>
                    <p style="margin:0 0 0.75rem;color:var(--c-muted);font-size:0.875rem">Add measurements for a better fit before booking.</p>
                    <a href="{{ route('web.profile.measurements.create', ['redirect' => request()->fullUrl()]) }}" data-save-draft class="jbw-btn jbw-btn--outline jbw-btn--sm">Add measurements</a>
                </div>
            @endif

            <div class="jbw-overview-card" id="jbw-notes-refs-card">
                <p class="jbw-overview-label">Additional notes</p>
                <textarea name="customer_notes" class="jbw-textarea jbw-notes-textarea" placeholder="Any specific requirements or fitting instructions..." style="min-height:6rem">{{ old('customer_notes') }}</textarea>

                <div class="jbw-ref-images" data-ref-images data-max="5">
                    <div class="jbw-ref-images-head">
                        <p class="jbw-overview-label" style="margin:0">Reference images</p>
                        <span class="jbw-ref-images-hint">You can upload maximum 5 images</span>
                    </div>
                    <div class="jbw-ref-images-grid" data-ref-grid>
                        <label class="jbw-ref-add" data-ref-add>
                            <input type="file" name="reference_images[]" accept="image/png,image/jpeg,image/jpg,image/webp" multiple hidden data-ref-input>
                            <span class="jbw-ref-add-icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                    <circle cx="12" cy="13" r="4"/>
                                    <path d="M19 8v0"/>
                                </svg>
                                <span class="jbw-ref-add-plus">+</span>
                            </span>
                        </label>
                    </div>
                    @error('reference_images')<p class="jbw-field-error">{{ $message }}</p>@enderror
                    @error('reference_images.*')<p class="jbw-field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="jbw-booking-sidebar">
            <div class="jbw-overview-card jbw-overview-card--accent" id="booking-payment-summary">
                <p class="jbw-overview-label">Payment Summary</p>
                <div class="jbw-payment-lines" style="margin-bottom:0">
                    <div>
                        <span id="booking-rental-label">
                            @if ($item->requiresRentalPeriod())
                                Rental ({{ $pricing['rental_days'] ?? $pricing['billing_days'] ?? 1 }} {{ Str::plural('day', $pricing['rental_days'] ?? $pricing['billing_days'] ?? 1) }})
                            @else
                                Service amount
                            @endif
                        </span>
                        <span id="booking-line-subtotal">₹{{ number_format($pricing['subtotal'] ?? $item->rentalPriceAmount(), 0) }}</span>
                    </div>
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

    <aside class="jbw-booking-rail" aria-label="Booking sections">
        <button type="button" class="jbw-booking-rail-btn" data-scroll-to="jbw-notes-refs-card" title="Notes &amp; reference images" aria-label="Notes and reference images">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
        </button>
    </aside>
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
            const variant = form.querySelector('#jbw-variant-id')?.value
                || form.querySelector('input[name="portfolio_item_variant_id"]:checked')?.value
                || form.querySelector('input[name="portfolio_item_variant_id"]')?.value;
            if (start) body.append('rental_start_date', start);
            if (end) body.append('rental_end_date', end);
            if (variant) body.append('portfolio_item_variant_id', variant);
            body.append('shipment_required', '1');

            try {
                const res = await fetch(previewUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const { pricing } = await res.json();
                if (!pricing) return;

                const days = pricing.rental_days || pricing.billing_days || 1;
                const rentalLabel = document.getElementById('booking-rental-label');
                if (rentalLabel) {
                    rentalLabel.textContent = pricing.requires_rental_period
                        ? `Rental (${days} ${dayLabel(days)})`
                        : 'Service amount';
                }
                const hint = document.getElementById('booking-rental-hint');
                if (hint) hint.textContent = `${days} ${dayLabel(days)}`;
                document.getElementById('booking-line-subtotal').textContent = formatInr(pricing.subtotal);
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
        const start = startInput.value;
        if (start) {
            endInput.min = start;
            if (endInput.value && endInput.value < start) {
                endInput.value = '';
            }
        }
    };

    syncEndMin();

    startInput?.addEventListener('change', () => { syncEndMin(); refreshPreview(); });
    endInput?.addEventListener('change', refreshPreview);
    startInput?.addEventListener('input', () => { syncEndMin(); refreshPreview(); });
    endInput?.addEventListener('input', refreshPreview);

    form.addEventListener('jbw:draft-restored', () => {
        syncEndMin();
        refreshPreview();
    });

    form.querySelectorAll('input[name="portfolio_item_variant_id"]').forEach((input) => {
        input.addEventListener('change', refreshPreview);
    });

    const picker = document.getElementById('jbw-variant-picker');
    picker?.addEventListener('jbw:variant-changed', (event) => {
        const { label, image, variantLabel } = event.detail || {};
        const priceEl = document.getElementById('jbw-overview-price');
        const imgEl = document.getElementById('jbw-overview-img');
        const variantEl = document.getElementById('jbw-overview-variant');

        if (priceEl && label) priceEl.textContent = label;
        if (imgEl && image) imgEl.src = image;
        if (variantEl) variantEl.textContent = variantLabel || 'Base item';
        refreshPreview();
    });
})();
</script>
@endpush

@push('scripts')
@php
    $profileMeasurements = [];
    foreach (($measurementProfiles ?? collect()) as $p) {
        $profileMeasurements[$p->id] = [
            'type' => $p->measurement_type ? ucfirst($p->measurement_type) : '—',
            'values' => \App\Support\WebMeasurementForm::valuesFromProfile($p),
        ];
    }
@endphp
<script>
(function () {
    var select = document.querySelector('[data-measure-profile-select]');
    if (!select) return;

    var profiles = @json($profileMeasurements);

    function applyProfile(id) {
        var profile = profiles[id];
        if (!profile) return;

        document.querySelectorAll('[data-measure-key]').forEach(function (span) {
            var key = span.getAttribute('data-measure-key');
            var val = profile.values && profile.values[key];
            span.textContent = (val === null || val === undefined || val === '') ? '—' : val;
        });

        var typeLabel = document.querySelector('[data-measure-type-label]');
        if (typeLabel) typeLabel.textContent = 'Type: ' + profile.type;
    }

    function refreshCount() {
        var count = 0;
        document.querySelectorAll('[data-measure-key]').forEach(function (span) {
            var v = (span.textContent || '').trim();
            if (v && v !== '—') count++;
        });
        var el = document.querySelector('[data-measure-count]');
        if (el) el.textContent = count + ' value' + (count === 1 ? '' : 's');
    }

    select.addEventListener('change', function () {
        applyProfile(select.value);
        refreshCount();
    });
})();

(function () {
    var block = document.querySelector('.jbw-measure-block');
    if (!block) return;
    var toggle = block.querySelector('[data-measure-toggle]');
    if (!toggle) return;
    var labelEl = toggle.querySelector('[data-measure-toggle-label]');

    toggle.addEventListener('click', function () {
        var expanded = block.getAttribute('data-expanded') === 'true';
        var next = !expanded;
        block.setAttribute('data-expanded', next ? 'true' : 'false');
        toggle.setAttribute('aria-expanded', next ? 'true' : 'false');
        if (labelEl) {
            labelEl.textContent = next ? 'Hide measurement details' : 'View all measurement details';
        }
    });
})();

(function () {
    document.querySelectorAll('[data-scroll-to]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-scroll-to');
            var target = id ? document.getElementById(id) : null;
            if (!target) return;
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            target.classList.add('is-highlight');
            document.querySelectorAll('.jbw-booking-rail-btn').forEach(function (b) {
                b.classList.toggle('is-active', b === btn);
            });
            setTimeout(function () { target.classList.remove('is-highlight'); }, 1200);
        });
    });
})();

(function () {
    var root = document.querySelector('[data-ref-images]');
    if (!root) return;

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
        var incoming = Array.from(input.files || []);
        incoming.forEach(function (file) {
            if (files.length >= max) return;
            if (!file.type || file.type.indexOf('image/') !== 0) return;
            files.push(file);
        });
        activeIndex = Math.max(0, files.length - 1);
        syncInput();
        render();
    });
})();
</script>
@endpush

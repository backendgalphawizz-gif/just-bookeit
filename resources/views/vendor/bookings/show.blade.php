@extends('vendor.layouts.app')

@section('title', 'Booking '.$booking->order_number)

@section('content')
@php
    $checkout = $booking->checkoutOrder;
    $displayNumber = $booking->sub_order_number ?: $booking->order_number;
    $listStatus = \App\Support\Api\VendorBookingListStatus::resolve($booking);
    $statusBadge = match ($listStatus['status']) {
        'new' => 'new',
        'processing', 'pending' => 'accepted',
        'complete' => 'done',
        'cancelled' => 'cancelled',
        default => 'accepted',
    };
    $paymentBadge = match ($booking->payment_status) {
        'paid' => 'done',
        'failed' => 'failed',
        'refunded' => 'cancelled',
        default => 'pending',
    };
@endphp

<a href="{{ route('vendor.bookings.index') }}" class="vp-back-link">← Back to bookings</a>

<div class="vp-booking-header">
    <div>
        <p class="vp-booking-id">#{{ $displayNumber }}</p>
        @if ($checkout)
            <p class="vp-booking-checkout-ref">
                Part of checkout <strong>#{{ $checkout->order_number }}</strong>
            </p>
        @endif
        <div class="vp-booking-header-badges">
            <span class="vp-type-pill">{{ $booking->orderTypeLabel() }}</span>
            <span class="vp-badge vp-badge--{{ $statusBadge }}">{{ $listStatus['status_label'] }}</span>
        </div>
    </div>
    <p class="vp-booking-booked-on">Booked {{ $booking->created_at->format('M d, Y · H:i') }}</p>
</div>

<div class="vp-booking-layout">
    <div class="vp-booking-main">
        {{-- Product --}}
        <div class="vp-booking-card">
            <h3 class="vp-booking-card-title">Product detail</h3>
            @if ($booking->orderItems->isNotEmpty())
                <div class="vp-booking-line-items">
                    @foreach ($booking->orderItems as $lineItem)
                        @php
                            $lineImage = $lineItem->displayImageUrl()
                                ?: $lineItem->portfolioItem?->displayImageUrl();
                            $variantLabel = $lineItem->variantLabel();
                        @endphp
                        <div class="vp-booking-product-row">
                            <div class="vp-booking-product-media">
                                @if ($lineImage)
                                    <img src="{{ $lineImage }}" alt="" class="vp-booking-product-img">
                                @else
                                    <div class="vp-booking-product-placeholder" aria-hidden="true">📦</div>
                                @endif
                            </div>
                            <div class="vp-booking-product-info">
                                <p class="vp-booking-product-name">{{ $lineItem->title() }}</p>
                                <p class="vp-booking-product-meta">
                                    Qty {{ $lineItem->quantity }} · ₹{{ number_format($lineItem->line_amount, 0) }}
                                    @if ($variantLabel) · {{ $variantLabel }}@endif
                                    · {{ $lineItem->statusLabel() }}
                                    @if ($lineItem->damageDeduction() > 0)
                                        <span style="color:#b91c1c;font-weight:700"> · Damage −₹{{ number_format($lineItem->damageDeduction(), 2) }}</span>
                                    @endif
                                </p>
                                @if (filled($lineItem->damage_note) || $lineItem->damage_amount !== null || $lineItem->damage_deduct_percent !== null)
                                    <p class="vp-booking-product-meta" style="color:#b91c1c;margin-top:.25rem">
                                        Damage: {{ $lineItem->damage_note ?: 'Deduction applied' }}
                                        @if ($lineItem->damage_deduct_percent !== null)
                                            ({{ rtrim(rtrim(number_format((float) $lineItem->damage_deduct_percent, 2), '0'), '.') }}%)
                                        @endif
                                    </p>
                                @endif
                                @if ($lineItem->canAccept() || $lineItem->canReject())
                                    <div class="vp-booking-line-actions" style="display:flex;gap:.5rem;margin-top:.5rem;flex-wrap:wrap;">
                                        @if ($lineItem->canAccept())
                                            <form method="POST" action="{{ route('vendor.bookings.items.accept', [$booking, $lineItem]) }}">
                                                @csrf
                                                <button type="submit" class="vp-btn vp-btn--sm vp-btn--primary">Accept item</button>
                                            </form>
                                        @endif
                                        @if ($lineItem->canReject())
                                            <form method="POST" action="{{ route('vendor.bookings.items.reject', [$booking, $lineItem]) }}"
                                                  data-vp-confirm="Reject this item? A partial refund may be issued."
                                                  onsubmit="var r=prompt('Reason for rejecting this item (required):'); if(!r||r.trim().length<5){event.preventDefault();alert('Please enter a reason (at least 5 characters).');return false;} this.querySelector('[name=reason]').value=r.trim();">
                                                @csrf
                                                <input type="hidden" name="reason" value="">
                                                <button type="submit" class="vp-btn vp-btn--sm vp-btn--outline">Reject item</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="vp-booking-product-row">
                    <div class="vp-booking-product-media">
                        @if ($booking->itemImageUrl())
                            <img src="{{ $booking->itemImageUrl() }}" alt="" class="vp-booking-product-img">
                        @else
                            <div class="vp-booking-product-placeholder" aria-hidden="true">📦</div>
                        @endif
                    </div>
                    <div class="vp-booking-product-info">
                        <p class="vp-booking-product-name">{{ $booking->itemDisplayName() }}</p>
                        <p class="vp-booking-product-meta">
                            @if ($booking->category){{ $booking->category->name }}@endif
                            @if ($booking->color) · {{ $booking->color }}@endif
                            @if ($booking->size) · Size {{ $booking->size }}@endif
                        </p>
                        @if ($booking->isRental() && ($booking->rental_start_date || $booking->rental_end_date))
                            <p class="vp-booking-product-meta">
                                Rental:
                                {{ $booking->rental_start_date?->format('M d') ?? '—' }}
                                – {{ $booking->rental_end_date?->format('M d, Y') ?? '—' }}
                                @if ($booking->rentalDurationDays()) ({{ $booking->rentalDurationDays() }} days) @endif
                            </p>
                        @endif
                        <p class="vp-booking-product-price">₹{{ number_format($booking->amount, 0) }}</p>
                        <p class="vp-booking-product-qty">Qty — {{ $booking->quantity ?? 1 }}</p>
                    </div>
                </div>
            @endif
        </div>

        @include('vendor.bookings.partials.rent-tracking')

        <div @class(['vp-booking-split', 'vp-booking-split--single' => ! $booking->isRental()])>
            <div class="vp-booking-card vp-booking-card--compact">
                <h3 class="vp-booking-card-title">Customer</h3>
                @if ($booking->customer)
                    <div class="vp-booking-person">
                        <div class="vp-booking-person-avatar">{{ strtoupper(substr($booking->customer->name ?? 'C', 0, 1)) }}</div>
                        <div class="vp-booking-person-info">
                            <p class="vp-booking-person-name">{{ $booking->customer->name }}</p>
                            <p class="vp-booking-person-meta">{{ $booking->customer->mobile ?? '—' }}</p>
                        </div>
                        @if ($booking->customer->mobile)
                            <a href="tel:{{ $booking->customer->mobile }}" class="vp-booking-call-btn" title="Call customer">📞</a>
                        @endif
                    </div>
                @else
                    <p class="vp-booking-muted">No customer on file</p>
                @endif
            </div>
            @if ($booking->isRental())
                <div class="vp-booking-card vp-booking-card--compact">
                    <h3 class="vp-booking-card-title">Rental period</h3>
                    @if ($booking->rental_start_date || $booking->rental_end_date)
                        <p class="vp-booking-rental-dates">
                            {{ $booking->rental_start_date?->format('d M') ?? '—' }}
                            – {{ $booking->rental_end_date?->format('d M') ?? '—' }}
                        </p>
                        <p class="vp-booking-rental-days">{{ $booking->rentalDurationDays() ?? '—' }} days duration</p>
                    @else
                        <p class="vp-booking-muted">Dates not set</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="vp-booking-card">
            <h3 class="vp-booking-card-title">📍 Delivery address</h3>
            <p class="vp-booking-address-name">{{ $booking->customer?->name ?? 'Customer' }}</p>
            <p class="vp-booking-address-text">{{ $booking->delivery_address ?? '—' }}</p>
            @if ($booking->city || $booking->pincode)
                <p class="vp-booking-address-text">{{ $booking->city }}@if($booking->pincode), {{ $booking->pincode }}@endif</p>
            @endif
        </div>

        <div class="vp-booking-card">
            <h3 class="vp-booking-card-title">Customer measurements</h3>
            @php
                $checkout = $booking->checkoutOrder;
                $measureHeight = $booking->measure_height_cm ?? $checkout?->measure_height_cm;
                $measureChest = $booking->measure_chest_cm ?? $checkout?->measure_chest_cm;
                $measureWaist = $booking->measure_waist_cm ?? $checkout?->measure_waist_cm;
                $fieldMap = \App\Support\WebMeasurementForm::labelToField();
                $hasProfileMeasures = collect($measurementValues ?? [])->filter(fn ($v) => filled($v))->isNotEmpty();
            @endphp

            @if ($measureHeight || $measureChest || $measureWaist)
                <div class="vp-measure-section">
                    <p class="vp-measure-section-title">Body (cm)</p>
                    <div class="vp-booking-measures vp-booking-measures--grid">
                        <div class="vp-booking-measure">
                            <span class="vp-booking-measure-label">Height</span>
                            <span class="vp-booking-measure-value">{{ $measureHeight ? $measureHeight.' cm' : '—' }}</span>
                        </div>
                        <div class="vp-booking-measure">
                            <span class="vp-booking-measure-label">Chest</span>
                            <span class="vp-booking-measure-value">{{ $measureChest ? $measureChest.' cm' : '—' }}</span>
                        </div>
                        <div class="vp-booking-measure">
                            <span class="vp-booking-measure-label">Waist</span>
                            <span class="vp-booking-measure-value">{{ $measureWaist ? $measureWaist.' cm' : '—' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            @if ($hasProfileMeasures)
                @foreach ($measurementSections as $title => $fields)
                    @php
                        $sectionValues = collect($fields)->filter(fn ($label) => filled($measurementValues[$fieldMap[$label]] ?? null));
                    @endphp
                    @if ($sectionValues->isNotEmpty())
                        <div class="vp-measure-section">
                            <p class="vp-measure-section-title">{{ $title }}</p>
                            <div class="vp-booking-measures vp-booking-measures--grid">
                                @foreach ($fields as $label)
                                    @php $key = $fieldMap[$label]; $val = $measurementValues[$key] ?? null; @endphp
                                    @if (filled($val))
                                        <div class="vp-booking-measure">
                                            <span class="vp-booking-measure-label">{{ $label }}</span>
                                            <span class="vp-booking-measure-value">{{ $val }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            @elseif (! $measureHeight && ! $measureChest && ! $measureWaist)
                <p class="vp-booking-muted">No measurements provided for this booking.</p>
            @endif
        </div>

        @if ($booking->customer_notes)
            <div class="vp-booking-card">
                <h3 class="vp-booking-card-title">Customer notes</h3>
                <p class="vp-booking-notes">{{ $booking->customer_notes }}</p>
            </div>
        @endif

        @if (count($booking->referenceImageUrls()) > 0)
            <div class="vp-booking-card">
                <h3 class="vp-booking-card-title">Reference images</h3>
                <div class="vp-booking-ref-grid">
                    @foreach ($booking->referenceImageUrls() as $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="vp-booking-ref-thumb">
                            <img src="{{ $url }}" alt="Reference image">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($booking->driver)
            <div class="vp-booking-card">
                <h3 class="vp-booking-card-title">Delivery driver</h3>
                <div class="vp-booking-person">
                    <div class="vp-booking-person-avatar">{{ strtoupper(substr($booking->driver->name ?? 'D', 0, 1)) }}</div>
                    <div class="vp-booking-person-info">
                        <p class="vp-booking-person-name">{{ $booking->driver->name }}</p>
                        <p class="vp-booking-person-meta">{{ $booking->driver->vehicle_no ?? 'No vehicle' }} · {{ $booking->driver->mobile }}</p>
                    </div>
                    <a href="tel:{{ $booking->driver->mobile }}" class="vp-booking-call-btn" title="Call driver">📞</a>
                </div>
                @if ($booking->deliveryProofImageUrl())
                    <div class="vp-booking-proof">
                        <span class="vp-booking-measure-label">Delivery proof</span>
                        <a href="{{ $booking->deliveryProofImageUrl() }}" target="_blank" rel="noopener">
                            <img src="{{ $booking->deliveryProofImageUrl() }}" alt="Delivery proof" class="vp-booking-proof-img">
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <aside class="vp-booking-sidebar">
        <div class="vp-booking-card">
            <h3 class="vp-booking-card-title">{{ $booking->isRental() ? 'Delivery tracking' : 'Track booking' }}</h3>
            @if ($booking->orderItems->isNotEmpty())
                <div class="vp-item-tracks">
                    @foreach ($booking->orderItems as $lineItem)
                        <div class="vp-item-track">
                            <div class="vp-item-track-head">
                                <p class="vp-item-track-title">{{ $lineItem->title() }}</p>
                                <p class="vp-item-track-status">{{ $lineItem->statusLabel() }}</p>
                            </div>
                            @if ($lineItem->driver)
                                <p class="vp-item-track-driver">Driver: {{ $lineItem->driver->name }}</p>
                            @endif
                            <ol class="vp-booking-track">
                                @foreach ($lineItem->trackSteps() as $step)
                                    <li class="vp-booking-track-step vp-booking-track-step--{{ $step['state'] }}">
                                        <span class="vp-booking-track-marker" aria-hidden="true">
                                            @if ($step['state'] === 'done')
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            @endif
                                        </span>
                                        <div class="vp-booking-track-body">
                                            <p class="vp-booking-track-label">{{ $step['label'] }}</p>
                                            @if ($step['time'])
                                                <p class="vp-booking-track-time">{{ $step['time'] }}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                </div>
            @else
                <ol class="vp-booking-track">
                    @foreach ($booking->trackBookingSteps() as $step)
                        <li class="vp-booking-track-step vp-booking-track-step--{{ $step['state'] }}">
                            <span class="vp-booking-track-marker" aria-hidden="true">
                                @if ($step['state'] === 'done')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </span>
                            <div class="vp-booking-track-body">
                                <p class="vp-booking-track-label">{{ $step['label'] }}</p>
                                @if ($step['time'])
                                    <p class="vp-booking-track-time">{{ $step['time'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

        <div class="vp-booking-card">
            <h3 class="vp-booking-card-title">Billing address</h3>
            <p class="vp-booking-address-name">{{ $booking->customer?->name ?? 'Customer' }}</p>
            <p class="vp-booking-address-text">{{ $booking->billing_address ?? $booking->delivery_address ?? '—' }}</p>
        </div>

        <div class="vp-booking-card vp-booking-payment">
            <h3 class="vp-booking-card-title">Payment summary</h3>
            <dl class="vp-booking-payment-lines">
                <div><dt>Subtotal</dt><dd>₹{{ number_format($booking->subtotal(), 0) }}</dd></div>
                @if ($booking->damageDeduction() > 0)
                    <div class="vp-booking-payment-damage">
                        <dt>
                            Damage deduction
                            @if ($booking->damage_note)
                                <span style="display:block;font-weight:500;font-size:.75rem;margin-top:.15rem">{{ $booking->damage_note }}@if($booking->damage_deduct_percent !== null) ({{ rtrim(rtrim(number_format((float) $booking->damage_deduct_percent, 2), '0'), '.') }}%)@endif</span>
                            @elseif ($booking->damage_deduct_percent !== null)
                                <span style="display:block;font-weight:500;font-size:.75rem;margin-top:.15rem">{{ rtrim(rtrim(number_format((float) $booking->damage_deduct_percent, 2), '0'), '.') }}%</span>
                            @endif
                        </dt>
                        <dd>− ₹{{ number_format($booking->damageDeduction(), 2) }}</dd>
                    </div>
                @endif
                <div><dt>Delivery fee</dt><dd>₹{{ number_format($booking->delivery_fee ?? 0, 0) }}</dd></div>
                <div><dt>Tax (GST)</dt><dd>₹{{ number_format($booking->tax_amount ?? 0, 0) }}</dd></div>
                @if ($booking->security_deposit)
                    <div><dt>Security deposit</dt><dd>₹{{ number_format($booking->security_deposit, 0) }}</dd></div>
                @endif
                @if ($booking->isRental() && $booking->rentalDurationDays())
                    <div><dt>Rental duration</dt><dd>{{ $booking->rentalDurationDays() }} days</dd></div>
                @endif
            </dl>
            <div class="vp-booking-payment-total">
                <span>Your order total</span>
                <strong>₹{{ number_format($booking->grandTotal(), 0) }}</strong>
            </div>
            <div style="margin-top:.75rem">
                <span class="vp-badge vp-badge--{{ $paymentBadge }}">{{ ucfirst($booking->payment_status) }}</span>
                @if ($booking->payment_method)
                    <span class="vp-booking-muted" style="margin-left:.5rem;font-size:.8rem">{{ strtoupper($booking->payment_method) }}</span>
                @endif
            </div>
        </div>

        @if ($booking->damageDeduction() > 0 || $booking->damage_note || $booking->orderItems->contains(fn ($i) => $i->hasDamageRecord()))
            <div class="vp-booking-card">
                <h3 class="vp-booking-card-title">Damage</h3>
                @if ($booking->orderItems->contains(fn ($i) => $i->hasDamageRecord()))
                    @foreach ($booking->orderItems->filter(fn ($i) => $i->hasDamageRecord()) as $damageItem)
                        <p class="vp-booking-notes" style="{{ ! $loop->first ? 'margin-top:.5rem' : '' }}">
                            <strong>{{ $damageItem->title() }}:</strong>
                            {{ $damageItem->damage_note ?? '—' }}
                            — ₹{{ number_format($damageItem->damageDeduction(), 2) }}
                            @if ($damageItem->damage_deduct_percent !== null)
                                ({{ $damageItem->damage_deduct_percent }}%)
                            @endif
                        </p>
                    @endforeach
                @else
                    <p class="vp-booking-notes">{{ $booking->damage_note ?? '—' }} — ₹{{ number_format($booking->damageDeduction(), 2) }}@if($booking->damage_deduct_percent !== null) ({{ $booking->damage_deduct_percent }}%)@endif</p>
                @endif
            </div>
        @endif

        @if ($booking->refund || $booking->dispute)
            <div class="vp-booking-card">
                <h3 class="vp-booking-card-title">Related</h3>
                @if ($booking->refund)
                    <p class="vp-booking-notes">Refund — <strong>{{ ucfirst($booking->refund->status) }}</strong></p>
                @endif
                @if ($booking->dispute)
                    <p class="vp-booking-notes" style="margin-top:.5rem">{{ $booking->dispute->subject }}</p>
                @endif
            </div>
        @endif

        @if (in_array($booking->status, ['new', 'pending_acceptance'], true))
            <div class="vp-booking-card">
                <h3 class="vp-booking-card-title">Respond to booking</h3>
                <div class="vp-booking-actions">
                    <form method="POST" action="{{ route('vendor.bookings.accept', $booking) }}">@csrf
                        <button type="submit" class="vp-btn vp-btn--primary vp-btn--block">Accept booking</button>
                    </form>
                    <form method="POST" action="{{ route('vendor.bookings.reject', $booking) }}"
                          data-vp-confirm="This booking will be rejected."
                          data-vp-confirm-title="Reject booking?"
                          data-vp-confirm-label="Reject"
                          data-vp-confirm-variant="error">@csrf
                        <button type="submit" class="vp-btn vp-btn--danger vp-btn--block">Reject</button>
                    </form>
                </div>
            </div>
        @endif

        @if (count($quickActions) > 0)
            <div class="vp-booking-card">
                <h3 class="vp-booking-card-title">Quick actions</h3>
                <div class="vp-booking-actions">
                    @foreach ($quickActions as $action)
                        <form method="POST" action="{{ route('vendor.bookings.status', $booking) }}">@csrf
                            <input type="hidden" name="status" value="{{ $action['status'] }}">
                            <button type="submit" class="vp-btn vp-btn--{{ $action['variant'] ?? 'outline' }} vp-btn--block">{{ $action['label'] }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="vp-booking-card">
            <h3 class="vp-booking-card-title">Update status</h3>
            <form method="POST" action="{{ route('vendor.bookings.status', $booking) }}" class="vp-booking-manage-form">
                @csrf
                <label class="vp-label" for="booking-status">Order status</label>
                <select id="booking-status" name="status" class="vp-select">
                    @foreach ($manageableStatuses as $status)
                        <option value="{{ $status }}" @selected($booking->status === $status)>{{ \App\Models\Order::statusLabelFor($status) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="vp-btn vp-btn--outline vp-btn--block" style="margin-top:.75rem">Save status</button>
            </form>
        </div>

        @php
            $returnedItems = $booking->orderItems->where('status', 'returned')->values();
            $canRecordDamage = $returnedItems->isNotEmpty()
                || ($booking->orderItems->isEmpty() && $booking->status === 'returned');
        @endphp
        @if ($canRecordDamage)
            <div class="vp-booking-card">
                <h3 class="vp-booking-card-title">Damage deduction</h3>
                @if ($returnedItems->isNotEmpty())
                    @foreach ($returnedItems as $damageItem)
                        <form method="POST" action="{{ route('vendor.bookings.damage', $booking) }}" class="vp-booking-manage-form" style="{{ ! $loop->first ? 'margin-top:1rem;padding-top:1rem;border-top:1px solid var(--vp-border)' : '' }}">
                            @csrf
                            <input type="hidden" name="item_id" value="{{ $damageItem->id }}">
                            <p class="vp-booking-muted" style="margin:0 0 .5rem;font-weight:700;color:var(--vp-text)">{{ $damageItem->title() }}</p>
                            <label class="vp-label" for="damage_amount_{{ $damageItem->id }}">Deduction amount (₹)</label>
                            <input id="damage_amount_{{ $damageItem->id }}" type="number" min="0" step="0.01" name="damage_amount"
                                   class="vp-input" value="{{ old('damage_amount', $damageItem->damage_amount) }}" placeholder="e.g. 28">
                            <label class="vp-label" for="damage_note_{{ $damageItem->id }}" style="margin-top:.75rem">Reason</label>
                            <textarea id="damage_note_{{ $damageItem->id }}" name="damage_note" class="vp-textarea" rows="3" placeholder="Describe damage">{{ old('damage_note', $damageItem->damage_note) }}</textarea>
                            <button type="submit" class="vp-btn vp-btn--outline vp-btn--block" style="margin-top:.75rem">Save damage</button>
                        </form>
                    @endforeach
                @else
                    <form method="POST" action="{{ route('vendor.bookings.damage', $booking) }}" class="vp-booking-manage-form">
                        @csrf
                        <label class="vp-label" for="damage_amount">Deduction amount (₹)</label>
                        <input id="damage_amount" type="number" min="0" step="0.01" name="damage_amount"
                               class="vp-input" value="{{ old('damage_amount', $booking->damage_amount) }}" placeholder="e.g. 28">
                        <label class="vp-label" for="damage_note" style="margin-top:.75rem">Reason</label>
                        <textarea id="damage_note" name="damage_note" class="vp-textarea" rows="3" placeholder="Describe damage">{{ old('damage_note', $booking->damage_note) }}</textarea>
                        <button type="submit" class="vp-btn vp-btn--outline vp-btn--block" style="margin-top:.75rem">Save damage</button>
                    </form>
                @endif
            </div>
        @endif
    </aside>
</div>
@endsection

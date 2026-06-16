@extends('web.layouts.app')

@section('title', 'Booking '.$order->order_number)

@section('content')
<div class="jbw-container">
    <p style="margin-bottom:1rem"><a href="{{ route('web.bookings.index') }}" class="jbw-back-link">← Booking detail</a></p>

    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:0.75rem;margin-bottom:1.25rem">
        <div>
            <h1 class="jbw-page-title" style="font-size:1.25rem;margin:0">#{{ $order->order_number }}</h1>
            <p class="jbw-page-subtitle">Booked on {{ $order->created_at->format('M d, Y · H:i') }}</p>
        </div>
        @php
            $statusClass = match ($order->status) {
                'new', 'pending_acceptance' => 'new',
                'in_transit' => 'in_transit',
                'delivered' => 'delivered',
                'cancelled', 'refunded' => 'cancelled',
                default => 'default',
            };
        @endphp
        <span class="jbw-status jbw-status--{{ $statusClass }}">{{ $order->statusLabel() }}</span>
    </div>

    <div class="jbw-booking-layout">
        <div class="jbw-booking-main">
            <div class="jbw-booking-card">
                <h3 class="jbw-booking-card-title">Product detail</h3>
                <div class="jbw-booking-product-row">
                    @if ($order->itemImageUrl())
                        <img src="{{ $order->itemImageUrl() }}" alt="" class="jbw-booking-product-img">
                    @endif
                    <div>
                        <p class="jbw-booking-product-name">{{ $order->itemDisplayName() }}</p>
                        <p class="jbw-booking-product-meta">
                            @if ($order->color){{ $order->color }}@endif
                            @if ($order->size) | Size: {{ $order->size }}@endif
                        </p>
                        @if ($order->isRental() && $order->rental_start_date)
                            <p class="jbw-booking-product-meta">Rental: {{ $order->rental_start_date->format('M d') }} – {{ $order->rental_end_date?->format('M d, Y') }}</p>
                        @endif
                        <p class="jbw-booking-product-price">₹{{ number_format($order->amount, 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="jbw-booking-split">
                <div class="jbw-booking-card">
                    <h3 class="jbw-booking-card-title">Designer</h3>
                    @if ($order->vendor)
                        <strong>{{ $order->vendor->brand_name }}</strong>
                        <p class="jbw-booking-product-meta">★ {{ number_format($order->vendor->rating, 1) }} · {{ $order->vendor->city }}</p>
                    @else
                        <p class="jbw-booking-product-meta">Not assigned</p>
                    @endif
                </div>
                @if ($order->isRental())
                    <div class="jbw-booking-card">
                        <h3 class="jbw-booking-card-title">Rental period</h3>
                        @if ($order->rental_start_date)
                            <p style="font-weight:800;margin:0">{{ $order->rental_start_date->format('d M') }} – {{ $order->rental_end_date?->format('d M') }}</p>
                            <p class="jbw-booking-product-meta">{{ $order->rentalDurationDays() }} days duration</p>
                        @else
                            <p class="jbw-booking-product-meta">—</p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="jbw-booking-card">
                <h3 class="jbw-booking-card-title">Shipping address</h3>
                <p style="font-weight:700;margin:0">{{ $order->customer->name }}</p>
                <p class="jbw-booking-product-meta">{{ $order->delivery_address ?? '—' }}</p>
            </div>

            @if ($order->measure_height_cm || $order->measure_chest_cm || $order->measure_waist_cm)
                <div class="jbw-booking-card">
                    <h3 class="jbw-booking-card-title">Measurements</h3>
                    <div class="jbw-measures">
                        <div class="jbw-measure"><span class="jbw-measure-label">Height</span><span class="jbw-measure-value">{{ $order->measure_height_cm ?? '—' }} cm</span></div>
                        <div class="jbw-measure"><span class="jbw-measure-label">Chest</span><span class="jbw-measure-value">{{ $order->measure_chest_cm ?? '—' }} cm</span></div>
                        <div class="jbw-measure"><span class="jbw-measure-label">Waist</span><span class="jbw-measure-value">{{ $order->measure_waist_cm ?? '—' }} cm</span></div>
                    </div>
                </div>
            @endif

            @if ($order->customer_notes)
                <div class="jbw-booking-card">
                    <h3 class="jbw-booking-card-title">Custom notes</h3>
                    <p style="margin:0;line-height:1.6;color:var(--jbw-muted)">{{ $order->customer_notes }}</p>
                </div>
            @endif

            @if (count($order->referenceImageUrls()) > 0)
                <div class="jbw-booking-card">
                    <h3 class="jbw-booking-card-title">Reference images</h3>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
                        @foreach ($order->referenceImageUrls() as $url)
                            <img src="{{ $url }}" alt="" style="width:4.5rem;height:4.5rem;border-radius:0.5rem;object-fit:cover">
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="jbw-booking-sidebar">
            <div class="jbw-booking-card">
                <h3 class="jbw-booking-card-title">Track booking</h3>
                <ol class="jbw-booking-track">
                    @foreach ($order->trackBookingSteps() as $step)
                        <li class="jbw-booking-track-step jbw-booking-track-step--{{ $step['state'] }}">
                            <span class="jbw-booking-track-marker"></span>
                            <div>
                                <p class="jbw-booking-track-label">{{ $step['label'] }}</p>
                                @if ($step['time'])<p class="jbw-booking-track-time">{{ $step['time'] }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="jbw-booking-card">
                <h3 class="jbw-booking-card-title">Payment summary</h3>
                <div class="jbw-payment-lines">
                    <div><span>Subtotal</span><span>₹{{ number_format($order->subtotal(), 0) }}</span></div>
                    @if ($order->damageDeduction() > 0)
                        <div><span>Damage deduction</span><span>- ₹{{ number_format($order->damageDeduction(), 0) }}</span></div>
                    @endif
                    <div><span>Shipping</span><span>₹{{ number_format($order->delivery_fee ?? 0, 0) }}</span></div>
                    <div><span>Tax (GST)</span><span>₹{{ number_format($order->tax_amount ?? 0, 0) }}</span></div>
                </div>
                <div class="jbw-payment-total"><span>Total amount</span><strong>₹{{ number_format($order->grandTotal(), 0) }}</strong></div>
            </div>

            <div class="jbw-booking-card">
                <h3 class="jbw-booking-card-title">Dispute</h3>
                @if ($order->category)
                    <p class="jbw-booking-product-meta" style="margin-bottom:0.75rem">Category: <strong>{{ $order->category->name }}</strong></p>
                @endif

                @if ($order->dispute)
                    <p style="margin:0 0 0.75rem;line-height:1.5;color:var(--jbw-muted)">{{ $order->dispute->subject }}</p>
                    <a href="{{ route('web.bookings.dispute.show', $order) }}" class="jbw-btn jbw-btn--primary jbw-btn--block">
                        {{ $order->dispute->isChatOpen() ? 'Open dispute chat' : 'View dispute' }}
                    </a>
                @else
                    <form method="POST" action="{{ route('web.bookings.dispute.store', $order) }}" style="display:grid;gap:0.75rem">
                        @csrf
                        <div>
                            <label for="dispute-subject" style="display:block;font-size:0.75rem;font-weight:700;margin-bottom:0.35rem">Issue type</label>
                            <select id="dispute-subject" name="subject" class="jbw-input" style="width:100%" required>
                                <option value="">Select an issue</option>
                                @foreach (\App\Models\Dispute::subjectOptionsForCategory($order->category) as $subject)
                                    <option value="{{ $subject }}" @selected(old('subject') === $subject)>{{ $subject }}</option>
                                @endforeach
                            </select>
                            @error('subject')
                                <p style="margin:0.35rem 0 0;font-size:0.75rem;color:#e11d48">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="dispute-body" style="display:block;font-size:0.75rem;font-weight:700;margin-bottom:0.35rem">Details (optional)</label>
                            <textarea id="dispute-body" name="body" rows="3" class="jbw-input" style="width:100%;resize:vertical" placeholder="Tell us what went wrong...">{{ old('body') }}</textarea>
                        </div>
                        <button type="submit" class="jbw-btn jbw-btn--danger jbw-btn--block">Raise dispute</button>
                    </form>
                @endif
            </div>

            @if (in_array($order->status, ['new', 'pending_acceptance'], true))
                <div class="jbw-booking-card">
                    <h3 class="jbw-booking-card-title">Cancel booking</h3>
                    <form method="POST" action="{{ route('web.bookings.cancel', $order) }}" style="display:grid;gap:0.75rem">
                        @csrf
                        <div>
                            <label for="cancel-reason" style="display:block;font-size:0.75rem;font-weight:700;margin-bottom:0.35rem">Reason</label>
                            <textarea id="cancel-reason" name="reason" rows="3" class="jbw-input" style="width:100%;resize:vertical" placeholder="Why are you cancelling?" required minlength="5">{{ old('reason') }}</textarea>
                            @error('reason')<p style="margin:0.35rem 0 0;font-size:0.75rem;color:#e11d48">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="jbw-btn jbw-btn--danger jbw-btn--block" onclick="return confirm('Cancel this booking?')">Cancel booking</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

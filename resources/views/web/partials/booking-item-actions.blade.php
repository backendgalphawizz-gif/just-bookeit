@php
    /** @var \App\Models\Order $order */
    /** @var \App\Models\OrderItem|null $orderItem */
    $orderItem = $orderItem ?? null;
    $order->loadMissing(['orderItems.review', 'reviews']);

    $hasLineItems = $order->orderItems->isNotEmpty();

    $canConfirm = ! $orderItem && $order->status === 'delivered';
    $canReturn = $orderItem
        ? \App\Support\WebBookingStatus::itemCanRequestProductReturn($orderItem, $order)
        : (! $hasLineItems && \App\Support\WebBookingStatus::bookingCanRequestProductReturn($order));
    $canRework = $orderItem
        ? \App\Support\FashionDesignerLifecycleSupport::canCustomerRequestRework($orderItem, $order)
        : (! $hasLineItems && in_array($order->status, ['delivered', 'rental_active', 're_delivered'], true));
    $canReviewItem = $orderItem
        && in_array($orderItem->status, \App\Models\OrderReview::reviewableStatuses(), true)
        && ! $orderItem->review;
    $canReviewBooking = ! $orderItem
        && ! $hasLineItems
        && \App\Support\WebBookingStatus::bookingCanReview($order);
    $reworkFields = $orderItem
        ? \App\Support\FashionDesignerLifecycleSupport::apiFields($orderItem, $order)
        : [];
    $otp = null;
    $itemInDispatch = $orderItem && in_array($orderItem->statusForDisplay($order), ['in_progress', 're_intransit'], true);
    $bookingInDispatch = ! $orderItem && (
        in_array($order->status, ['in_progress', 're_intransit'], true)
        || $order->orderItems->contains(fn ($item) => in_array($item->statusForDisplay($order), ['in_progress', 're_intransit'], true))
    );
    if ($itemInDispatch || $bookingInDispatch) {
        $otp = $order->ensureDeliveryOtp();
    }
    $hasActions = $canConfirm || $canReturn || $canRework || $canReviewItem || $canReviewBooking || $otp
        || ($orderItem && $orderItem->review);
@endphp

@if ($hasActions)
    <div class="jbw-booking-item-actions">
        @if ($otp)
            <div class="jbw-booking-otp">
                <span class="jbw-booking-otp-label">Delivery OTP</span>
                <strong class="jbw-booking-otp-code">{{ $otp }}</strong>
                <span class="jbw-booking-otp-hint">Share with the driver on delivery / pickup.</span>
            </div>
        @endif

        @if ($canConfirm)
            <form method="POST" action="{{ route('web.bookings.confirm-received', $order) }}" class="jbw-booking-action-form">
                @csrf
                <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--sm jbw-btn--block">Confirm received</button>
            </form>
        @endif

        @if ($canReturn)
            <form method="POST" action="{{ route('web.bookings.request-return', $order) }}" class="jbw-booking-action-form">
                @csrf
                @if ($orderItem)
                    <input type="hidden" name="item_id" value="{{ $orderItem->id }}">
                @endif
                <button type="submit" class="jbw-btn jbw-btn--outline jbw-btn--sm jbw-btn--block" onclick="return confirm('Request return pickup for this rented item?')">
                    Request return pickup
                </button>
                <p class="jbw-booking-action-hint">Returns rented dress/jewellery to the vendor — not a dispute.</p>
            </form>
        @endif

        @if ($canRework)
            <form method="POST" action="{{ route('web.bookings.request-rework', $order) }}" class="jbw-booking-action-form">
                @csrf
                @if ($orderItem)
                    <input type="hidden" name="item_id" value="{{ $orderItem->id }}">
                @endif
                @if (! empty($reworkFields['rework_deadline_label']))
                    <p class="jbw-booking-action-hint">Rework window open until {{ $reworkFields['rework_deadline_label'] }}.</p>
                @endif
                <div class="jbw-field">
                    <label class="jbw-label" for="rework-reason-{{ $orderItem?->id ?? 'booking-'.$order->id }}">Rework reason <span class="jbw-label-optional">(optional)</span></label>
                    <textarea id="rework-reason-{{ $orderItem?->id ?? 'booking-'.$order->id }}" name="reason" rows="2" class="jbw-textarea" placeholder="Describe the fitting or issue..."></textarea>
                </div>
                <button type="submit" class="jbw-btn jbw-btn--outline jbw-btn--sm jbw-btn--block">Request rework</button>
            </form>
        @endif

        @if ($orderItem && $orderItem->review)
            <div class="jbw-booking-review-done">
                <p class="jbw-booking-action-hint">Your rating: <strong>{{ number_format((float) $orderItem->review->rating, 1) }}★</strong>
                    @if ($orderItem->review->comment)
                        — {{ $orderItem->review->comment }}
                    @endif
                </p>
            </div>
        @elseif ($canReviewItem || $canReviewBooking)
            <form method="POST" action="{{ route('web.bookings.review', $order) }}" class="jbw-booking-action-form">
                @csrf
                @if ($orderItem)
                    <input type="hidden" name="item_id" value="{{ $orderItem->id }}">
                @endif
                <div class="jbw-field">
                    <label class="jbw-label" for="review-rating-{{ $orderItem?->id ?? $order->id }}">Rating</label>
                    <select id="review-rating-{{ $orderItem?->id ?? $order->id }}" name="rating" class="jbw-select" required>
                        <option value="">Select</option>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ $i }} ★</option>
                        @endfor
                    </select>
                </div>
                <div class="jbw-field">
                    <label class="jbw-label" for="review-comment-{{ $orderItem?->id ?? $order->id }}">Comment <span class="jbw-label-optional">(optional)</span></label>
                    <textarea id="review-comment-{{ $orderItem?->id ?? $order->id }}" name="comment" rows="2" class="jbw-textarea" maxlength="2000"></textarea>
                </div>
                <button type="submit" class="jbw-btn jbw-btn--primary jbw-btn--sm jbw-btn--block">
                    {{ $orderItem ? 'Submit item review' : 'Submit review' }}
                </button>
            </form>
        @endif
    </div>
@endif

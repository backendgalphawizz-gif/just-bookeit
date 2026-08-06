@php
    /** @var array<string, mixed> $paymentSummary */
    $isCheckout = ($context ?? 'checkout') === 'checkout';
    $subtotal = $isCheckout
        ? (float) ($paymentSummary['subtotal'] ?? $model->amount ?? 0)
        : (float) ($paymentSummary['subtotal'] ?? $model->subtotal());
    $shipping = $isCheckout
        ? (float) ($paymentSummary['shipping_fee'] ?? $model->delivery_fee ?? 0)
        : (float) ($model->delivery_fee ?? 0);
    $tax = $isCheckout
        ? (float) ($paymentSummary['tax_amount'] ?? $model->tax_amount ?? 0)
        : (float) ($model->tax_amount ?? 0);
    $taxPercent = (float) ($paymentSummary['tax_percent'] ?? \App\Services\Booking\BookingPricingService::gstPercent());
    $advance = (float) ($paymentSummary['advance_amount'] ?? 0);
    $total = $isCheckout
        ? (float) ($paymentSummary['grand_total'] ?? $paymentSummary['total_amount'] ?? $model->grand_total)
        : (float) ($paymentSummary['total_amount'] ?? $model->grandTotal());
    $canPay = (bool) ($paymentSummary['can_pay'] ?? false);
    $payLabel = (string) ($paymentSummary['pay_label'] ?? 'Pay now');
    $payUrl = $payUrl ?? null;
    $showRateReview = $showRateReview ?? false;
    $showPayButton = $showPayButton ?? false;
@endphp

<div class="jbw-figma-pay-card">
    <h2 class="jbw-figma-section-title">Payment Summary</h2>
    <div class="jbw-figma-pay-lines">
        <div class="jbw-figma-pay-line">
            <span>Subtotal</span>
            <span>₹{{ number_format($subtotal, 0) }}</span>
        </div>
        @if ($advance > 0)
            <div class="jbw-figma-pay-line jbw-figma-pay-line--accent">
                <span>Advance Amount</span>
                <span>₹{{ number_format($advance, 0) }}</span>
            </div>
        @endif
        <div class="jbw-figma-pay-line">
            <span>Shipping &amp; Handling</span>
            <span>₹{{ number_format($shipping, 0) }}</span>
        </div>
        <div class="jbw-figma-pay-line">
            <span>Tax (GST {{ rtrim(rtrim(number_format($taxPercent, 1), '0'), '.') }}%)</span>
            <span>₹{{ number_format($tax, 0) }}</span>
        </div>
    </div>
    <div class="jbw-figma-pay-total">
        <span>Total Amount</span>
        <strong>₹{{ number_format($total, 0) }}</strong>
    </div>
    @if ($showPayButton && $canPay && $payUrl)
        <a href="{{ $payUrl }}" class="jbw-btn jbw-btn--primary jbw-btn--block jbw-figma-pay-btn">{{ $payLabel }}</a>
    @elseif ($showRateReview)
        <a href="#jbw-rate-review" class="jbw-btn jbw-btn--primary jbw-btn--block jbw-figma-pay-btn">Rate &amp; Review</a>
    @endif
    <p class="jbw-figma-pay-footnote">Taxes and shipping are calculated based on your address.</p>
</div>

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PlatformSetting;
use App\Services\Booking\BookingPaymentService;
use App\Services\Payment\RazorpayService;
use App\Support\Api\CustomerApiPresenter;
use App\Support\RazorpayPaymentSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class PaymentController extends ApiController
{
    public function __construct(
        protected BookingPaymentService $payments,
        protected RazorpayService $razorpay
    ) {}

    /** @return array<int, array{id: string, label: string, enabled: bool}> */
    protected function paymentMethodItems(): array
    {
        return RazorpayPaymentSupport::paymentMethods(true);
    }

    public function methods(): JsonResponse
    {
        return $this->success([
            'items' => $this->paymentMethodItems(),
            'razorpay_enabled' => $this->razorpay->enabled(),
            'razorpay_key_id' => $this->razorpay->enabled() ? $this->razorpay->keyId() : null,
        ]);
    }

    /**
     * Create a Razorpay order for the current cart (pay before POST /bookings).
     * Then complete Razorpay UI and send payment ids on POST /bookings.
     */
    public function createCartRazorpayOrder(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        if (! $this->razorpay->enabled()) {
            return $this->error('Razorpay is not configured.', 503);
        }

        $data = $request->validate([
            'rental_start_date' => ['nullable', 'date'],
            'rental_end_date' => ['nullable', 'date', 'after_or_equal:rental_start_date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'shipment_required' => ['nullable', 'boolean'],
            'items' => ['nullable'],
            'items_json' => ['nullable'],
            'cart_items' => ['nullable'],
            'line_items' => ['nullable'],
            'vendor_shipments' => ['nullable', 'array'],
        ]);

        try {
            $preview = app(\App\Services\Checkout\CheckoutService::class)->preview($customer, $data, true, $request);
            $payableNow = (float) ($preview['summary']['advance_amount'] ?? 0);
            $grandTotal = (float) ($preview['summary']['grand_total'] ?? 0);
            if ($payableNow <= 0 || $payableNow > $grandTotal) {
                $payableNow = $grandTotal;
            }
            if ($payableNow <= 0) {
                return $this->error('Nothing to pay for this cart.', 422);
            }

            $options = $this->createCachedOrderPayload(
                'cart',
                $customer->id,
                $payableNow,
                'cart_'.$customer->id,
                [
                    'type' => 'cart',
                    'customer_id' => (string) $customer->id,
                ],
                'Cart checkout',
                $customer
            );

            return $this->success([
                'razorpay' => $options,
                'payment_summary' => $preview['summary'],
                'payable_now' => $payableNow,
            ], 'Razorpay order created for cart.');
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function summary(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        $paymentSummary = $this->payments->summaryForOrder($booking);

        return $this->success([
            'booking' => CustomerApiPresenter::bookingSummary($booking),
            'payment_summary' => $paymentSummary,
            'payment_methods' => $this->paymentMethodItems(),
            'razorpay_enabled' => $this->razorpay->enabled(),
            'razorpay_key_id' => $this->razorpay->enabled() ? $this->razorpay->keyId() : null,
        ]);
    }

    public function createRazorpayOrder(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        if (! $this->razorpay->enabled()) {
            return $this->error('Razorpay is not configured.', 503);
        }

        $summary = $this->payments->summaryForOrder($booking);
        if (! $summary['can_pay']) {
            return $this->error(
                $summary['is_fully_paid']
                    ? 'Payment already completed for this booking.'
                    : 'No payment is due for this booking right now.',
                422
            );
        }

        try {
            $options = $this->createCachedOrderPayload('order', $booking->id, (float) $summary['payable_now'], 'ord_'.$booking->id, [
                'type' => 'order',
                'order_id' => (string) $booking->id,
                'order_number' => (string) $booking->order_number,
            ], 'Booking #'.$booking->order_number, $customer);
        } catch (Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success([
            'razorpay' => $options,
            'payment_summary' => $summary,
        ], 'Razorpay order created.');
    }

    public function pay(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        $methodIds = collect($this->paymentMethodItems())->pluck('id')->implode(',');
        $data = $request->validate([
            'payment_method' => ['required', 'in:'.$methodIds],
            'razorpay_order_id' => ['nullable', 'string'],
            'razorpay_payment_id' => ['nullable', 'string'],
            'razorpay_signature' => ['nullable', 'string'],
        ]);

        if ($data['payment_method'] === 'cod' && ! (bool) PlatformSetting::get('enable_cod', false)) {
            return $this->error('Cash on delivery is not available.', 422);
        }

        try {
            if (RazorpayPaymentSupport::isOnlineMethod($data['payment_method'])) {
                if (! $this->razorpay->enabled()) {
                    return $this->error('Online payments are not configured.', 503);
                }

                if (! $request->filled(['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'])) {
                    return $this->error('Complete Razorpay checkout first, then send payment identifiers.', 422);
                }

                $this->assertCachedPayment('order', $booking->id, $request->only([
                    'razorpay_order_id',
                    'razorpay_payment_id',
                    'razorpay_signature',
                ]));
                $data['payment_method'] = 'razorpay';
            }

            $before = $this->payments->summaryForOrder($booking);
            $booking = $this->payments->payOrder($booking, $data['payment_method']);
            $after = $this->payments->summaryForOrder($booking);
        } catch (InvalidArgumentException $e) {
            $status = str_contains(strtolower($e->getMessage()), 'already') ? 409 : 422;

            return $this->error($e->getMessage(), $status);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $booking->load(['vendor', 'category', 'orderItems.portfolioItem']);

        $isCod = $data['payment_method'] === 'cod';

        return $this->success([
            'booking' => CustomerApiPresenter::bookingDetail($booking),
            'payment_summary' => $after,
            'payment' => [
                'method' => $data['payment_method'],
                'status' => $booking->payment_status,
                'phase' => $after['payment_phase'],
                'transaction_id' => $request->string('razorpay_payment_id')->toString()
                    ?: 'JBTX'.strtoupper(substr(md5($booking->id.now()->timestamp), 0, 12)),
                'paid_amount' => $isCod ? 0.0 : (float) $before['payable_now'],
                'amount_paid_total' => (float) $after['amount_paid'],
                'remaining_amount' => (float) $after['remaining_amount'],
            ],
        ], $isCod
            ? 'Cash on delivery confirmed. Booking sent to the designer.'
            : (in_array($after['payment_phase'], ['remaining_due', 'advance_paid_waiting'], true)
                ? 'Advance paid successfully. Remaining amount is due on booking completion. Booking sent to the designer.'
                : 'Payment successful. Booking sent to the designer.'));
    }

    public function checkoutSummary(Request $request, CheckoutOrder $checkoutOrder): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        $checkoutOrder->load(['subOrders.vendor', 'subOrders.orderItems.portfolioItem']);
        $paymentSummary = $this->payments->summaryForCheckout($checkoutOrder);

        return $this->success([
            'checkout_order' => CustomerApiPresenter::checkoutOrderSummary($checkoutOrder),
            'payment_summary' => $paymentSummary,
            'payment_methods' => $this->paymentMethodItems(),
            'razorpay_enabled' => $this->razorpay->enabled(),
            'razorpay_key_id' => $this->razorpay->enabled() ? $this->razorpay->keyId() : null,
        ]);
    }

    public function createCheckoutRazorpayOrder(Request $request, CheckoutOrder $checkoutOrder): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        if (! $this->razorpay->enabled()) {
            return $this->error('Razorpay is not configured.', 503);
        }

        $summary = $this->payments->summaryForCheckout($checkoutOrder);
        if (! $summary['can_pay']) {
            return $this->error(
                $summary['is_fully_paid']
                    ? 'Payment already completed for this checkout.'
                    : 'No payment is due for this checkout right now.',
                422
            );
        }

        try {
            $options = $this->createCachedOrderPayload('checkout', $checkoutOrder->id, (float) $summary['payable_now'], 'chk_'.$checkoutOrder->id, [
                'type' => 'checkout',
                'checkout_order_id' => (string) $checkoutOrder->id,
                'order_number' => (string) $checkoutOrder->order_number,
            ], 'Order #'.$checkoutOrder->order_number, $customer);
        } catch (Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success([
            'razorpay' => $options,
            'payment_summary' => $summary,
        ], 'Razorpay order created.');
    }

    public function payCheckout(Request $request, CheckoutOrder $checkoutOrder): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        $methodIds = collect($this->paymentMethodItems())->pluck('id')->implode(',');
        $data = $request->validate([
            'payment_method' => ['required', 'in:'.$methodIds],
            'razorpay_order_id' => ['nullable', 'string'],
            'razorpay_payment_id' => ['nullable', 'string'],
            'razorpay_signature' => ['nullable', 'string'],
        ]);

        if ($data['payment_method'] === 'cod' && ! (bool) PlatformSetting::get('enable_cod', false)) {
            return $this->error('Cash on delivery is not available.', 422);
        }

        try {
            if (RazorpayPaymentSupport::isOnlineMethod($data['payment_method'])) {
                if (! $this->razorpay->enabled()) {
                    return $this->error('Online payments are not configured.', 503);
                }

                if (! $request->filled(['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'])) {
                    return $this->error('Complete Razorpay checkout first, then send payment identifiers.', 422);
                }

                $this->assertCachedPayment('checkout', $checkoutOrder->id, $request->only([
                    'razorpay_order_id',
                    'razorpay_payment_id',
                    'razorpay_signature',
                ]));
                $data['payment_method'] = 'razorpay';
            }

            $before = $this->payments->summaryForCheckout($checkoutOrder);
            $checkout = $this->payments->payCheckout($checkoutOrder, $data['payment_method']);
            $after = $this->payments->summaryForCheckout($checkout);
        } catch (InvalidArgumentException $e) {
            $status = str_contains(strtolower($e->getMessage()), 'already') ? 409 : 422;

            return $this->error($e->getMessage(), $status);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $checkout->load(['subOrders.vendor', 'subOrders.category', 'subOrders.orderItems']);

        $isCod = $data['payment_method'] === 'cod';

        return $this->success([
            'checkout_order' => CustomerApiPresenter::checkoutOrderDetail($checkout),
            'payment_summary' => $after,
            'payment' => [
                'method' => $data['payment_method'],
                'status' => $checkout->payment_status,
                'phase' => $after['payment_phase'],
                'transaction_id' => $request->string('razorpay_payment_id')->toString()
                    ?: 'JBTX'.strtoupper(substr(md5($checkout->id.now()->timestamp), 0, 12)),
                'paid_amount' => $isCod ? 0.0 : (float) $before['payable_now'],
                'amount_paid_total' => (float) $after['amount_paid'],
                'remaining_amount' => (float) $after['remaining_amount'],
            ],
        ], $isCod
            ? 'Cash on delivery confirmed. Booking sent to the designers.'
            : (in_array($after['payment_phase'], ['remaining_due', 'advance_paid_waiting'], true)
                ? 'Advance paid successfully. Remaining amount is due on booking completion. Booking sent to the designer.'
                : 'Payment successful. Booking sent to the designer.'));
    }

    /**
     * @param  array<string, mixed>  $notes
     * @return array<string, mixed>
     */
    protected function createCachedOrderPayload(
        string $type,
        int|string $entityId,
        float $payableNow,
        string $receiptPrefix,
        array $notes,
        string $description,
        Customer $customer
    ): array {
        $rzOrder = $this->razorpay->createOrder(
            $payableNow,
            $receiptPrefix.'_'.substr((string) time(), -6),
            $notes
        );

        Cache::put($this->cacheKey($type, $entityId), [
            'razorpay_order_id' => $rzOrder['id'],
            'amount' => $rzOrder['amount'],
            'payable_now' => $payableNow,
        ], now()->addHour());

        return $this->razorpay->checkoutOptions(
            $rzOrder['id'],
            $rzOrder['amount'],
            $description,
            $customer->name,
            $customer->email,
            $customer->mobile,
        );
    }

    /** @param  array{razorpay_order_id?: string, razorpay_payment_id?: string, razorpay_signature?: string}  $payload */
    protected function assertCachedPayment(string $type, int|string $entityId, array $payload): void
    {
        $expected = Cache::get($this->cacheKey($type, $entityId));

        // Prefer matching the cached Razorpay order when still available.
        // If cache expired after a successful checkout UI, signature verification alone is enough.
        if (
            is_array($expected)
            && ($expected['razorpay_order_id'] ?? null) !== null
            && ($expected['razorpay_order_id'] ?? null) !== ($payload['razorpay_order_id'] ?? null)
        ) {
            throw new InvalidArgumentException('Razorpay order mismatch. Create a new Razorpay order and try again.');
        }

        $this->razorpay->verifyPaymentSignature([
            'razorpay_order_id' => (string) $payload['razorpay_order_id'],
            'razorpay_payment_id' => (string) $payload['razorpay_payment_id'],
            'razorpay_signature' => (string) $payload['razorpay_signature'],
        ]);

        Cache::forget($this->cacheKey($type, $entityId));
    }

    protected function cacheKey(string $type, int|string $entityId): string
    {
        return 'razorpay.'.$type.'.'.$entityId;
    }
}

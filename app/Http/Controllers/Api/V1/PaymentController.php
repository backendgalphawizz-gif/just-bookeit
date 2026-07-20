<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PlatformSetting;
use App\Services\Booking\BookingPaymentService;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PaymentController extends ApiController
{
    public function __construct(
        protected BookingPaymentService $payments
    ) {}

    /** @return array<int, array{id: string, label: string, enabled: bool}> */
    protected function paymentMethodItems(): array
    {
        $methods = [
            ['id' => 'upi', 'label' => 'UPI', 'enabled' => true],
            ['id' => 'debit_card', 'label' => 'Debit Card', 'enabled' => true],
            ['id' => 'credit_card', 'label' => 'Credit Card', 'enabled' => true],
        ];

        if ((bool) PlatformSetting::get('enable_cod', false)) {
            $methods[] = ['id' => 'cod', 'label' => 'Cash On Delivery', 'enabled' => true];
        }

        return $methods;
    }

    public function methods(): JsonResponse
    {
        return $this->success(['items' => $this->paymentMethodItems()]);
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
        ]);
    }

    public function pay(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        $data = $request->validate([
            'payment_method' => ['required', 'in:upi,debit_card,credit_card,cod'],
        ]);

        if ($data['payment_method'] === 'cod' && ! (bool) PlatformSetting::get('enable_cod', false)) {
            return $this->error('Cash on delivery is not available.', 422);
        }

        try {
            $before = $this->payments->summaryForOrder($booking);
            $booking = $this->payments->payOrder($booking, $data['payment_method']);
            $after = $this->payments->summaryForOrder($booking);
        } catch (InvalidArgumentException $e) {
            $status = str_contains(strtolower($e->getMessage()), 'already') ? 409 : 422;

            return $this->error($e->getMessage(), $status);
        }

        $booking->load(['vendor', 'category', 'orderItems.portfolioItem']);

        return $this->success([
            'booking' => CustomerApiPresenter::bookingDetail($booking),
            'payment_summary' => $after,
            'payment' => [
                'method' => $data['payment_method'],
                'status' => $booking->payment_status,
                'phase' => $after['payment_phase'],
                'transaction_id' => 'JBTX'.strtoupper(substr(md5($booking->id.now()->timestamp), 0, 12)),
                'paid_amount' => (float) $before['payable_now'],
                'amount_paid_total' => (float) $after['amount_paid'],
                'remaining_amount' => (float) $after['remaining_amount'],
            ],
        ], in_array($after['payment_phase'], ['remaining_due', 'advance_paid_waiting'], true)
            ? 'Advance paid successfully. Remaining amount is due on booking completion.'
            : 'Payment successful.');
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
        ]);
    }

    public function payCheckout(Request $request, CheckoutOrder $checkoutOrder): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        $data = $request->validate([
            'payment_method' => ['required', 'in:upi,debit_card,credit_card,cod'],
        ]);

        if ($data['payment_method'] === 'cod' && ! (bool) PlatformSetting::get('enable_cod', false)) {
            return $this->error('Cash on delivery is not available.', 422);
        }

        try {
            $before = $this->payments->summaryForCheckout($checkoutOrder);
            $checkout = $this->payments->payCheckout($checkoutOrder, $data['payment_method']);
            $after = $this->payments->summaryForCheckout($checkout);
        } catch (InvalidArgumentException $e) {
            $status = str_contains(strtolower($e->getMessage()), 'already') ? 409 : 422;

            return $this->error($e->getMessage(), $status);
        }

        $checkout->load(['subOrders.vendor', 'subOrders.category', 'subOrders.orderItems']);

        return $this->success([
            'checkout_order' => CustomerApiPresenter::checkoutOrderDetail($checkout),
            'payment_summary' => $after,
            'payment' => [
                'method' => $data['payment_method'],
                'status' => $checkout->payment_status,
                'phase' => $after['payment_phase'],
                'transaction_id' => 'JBTX'.strtoupper(substr(md5($checkout->id.now()->timestamp), 0, 12)),
                'paid_amount' => (float) $before['payable_now'],
                'amount_paid_total' => (float) $after['amount_paid'],
                'remaining_amount' => (float) $after['remaining_amount'],
            ],
        ], in_array($after['payment_phase'], ['remaining_due', 'advance_paid_waiting'], true)
            ? 'Advance paid successfully. Remaining amount is due on booking completion.'
            : 'Payment successful.');
    }
}

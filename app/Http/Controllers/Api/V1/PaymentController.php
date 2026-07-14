<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PlatformSetting;
use App\Services\Booking\BookingPricingService;
use App\Services\Checkout\CheckoutService;
use App\Services\Vendor\VendorWalletService;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    public function __construct(
        protected CheckoutService $checkout
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

        return $this->success([
            'booking' => CustomerApiPresenter::bookingSummary($booking),
            'payment_summary' => BookingPricingService::fromOrder($booking),
            'payment_methods' => $this->paymentMethodItems(),
        ]);
    }

    public function pay(Request $request, Order $booking): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($booking->customer_id === $customer->id, 403);

        if ($booking->payment_status === 'success') {
            return $this->error('Payment already completed for this booking.', 409);
        }

        $data = $request->validate([
            'payment_method' => ['required', 'in:upi,debit_card,credit_card,cod'],
        ]);

        if ($data['payment_method'] === 'cod' && ! (bool) PlatformSetting::get('enable_cod', false)) {
            return $this->error('Cash on delivery is not available.', 422);
        }

        $booking->update([
            'payment_status' => 'success',
            'payment_method' => $data['payment_method'],
            'paid_at' => now(),
            'status' => $booking->status === 'new' ? 'pending_acceptance' : $booking->status,
        ]);

        app(VendorWalletService::class)->creditFromPayment($booking->fresh());

        $booking->load(['vendor', 'category']);

        return $this->success([
            'booking' => CustomerApiPresenter::bookingDetail($booking),
            'payment' => [
                'method' => $data['payment_method'],
                'status' => 'success',
                'transaction_id' => 'JBTX'.strtoupper(substr(md5($booking->id.now()->timestamp), 0, 12)),
                'paid_amount' => $booking->grandTotal(),
            ],
        ], 'Payment successful.');
    }

    public function checkoutSummary(Request $request, CheckoutOrder $checkoutOrder): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        $checkoutOrder->load(['subOrders.vendor', 'subOrders.orderItems']);

        return $this->success([
            'checkout_order' => CustomerApiPresenter::checkoutOrderSummary($checkoutOrder),
            'payment_methods' => $this->paymentMethodItems(),
        ]);
    }

    public function payCheckout(Request $request, CheckoutOrder $checkoutOrder): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        if ($checkoutOrder->payment_status === 'success') {
            return $this->error('Payment already completed for this checkout.', 409);
        }

        $data = $request->validate([
            'payment_method' => ['required', 'in:upi,debit_card,credit_card,cod'],
        ]);

        if ($data['payment_method'] === 'cod' && ! (bool) PlatformSetting::get('enable_cod', false)) {
            return $this->error('Cash on delivery is not available.', 422);
        }

        $checkout = $this->checkout->markPaid($checkoutOrder, $data['payment_method']);
        $checkout->load(['subOrders.vendor', 'subOrders.category', 'subOrders.orderItems']);

        return $this->success([
            'checkout_order' => CustomerApiPresenter::checkoutOrderDetail($checkout),
            'payment' => [
                'method' => $data['payment_method'],
                'status' => 'success',
                'transaction_id' => 'JBTX'.strtoupper(substr(md5($checkout->id.now()->timestamp), 0, 12)),
                'paid_amount' => (float) $checkout->grand_total,
            ],
        ], 'Payment successful.');
    }
}

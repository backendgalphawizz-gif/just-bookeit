<?php

namespace App\Support;

use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PlatformSetting;
use App\Services\Booking\BookingPaymentService;
use App\Services\Payment\RazorpayService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class RazorpayPaymentSupport
{
    /** @return array<int, array{id: string, label: string, enabled?: bool}> */
    public static function paymentMethods(bool $withEnabledFlag = false): array
    {
        $razorpay = app(RazorpayService::class);
        $methods = [];

        if ($razorpay->enabled()) {
            $methods[] = ['id' => 'razorpay', 'label' => 'Pay online (UPI / Card / Netbanking)'];
        } else {
            $methods = [
                ['id' => 'upi', 'label' => 'UPI'],
                ['id' => 'debit_card', 'label' => 'Debit Card'],
                ['id' => 'credit_card', 'label' => 'Credit Card'],
            ];
        }

        if ((bool) PlatformSetting::get('enable_cod', false)) {
            $methods[] = ['id' => 'cod', 'label' => 'Cash on Delivery'];
        }

        if ($withEnabledFlag) {
            return array_map(static function (array $method) {
                $method['enabled'] = true;

                return $method;
            }, $methods);
        }

        return $methods;
    }

    /** @return array<string, mixed> */
    public static function createCheckoutPayloadForOrder(Order $order, Customer $customer, array $summary): array
    {
        $razorpay = app(RazorpayService::class);
        if (! $razorpay->enabled()) {
            throw new RuntimeException('Razorpay is not configured.');
        }

        $payableNow = (float) ($summary['payable_now'] ?? 0);
        $rzOrder = $razorpay->createOrder(
            $payableNow,
            'ord_'.$order->id.'_'.substr((string) time(), -6),
            [
                'type' => 'order',
                'order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
            ]
        );

        session([
            'razorpay.order.'.$order->id => [
                'razorpay_order_id' => $rzOrder['id'],
                'amount' => $rzOrder['amount'],
                'payable_now' => $payableNow,
            ],
        ]);

        return $razorpay->checkoutOptions(
            $rzOrder['id'],
            $rzOrder['amount'],
            'Booking #'.$order->order_number,
            $customer->name,
            $customer->email,
            $customer->mobile,
        );
    }

    /** @return array<string, mixed> */
    public static function createCheckoutPayloadForCheckout(CheckoutOrder $checkout, Customer $customer, array $summary): array
    {
        $razorpay = app(RazorpayService::class);
        if (! $razorpay->enabled()) {
            throw new RuntimeException('Razorpay is not configured.');
        }

        $payableNow = (float) ($summary['payable_now'] ?? 0);
        $rzOrder = $razorpay->createOrder(
            $payableNow,
            'chk_'.$checkout->id.'_'.substr((string) time(), -6),
            [
                'type' => 'checkout',
                'checkout_order_id' => (string) $checkout->id,
                'order_number' => (string) $checkout->order_number,
            ]
        );

        session([
            'razorpay.checkout.'.$checkout->id => [
                'razorpay_order_id' => $rzOrder['id'],
                'amount' => $rzOrder['amount'],
                'payable_now' => $payableNow,
            ],
        ]);

        return $razorpay->checkoutOptions(
            $rzOrder['id'],
            $rzOrder['amount'],
            'Order #'.$checkout->order_number,
            $customer->name,
            $customer->email,
            $customer->mobile,
        );
    }

    public static function assertVerifiedOrderPayment(Request $request, Order $order): void
    {
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $expected = session('razorpay.order.'.$order->id);
        if (! is_array($expected) || ($expected['razorpay_order_id'] ?? null) !== $data['razorpay_order_id']) {
            throw new InvalidArgumentException('Razorpay order mismatch. Please refresh and try again.');
        }

        app(RazorpayService::class)->verifyPaymentSignature($data);
        session()->forget('razorpay.order.'.$order->id);
    }

    public static function assertVerifiedCheckoutPayment(Request $request, CheckoutOrder $checkout): void
    {
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $expected = session('razorpay.checkout.'.$checkout->id);
        if (! is_array($expected) || ($expected['razorpay_order_id'] ?? null) !== $data['razorpay_order_id']) {
            throw new InvalidArgumentException('Razorpay order mismatch. Please refresh and try again.');
        }

        app(RazorpayService::class)->verifyPaymentSignature($data);
        session()->forget('razorpay.checkout.'.$checkout->id);
    }

    public static function isOnlineMethod(string $method): bool
    {
        return in_array($method, ['razorpay', 'upi', 'debit_card', 'credit_card'], true);
    }

    public static function allowedMethodRule(): string
    {
        $ids = collect(self::paymentMethods())->pluck('id')->all();

        return 'in:'.implode(',', $ids ?: ['razorpay']);
    }

    public static function payments(): BookingPaymentService
    {
        return app(BookingPaymentService::class);
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Models\CheckoutOrder;
use App\Models\PlatformSetting;
use App\Services\Booking\BookingPaymentService;
use App\Services\Payment\RazorpayService;
use App\Support\RazorpayPaymentSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class CheckoutPaymentController extends WebController
{
    public function __construct(
        protected BookingPaymentService $payments,
        protected RazorpayService $razorpay
    ) {}

    public function show(CheckoutOrder $checkoutOrder): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        $checkoutOrder->load(['subOrders.vendor', 'subOrders.orderItems']);
        $pricing = $this->payments->summaryForCheckout($checkoutOrder);

        if (! $pricing['can_pay']) {
            return redirect()
                ->route('web.bookings.checkout.show', $checkoutOrder)
                ->with('success', $pricing['is_fully_paid']
                    ? 'Payment already completed for this checkout.'
                    : 'No payment is due for this checkout right now.');
        }

        $razorpayOptions = null;
        if ($this->razorpay->enabled()) {
            try {
                $razorpayOptions = RazorpayPaymentSupport::createCheckoutPayloadForCheckout($checkoutOrder, $customer, $pricing);
            } catch (Throwable $e) {
                return redirect()
                    ->route('web.bookings.checkout.show', $checkoutOrder)
                    ->with('error', $e->getMessage());
            }
        }

        return view('web.checkout.payment', [
            'checkoutOrder' => $checkoutOrder,
            'pricing' => $pricing,
            'paymentMethods' => RazorpayPaymentSupport::paymentMethods(),
            'razorpayOptions' => $razorpayOptions,
            'razorpayEnabled' => $this->razorpay->enabled(),
        ]);
    }

    public function pay(Request $request, CheckoutOrder $checkoutOrder): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        $methods = collect(RazorpayPaymentSupport::paymentMethods())->pluck('id')->all();

        $data = $request->validate([
            'payment_method' => ['required', 'in:'.implode(',', $methods)],
            'razorpay_order_id' => ['nullable', 'string'],
            'razorpay_payment_id' => ['nullable', 'string'],
            'razorpay_signature' => ['nullable', 'string'],
        ]);

        if ($data['payment_method'] === 'cod' && ! (bool) PlatformSetting::get('enable_cod', false)) {
            return back()->with('error', 'Cash on delivery is not available.');
        }

        try {
            if (RazorpayPaymentSupport::isOnlineMethod($data['payment_method'])) {
                if (! $this->razorpay->enabled()) {
                    throw new RuntimeException('Online payments are not configured.');
                }
                RazorpayPaymentSupport::assertVerifiedCheckoutPayment($request, $checkoutOrder);
                $data['payment_method'] = 'razorpay';
            }

            $checkoutOrder = $this->payments->payCheckout($checkoutOrder, $data['payment_method']);
            $summary = $this->payments->summaryForCheckout($checkoutOrder);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('web.checkout.payment', $checkoutOrder)
                ->with('error', $e->getMessage());
        }

        $message = in_array($summary['payment_phase'], ['remaining_due', 'advance_paid_waiting'], true)
            ? 'Advance paid successfully. Remaining amount will be due when the booking is completed.'
            : 'Payment successful. Your order is awaiting vendor confirmation.';

        return redirect()
            ->route('web.bookings.checkout.show', $checkoutOrder)
            ->with('success', $message);
    }
}

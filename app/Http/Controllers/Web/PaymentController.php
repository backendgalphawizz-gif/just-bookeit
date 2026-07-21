<?php

namespace App\Http\Controllers\Web;

use App\Models\Order;
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

class PaymentController extends WebController
{
    public function __construct(
        protected BookingPaymentService $payments,
        protected RazorpayService $razorpay
    ) {}

    public function show(Order $order): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        $pricing = $this->payments->summaryForOrder($order);

        if (! $pricing['can_pay']) {
            return redirect()
                ->route('web.bookings.show', $order)
                ->with('success', $pricing['is_fully_paid']
                    ? 'Payment already completed for this booking.'
                    : 'No payment is due for this booking right now.');
        }

        $order->load(['vendor', 'category', 'subcategory', 'orderItems']);

        $razorpayOptions = null;
        if ($this->razorpay->enabled()) {
            try {
                $razorpayOptions = RazorpayPaymentSupport::createCheckoutPayloadForOrder($order, $customer, $pricing);
            } catch (Throwable $e) {
                return redirect()
                    ->route('web.bookings.show', $order)
                    ->with('error', $e->getMessage());
            }
        }

        return view('web.bookings.payment', [
            'order' => $order,
            'pricing' => $pricing,
            'paymentMethods' => RazorpayPaymentSupport::paymentMethods(),
            'razorpayOptions' => $razorpayOptions,
            'razorpayEnabled' => $this->razorpay->enabled(),
        ]);
    }

    public function pay(Request $request, Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

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
                RazorpayPaymentSupport::assertVerifiedOrderPayment($request, $order);
                $data['payment_method'] = 'razorpay';
            }

            $order = $this->payments->payOrder($order, $data['payment_method']);
            $summary = $this->payments->summaryForOrder($order);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('web.bookings.payment', $order)
                ->with('error', $e->getMessage());
        }

        $message = in_array($summary['payment_phase'], ['remaining_due', 'advance_paid_waiting'], true)
            ? 'Advance paid successfully. Remaining amount will be due when the booking is completed.'
            : 'Payment successful. Your booking is awaiting designer confirmation.';

        return redirect()
            ->route('web.bookings.show', $order)
            ->with('success', $message);
    }
}

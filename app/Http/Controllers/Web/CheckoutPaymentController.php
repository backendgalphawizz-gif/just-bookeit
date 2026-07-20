<?php

namespace App\Http\Controllers\Web;

use App\Models\CheckoutOrder;
use App\Models\PlatformSetting;
use App\Services\Booking\BookingPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

class CheckoutPaymentController extends WebController
{
    public function __construct(
        protected BookingPaymentService $payments
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

        return view('web.checkout.payment', [
            'checkoutOrder' => $checkoutOrder,
            'pricing' => $pricing,
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function pay(Request $request, CheckoutOrder $checkoutOrder): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        $methods = collect($this->paymentMethods())->pluck('id')->all();

        $data = $request->validate([
            'payment_method' => ['required', 'in:'.implode(',', $methods)],
        ]);

        if ($data['payment_method'] === 'cod' && ! (bool) PlatformSetting::get('enable_cod', false)) {
            return back()->with('error', 'Cash on delivery is not available.');
        }

        try {
            $checkoutOrder = $this->payments->payCheckout($checkoutOrder, $data['payment_method']);
            $summary = $this->payments->summaryForCheckout($checkoutOrder);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('web.bookings.checkout.show', $checkoutOrder)
                ->with('error', $e->getMessage());
        }

        $message = in_array($summary['payment_phase'], ['remaining_due', 'advance_paid_waiting'], true)
            ? 'Advance paid successfully. Remaining amount will be due when the booking is completed.'
            : 'Payment successful. Your order is awaiting vendor confirmation.';

        return redirect()
            ->route('web.bookings.checkout.show', $checkoutOrder)
            ->with('success', $message);
    }

    /** @return array<int, array{id: string, label: string}> */
    protected function paymentMethods(): array
    {
        $methods = [
            ['id' => 'upi', 'label' => 'UPI'],
            ['id' => 'debit_card', 'label' => 'Debit Card'],
            ['id' => 'credit_card', 'label' => 'Credit Card'],
        ];

        if ((bool) PlatformSetting::get('enable_cod', false)) {
            $methods[] = ['id' => 'cod', 'label' => 'Cash on Delivery'];
        }

        return $methods;
    }
}

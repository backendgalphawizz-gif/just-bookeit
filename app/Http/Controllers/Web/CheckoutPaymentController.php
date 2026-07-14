<?php

namespace App\Http\Controllers\Web;

use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\PlatformSetting;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CheckoutPaymentController extends WebController
{
    public function __construct(
        protected CheckoutService $checkout
    ) {}

    public function show(CheckoutOrder $checkoutOrder): View|RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        if ($checkoutOrder->payment_status === 'success') {
            return redirect()
                ->route('web.checkout.show-order', $checkoutOrder)
                ->with('success', 'Payment already completed for this checkout.');
        }

        $checkoutOrder->load(['subOrders.vendor', 'subOrders.orderItems']);

        return view('web.checkout.payment', [
            'checkoutOrder' => $checkoutOrder,
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function pay(Request $request, CheckoutOrder $checkoutOrder): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        abort_unless($checkoutOrder->customer_id === $customer->id, 403);

        if ($checkoutOrder->payment_status === 'success') {
            return redirect()
                ->route('web.checkout.show-order', $checkoutOrder)
                ->with('success', 'Payment already completed for this checkout.');
        }

        $methods = collect($this->paymentMethods())->pluck('id')->all();

        $data = $request->validate([
            'payment_method' => ['required', 'in:'.implode(',', $methods)],
        ]);

        if ($data['payment_method'] === 'cod' && ! (bool) PlatformSetting::get('enable_cod', false)) {
            return back()->with('error', 'Cash on delivery is not available.');
        }

        $this->checkout->markPaid($checkoutOrder, $data['payment_method']);

        return redirect()
            ->route('web.checkout.show-order', $checkoutOrder)
            ->with('success', 'Payment successful. Your order has been sent to the designers.');
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

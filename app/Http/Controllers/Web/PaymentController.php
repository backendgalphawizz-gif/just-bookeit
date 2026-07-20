<?php

namespace App\Http\Controllers\Web;

use App\Models\Order;
use App\Models\PlatformSetting;
use App\Services\Booking\BookingPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

class PaymentController extends WebController
{
    public function __construct(
        protected BookingPaymentService $payments
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

        return view('web.bookings.payment', [
            'order' => $order,
            'pricing' => $pricing,
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function pay(Request $request, Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        $methods = collect($this->paymentMethods())->pluck('id')->all();

        $data = $request->validate([
            'payment_method' => ['required', 'in:'.implode(',', $methods)],
        ]);

        if ($data['payment_method'] === 'cod' && ! (bool) PlatformSetting::get('enable_cod', false)) {
            return back()->with('error', 'Cash on delivery is not available.');
        }

        try {
            $order = $this->payments->payOrder($order, $data['payment_method']);
            $summary = $this->payments->summaryForOrder($order);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('web.bookings.show', $order)
                ->with('error', $e->getMessage());
        }

        $message = in_array($summary['payment_phase'], ['remaining_due', 'advance_paid_waiting'], true)
            ? 'Advance paid successfully. Remaining amount will be due when the booking is completed.'
            : 'Payment successful. Your booking is awaiting designer confirmation.';

        return redirect()
            ->route('web.bookings.show', $order)
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

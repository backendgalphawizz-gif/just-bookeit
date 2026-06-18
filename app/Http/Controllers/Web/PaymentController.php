<?php

namespace App\Http\Controllers\Web;

use App\Models\Order;
use App\Models\PlatformSetting;
use App\Services\Booking\BookingPricingService;
use App\Services\Vendor\VendorWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PaymentController extends WebController
{
    public function show(Order $order): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        if ($order->payment_status === 'success') {
            return redirect()
                ->route('web.bookings.show', $order)
                ->with('success', 'Payment already completed for this booking.');
        }

        $order->load(['vendor', 'category', 'subcategory']);

        return view('web.bookings.payment', [
            'order' => $order,
            'pricing' => BookingPricingService::fromOrder($order),
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function pay(Request $request, Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 403);

        if ($order->payment_status === 'success') {
            return redirect()
                ->route('web.bookings.show', $order)
                ->with('success', 'Payment already completed for this booking.');
        }

        $methods = collect($this->paymentMethods())->pluck('id')->all();

        $data = $request->validate([
            'payment_method' => ['required', 'in:'.implode(',', $methods)],
        ]);

        if ($data['payment_method'] === 'cod' && ! (bool) PlatformSetting::get('enable_cod', false)) {
            return back()->with('error', 'Cash on delivery is not available.');
        }

        $order->update([
            'payment_status' => 'success',
            'payment_method' => $data['payment_method'],
            'paid_at' => now(),
            'status' => $order->status === 'new' ? 'pending_acceptance' : $order->status,
        ]);

        app(VendorWalletService::class)->creditFromPayment($order->fresh());

        return redirect()
            ->route('web.bookings.show', $order)
            ->with('success', 'Payment successful. Your booking is awaiting designer confirmation.');
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

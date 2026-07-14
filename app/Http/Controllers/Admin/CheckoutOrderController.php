<?php

namespace App\Http\Controllers\Admin;

use App\Models\CheckoutOrder;
use App\Support\AdminCityScope;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutOrderController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'orders';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $checkouts = AdminCityScope::scopeCheckoutOrders(
            $this->applyDateRange(CheckoutOrder::query(), $request)
        )
            ->with(['customer', 'subOrders.vendor'])
            ->withCount('subOrders')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('order_number', 'like', $term);
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')))
            ->when(! $request->filled('payment_status'), fn ($q) => $q->paymentConfirmed())
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.checkout-orders.index', compact('checkouts'));
    }

    public function show(CheckoutOrder $checkoutOrder): View
    {
        abort_unless($checkoutOrder->isPaymentConfirmed(), 404);

        $checkoutOrder->load([
            'customer',
            'subOrders.vendor',
            'subOrders.category',
            'subOrders.driver',
            'subOrders.orderItems',
            'subOrders.refund.histories',
            'refunds.histories',
        ]);

        return view('admin.checkout-orders.show', [
            'checkout' => $checkoutOrder,
        ]);
    }
}

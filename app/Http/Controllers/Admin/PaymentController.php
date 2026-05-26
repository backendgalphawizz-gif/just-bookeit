<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'payments';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $payments = $this->applyDateRange(Order::query(), $request)
            ->with(['customer', 'vendor'])
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')))
            ->when($request->filled('search'), fn ($q) => $q->where('order_number', 'like', '%'.$request->string('search').'%'))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $totals = [
            'success' => Order::query()->where('payment_status', 'success')->sum('amount'),
            'pending' => Order::query()->where('payment_status', 'pending')->sum('amount'),
            'failed' => Order::query()->where('payment_status', 'failed')->sum('amount'),
        ];

        return view('admin.payments.index', compact('payments', 'totals'));
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'vendor', 'category']);

        return view('admin.payments.show', compact('order'));
    }
}

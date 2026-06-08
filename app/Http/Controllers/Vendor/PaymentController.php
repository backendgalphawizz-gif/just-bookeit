<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Order;
use App\Models\VendorPayout;
use App\Models\VendorWalletTransaction;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends VendorController
{
    use AppliesListDateFilter;

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);
        $vendor = $this->vendor();

        $transactions = Order::query()
            ->where('vendor_id', $vendor->id)
            ->where('payment_status', 'success')
            ->with(['customer', 'category'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('order_number', 'like', $term)
                        ->orWhere('item_title', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('payment_status', $request->string('status')));
        $transactions = $this->applyDateRange($transactions, $request)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $payouts = VendorPayout::query()
            ->where('vendor_id', $vendor->id)
            ->latest('id')
            ->limit(5)
            ->get();

        $walletTransactions = VendorWalletTransaction::query()
            ->where('vendor_id', $vendor->id)
            ->with(['order', 'refund'])
            ->latest('id')
            ->paginate(15, ['*'], 'wallet_page')
            ->withQueryString();

        $vendor->refresh();

        return view('vendor.payments.index', compact('transactions', 'payouts', 'vendor', 'walletTransactions'));
    }
}

<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Order;
use App\Models\VendorPayout;
use App\Models\VendorWalletTransaction;
use App\Models\VendorWithdrawalRequest;
use App\Services\Vendor\VendorWalletService;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PaymentController extends VendorController
{
    use AppliesListDateFilter;

    public function __construct(
        protected VendorWalletService $wallet
    ) {}

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
            ->with(['order', 'refund', 'withdrawalRequest'])
            ->latest('id')
            ->paginate(15, ['*'], 'wallet_page')
            ->withQueryString();

        $withdrawals = VendorWithdrawalRequest::query()
            ->where('vendor_id', $vendor->id)
            ->newestFirst()
            ->limit(10)
            ->get();

        $vendor->refresh();
        $availableForWithdrawal = $this->wallet->availableForWithdrawal($vendor);

        return view('vendor.payments.index', compact(
            'transactions',
            'payouts',
            'vendor',
            'walletTransactions',
            'withdrawals',
            'availableForWithdrawal'
        ));
    }

    public function requestWithdrawal(Request $request): RedirectResponse
    {
        $vendor = $this->vendor();

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'vendor_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $withdrawal = $this->wallet->requestWithdrawal(
                $vendor,
                (float) $data['amount'],
                $data['vendor_note'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('vendor.payments.index', ['tab' => 'withdrawals'])
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('vendor.payments.index', ['tab' => 'withdrawals'])
            ->with('success', 'Withdrawal request '.$withdrawal->request_code.' submitted for admin review.');
    }
}

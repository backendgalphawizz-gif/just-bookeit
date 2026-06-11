<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\VendorPayout;
use App\Models\VendorWalletTransaction;
use App\Support\Api\VendorApiPresenter;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends VendorApiController
{
    use AppliesListDateFilter;

    public function index(Request $request): JsonResponse
    {
        $this->validateListDateRange($request);
        $vendor = $this->vendor($request);

        $query = VendorWalletTransaction::query()
            ->where('vendor_id', $vendor->id)
            ->with(['order.customer', 'refund'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('description', 'like', $term)
                        ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', $term))
                        ->orWhereHas('order.customer', fn ($customer) => $customer->where('name', 'like', $term));
                });
            })
            ->when($request->filled('type'), fn ($q) => $q->where('direction', $request->string('type')));

        $transactions = $this->applyDateRange($query, $request)
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        $vendor->refresh();

        return $this->success([
            ...VendorApiPresenter::paginator(
                $transactions,
                fn (VendorWalletTransaction $transaction) => VendorApiPresenter::walletTransaction($transaction)
            ),
            'wallet' => [
                'digital_balance' => (float) $vendor->digital_wallet_balance,
                'actual_balance' => (float) $vendor->wallet_balance,
                'currency' => 'INR',
            ],
            'recent_payouts' => VendorPayout::query()
                ->where('vendor_id', $vendor->id)
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (VendorPayout $payout) => [
                    'id' => $payout->id,
                    'payout_code' => $payout->payout_code,
                    'net_amount' => (float) $payout->net_amount,
                    'status' => $payout->status,
                    'paid_at' => $payout->paid_at?->format('M d, Y'),
                ])
                ->values()
                ->all(),
        ]);
    }
}

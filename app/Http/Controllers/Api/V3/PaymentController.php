<?php

namespace App\Http\Controllers\Api\V3;

use App\Models\DriverWalletTransaction;
use App\Support\Api\DriverApiPresenter;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PaymentController extends DriverApiController
{
    use AppliesListDateFilter;

    public function __construct(
        protected \App\Services\Driver\DriverWalletService $wallet
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->validateListDateRange($request);
        $driver = $this->driver($request);

        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:credit,debit'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = DriverWalletTransaction::query()
            ->where('driver_id', $driver->id)
            ->with(['order.customer'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('transaction_code', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', $term))
                        ->orWhereHas('order.customer', fn ($customer) => $customer->where('name', 'like', $term));
                });
            })
            ->when($request->filled('type'), fn ($q) => $q->where('direction', $request->string('type')));

        $transactions = $this->applyDateRange($query, $request)
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        $driver->refresh();

        return $this->success([
            ...DriverApiPresenter::paginator(
                $transactions,
                fn (DriverWalletTransaction $transaction) => DriverApiPresenter::walletTransaction($transaction)
            ),
            'wallet' => [
                'total_earnings' => (float) $driver->total_earnings,
                'available_balance' => (float) $driver->wallet_balance,
                'currency' => 'INR',
                'min_withdrawal' => (float) config('api.driver_min_withdrawal', 100),
            ],
        ]);
    }

    public function withdraw(Request $request): JsonResponse
    {
        $driver = $this->driver($request);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        try {
            $transaction = $this->wallet->withdraw($driver, (float) $data['amount']);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $driver->refresh();

        return $this->success([
            'transaction' => DriverApiPresenter::walletTransaction($transaction),
            'wallet' => [
                'available_balance' => (float) $driver->wallet_balance,
                'total_earnings' => (float) $driver->total_earnings,
            ],
        ], 'Withdrawal request processed.');
    }
}

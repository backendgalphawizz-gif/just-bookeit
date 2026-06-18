<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Vendor;
use App\Http\Requests\Admin\RefundStoreRequest;
use App\Http\Requests\Admin\RefundUpdateRequest;
use App\Services\Vendor\VendorWalletService;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'refunds';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $refunds = $this->applyDateRange(Refund::query(), $request)
            ->with(['customer', 'order.vendor'])
            ->when(
                $request->get('status') === '_open_' || $request->boolean('open_only'),
                fn ($q) => $q->whereIn('status', Refund::OPEN_STATUSES)
            )
            ->when(
                $request->filled('status') && $request->get('status') !== '_open_',
                fn ($q) => $q->where('status', $request->string('status'))
            )
            ->when($request->filled('customer'), function ($q) use ($request) {
                $term = '%'.$request->string('customer').'%';
                $q->whereHas('customer', fn ($customer) => $customer->where('name', 'like', $term));
            })
            ->when($request->filled('vendor_id'), function ($q) use ($request) {
                $q->whereHas('order', fn ($order) => $order->where('vendor_id', $request->integer('vendor_id')));
            })
            ->when($request->filled('order_id'), function ($q) use ($request) {
                $term = '%'.$request->string('order_id').'%';
                $q->whereHas('order', fn ($order) => $order->where('order_number', 'like', $term));
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $vendors = Vendor::query()
            ->where('status', 'active')
            ->orderBy('brand_name')
            ->get(['id', 'brand_name', 'shop_name']);

        return view('admin.refunds.index', compact('refunds', 'vendors'));
    }

    public function create(): View
    {
        return view('admin.refunds.create', [
            'orders' => Order::query()->with('customer')->orderByDesc('created_at')->limit(100)->get(),
            'customers' => Customer::query()->orderBy('name')->get(),
        ]);
    }

    public function store(RefundStoreRequest $request): RedirectResponse
    {
        $refund = Refund::query()->create($request->validated());

        $this->syncRefundWallet($refund->fresh());

        return redirect()->route('admin.refunds.index')->with('success', 'Refund request created.');
    }

    public function show(Refund $refund): View
    {
        $refund->load(['customer', 'order.vendor', 'order.category']);

        return view('admin.refunds.show', compact('refund'));
    }

    public function edit(Refund $refund): View
    {
        return view('admin.refunds.edit', compact('refund'));
    }

    public function update(RefundUpdateRequest $request, Refund $refund): RedirectResponse
    {
        $data = $request->validated();
        $previousStatus = $refund->status;

        $refund->update($data);
        $this->handleRefundStatusChange($refund->fresh(), $previousStatus);

        return redirect()->route('admin.refunds.show', $refund)->with('success', 'Refund updated successfully.');
    }

    public function destroy(Refund $refund): RedirectResponse
    {
        $refund->delete();

        return redirect()->route('admin.refunds.index')->with('success', 'Refund deleted successfully.');
    }

    public function approve(Refund $refund): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $previousStatus = $refund->status;
        $refund->update(['status' => 'approved']);
        $this->handleRefundStatusChange($refund->fresh(), $previousStatus);

        return back()->with('success', 'Refund approved.');
    }

    public function reject(Refund $refund): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $previousStatus = $refund->status;
        $refund->update(['status' => 'rejected']);
        $this->handleRefundStatusChange($refund->fresh(), $previousStatus);

        return back()->with('success', 'Refund rejected.');
    }

    public function process(Refund $refund): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $previousStatus = $refund->status;
        $refund->update(['status' => 'processed']);
        $this->handleRefundStatusChange($refund->fresh(), $previousStatus);

        return back()->with('success', 'Refund marked as processed.');
    }

    protected function syncRefundWallet(Refund $refund): void
    {
        $walletService = app(VendorWalletService::class);

        if (in_array($refund->status, Refund::OPEN_STATUSES, true)) {
            $walletService->debitForRefund($refund);
        }
    }

    protected function handleRefundStatusChange(Refund $refund, string $previousStatus): void
    {
        $walletService = app(VendorWalletService::class);

        if ($previousStatus === 'rejected' && in_array($refund->status, Refund::OPEN_STATUSES, true)) {
            $walletService->debitForRefund($refund);
        }

        if ($refund->status === 'rejected' && in_array($previousStatus, Refund::OPEN_STATUSES, true)) {
            $walletService->restoreForRejectedRefund($refund);
        }

        if ($refund->status === 'processed') {
            $refund->order?->update(['status' => 'refunded', 'payment_status' => 'refunded']);

            $order = $refund->order?->fresh();

            if ($order && $order->vendor_wallet_held_amount <= 0) {
                $order->update(['wallet_hold_status' => 'refunded']);
            }
        }
    }
}

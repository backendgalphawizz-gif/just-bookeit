<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Refund;
use App\Http\Requests\Admin\RefundStoreRequest;
use App\Http\Requests\Admin\RefundUpdateRequest;
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
            ->with(['customer', 'order'])
            ->when(
                $request->get('status') === '_open_' || $request->boolean('open_only'),
                fn ($q) => $q->whereIn('status', Refund::OPEN_STATUSES)
            )
            ->when(
                $request->filled('status') && $request->get('status') !== '_open_',
                fn ($q) => $q->where('status', $request->string('status'))
            )
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->whereHas('customer', fn ($c) => $c->where('name', 'like', $term));
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.refunds.index', compact('refunds'));
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
        Refund::query()->create($request->validated());

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

        $refund->update($data);

        if ($data['status'] === 'processed') {
            $refund->order?->update(['status' => 'refunded', 'payment_status' => 'refunded']);
        }

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

        $refund->update(['status' => 'approved']);

        return back()->with('success', 'Refund approved.');
    }

    public function reject(Refund $refund): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $refund->update(['status' => 'rejected']);

        return back()->with('success', 'Refund rejected.');
    }

    public function process(Refund $refund): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $refund->update(['status' => 'processed']);
        $refund->order?->update(['status' => 'refunded', 'payment_status' => 'refunded']);

        return back()->with('success', 'Refund marked as processed.');
    }
}

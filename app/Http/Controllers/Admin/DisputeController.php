<?php

namespace App\Http\Controllers\Admin;

use App\Models\Dispute;
use App\Models\Order;
use App\Http\Requests\Admin\DisputeStoreRequest;
use App\Http\Requests\Admin\DisputeUpdateRequest;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisputeController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'disputes';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $disputes = $this->applyDateRange(Dispute::query(), $request)
            ->with(['order.customer', 'order.vendor'])
            ->when(
                $request->get('status') === '_open_' || $request->boolean('open_only'),
                fn ($q) => $q->whereIn('status', Dispute::OPEN_STATUSES)
            )
            ->when(
                $request->filled('status') && $request->get('status') !== '_open_',
                fn ($q) => $q->where('status', $request->string('status'))
            )
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.disputes.index', compact('disputes'));
    }

    public function create(): View
    {
        return view('admin.disputes.create', [
            'orders' => Order::query()->with('customer')->orderByDesc('created_at')->limit(100)->get(),
        ]);
    }

    public function store(DisputeStoreRequest $request): RedirectResponse
    {
        Dispute::query()->create($request->validated());

        return redirect()->route('admin.disputes.index')->with('success', 'Dispute created successfully.');
    }

    public function show(Dispute $dispute): View
    {
        $dispute->load(['order.customer', 'order.vendor', 'order.category']);

        return view('admin.disputes.show', compact('dispute'));
    }

    public function edit(Dispute $dispute): View
    {
        return view('admin.disputes.edit', compact('dispute'));
    }

    public function update(DisputeUpdateRequest $request, Dispute $dispute): RedirectResponse
    {
        $dispute->update($request->validated());

        return redirect()->route('admin.disputes.show', $dispute)->with('success', 'Dispute updated successfully.');
    }

    public function destroy(Dispute $dispute): RedirectResponse
    {
        $dispute->delete();

        return redirect()->route('admin.disputes.index')->with('success', 'Dispute deleted successfully.');
    }

    public function resolve(Dispute $dispute): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $dispute->update(['status' => 'resolved']);

        return back()->with('success', 'Dispute marked as resolved.');
    }

    public function close(Dispute $dispute): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $dispute->update(['status' => 'closed']);

        return back()->with('success', 'Dispute closed.');
    }
}

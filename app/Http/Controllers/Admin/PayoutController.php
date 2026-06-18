<?php

namespace App\Http\Controllers\Admin;

use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'payouts';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $payouts = $this->applyDateRange(VendorPayout::query(), $request)
            ->with('vendor')
            ->when(
                $request->get('status') === '_open_' || $request->boolean('open_only'),
                fn ($q) => $q->whereIn('status', VendorPayout::OPEN_STATUSES)
            )
            ->when(
                $request->filled('status') && $request->get('status') !== '_open_',
                fn ($q) => $q->where('status', $request->string('status'))
            )
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('payout_code', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhereHas('vendor', fn ($v) => $v->where('brand_name', 'like', $term));
                });
            })
            ->newestFirst()
            ->paginate(15)
            ->withQueryString();

        $totals = [
            'pending' => VendorPayout::query()->whereIn('status', VendorPayout::OPEN_STATUSES)->sum('net_amount'),
            'paid' => VendorPayout::query()->where('status', 'paid')->sum('net_amount'),
        ];

        $vendors = Vendor::query()->active()->orderBy('brand_name')->get(['id', 'brand_name']);

        return view('admin.payouts.index', compact('payouts', 'totals', 'vendors'));
    }

    public function show(VendorPayout $payout): View
    {
        $payout->load('vendor');

        return view('admin.payouts.show', compact('payout'));
    }

    public function markPaid(Request $request, VendorPayout $payout): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $payout->update([
            ...$data,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return back()->with('success', "Payout {$payout->payout_code} marked as paid.");
    }
}

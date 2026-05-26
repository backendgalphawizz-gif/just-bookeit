<?php

namespace App\Http\Controllers\Admin;

use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'portfolio';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $items = $this->applyDateRange(PortfolioItem::query(), $request)
            ->with(['vendor', 'category'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('title', 'like', $term)
                        ->orWhereHas('vendor', fn ($v) => $v->where('brand_name', 'like', $term));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $vendors = Vendor::query()->orderBy('brand_name')->get(['id', 'brand_name']);

        return view('admin.portfolio.index', compact('items', 'vendors'));
    }

    public function show(PortfolioItem $portfolio): View
    {
        $portfolio->load(['vendor', 'category']);

        return view('admin.portfolio.show', compact('portfolio'));
    }

    public function approve(PortfolioItem $portfolio): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $portfolio->update([
            'status' => 'approved',
            'rejection_reason' => null,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Portfolio item approved.');
    }

    public function reject(Request $request, PortfolioItem $portfolio): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $portfolio->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Portfolio item rejected.');
    }
}

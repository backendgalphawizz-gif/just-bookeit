<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ProductSizeRequest;
use App\Models\ProductSize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductSizeController extends AdminController
{
    protected string $permissionModule = 'sizes';

    public function index(Request $request): View
    {
        $sizes = ProductSize::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where('name', 'like', $term);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->string('status')->toString() === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->string('status')->toString() === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.sizes.index', compact('sizes'));
    }

    public function create(): View
    {
        return view('admin.sizes.create');
    }

    public function store(ProductSizeRequest $request): RedirectResponse
    {
        ProductSize::query()->create($request->validated());

        return redirect()
            ->route('admin.sizes.index')
            ->with('success', 'Size created successfully.');
    }

    public function edit(ProductSize $size): View
    {
        return view('admin.sizes.edit', compact('size'));
    }

    public function update(ProductSizeRequest $request, ProductSize $size): RedirectResponse
    {
        $size->update($request->validated());

        return redirect()
            ->route('admin.sizes.index')
            ->with('success', 'Size updated successfully.');
    }

    public function destroy(ProductSize $size): RedirectResponse
    {
        $size->delete();

        return redirect()
            ->route('admin.sizes.index')
            ->with('success', 'Size deleted successfully.');
    }
}

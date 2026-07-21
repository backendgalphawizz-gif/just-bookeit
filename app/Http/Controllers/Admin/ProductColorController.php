<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ProductColorRequest;
use App\Models\ProductColor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductColorController extends AdminController
{
    protected string $permissionModule = 'colors';

    public function index(Request $request): View
    {
        $colors = ProductColor::query()
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

        return view('admin.colors.index', compact('colors'));
    }

    public function create(): View
    {
        return view('admin.colors.create');
    }

    public function store(ProductColorRequest $request): RedirectResponse
    {
        ProductColor::query()->create($request->validated());

        return redirect()
            ->route('admin.colors.index')
            ->with('success', 'Color created successfully.');
    }

    public function edit(ProductColor $color): View
    {
        return view('admin.colors.edit', compact('color'));
    }

    public function update(ProductColorRequest $request, ProductColor $color): RedirectResponse
    {
        $color->update($request->validated());

        return redirect()
            ->route('admin.colors.index')
            ->with('success', 'Color updated successfully.');
    }

    public function destroy(ProductColor $color): RedirectResponse
    {
        $color->delete();

        return redirect()
            ->route('admin.colors.index')
            ->with('success', 'Color deleted successfully.');
    }
}

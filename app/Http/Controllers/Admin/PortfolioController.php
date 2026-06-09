<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\PortfolioItemImage;
use App\Models\Vendor;
use App\Support\AdminValidationRules;
use App\Support\AppliesListDateFilter;
use App\Support\StoresUploadedFiles;
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
            ->with(['vendor', 'category', 'images'])
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
        $portfolio->load(['vendor', 'category', 'images']);

        return view('admin.portfolio.show', compact('portfolio'));
    }

    public function edit(PortfolioItem $portfolio): View
    {
        $this->authorizeAdmin('edit');

        $portfolio->load(['vendor', 'category', 'images']);

        return view('admin.portfolio.edit', [
            'portfolio' => $portfolio,
            'vendors' => Vendor::query()->orderBy('brand_name')->get(['id', 'brand_name']),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PortfolioItem $portfolio): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $data = $request->validate(
            AdminValidationRules::portfolioItem(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        $portfolio->fill([
            'vendor_id' => $data['vendor_id'],
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'audience' => $data['audience'],
            'status' => $data['status'],
            'rejection_reason' => $data['status'] === 'rejected' ? ($data['rejection_reason'] ?? null) : null,
        ]);

        if ($request->hasFile('image')) {
            $portfolio->image_url = StoresUploadedFiles::replace(
                $request->file('image'),
                $portfolio->image_url,
                'portfolio/images'
            );
        }

        if (in_array($data['status'], ['approved', 'rejected'], true) && ! $portfolio->reviewed_at) {
            $portfolio->reviewed_at = now();
        }

        $portfolio->save();

        if ($request->hasFile('gallery_images')) {
            $sortOrder = (int) ($portfolio->images()->max('sort_order') ?? 0);

            foreach ($request->file('gallery_images') as $file) {
                $sortOrder++;
                PortfolioItemImage::query()->create([
                    'portfolio_item_id' => $portfolio->id,
                    'image_path' => StoresUploadedFiles::store($file, 'portfolio/images'),
                    'sort_order' => $sortOrder,
                ]);
            }
        }

        return redirect()
            ->route('admin.portfolio.show', $portfolio)
            ->with('success', 'Portfolio item updated successfully.');
    }

    public function destroyImage(PortfolioItem $portfolio, PortfolioItemImage $image): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        abort_unless($image->portfolio_item_id === $portfolio->id, 404);

        StoresUploadedFiles::delete($image->image_path);
        $image->delete();

        return back()->with('success', 'Gallery image removed.');
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

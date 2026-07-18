<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\PortfolioItemImage;
use App\Models\Vendor;
use App\Support\AdminValidationRules;
use App\Support\AppliesListDateFilter;
use App\Support\ManagesPortfolioProducts;
use App\Support\ProductDamageDeductionRules;
use App\Support\StoresUploadedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioController extends AdminController
{
    use AppliesListDateFilter;
    use ManagesPortfolioProducts;

    protected string $permissionModule = 'portfolio';

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $typeTabs = $this->productTypeTabs();
        $type = $this->resolveProductTypeTab($request->string('type')->toString(), $typeTabs);
        $typeCategory = $typeTabs->firstWhere('slug', $type);

        $items = $this->applyDateRange(PortfolioItem::query(), $request)
            ->with(['vendor', 'category', 'subcategory.parent', 'images'])
            ->when($typeCategory, fn ($q) => $q->where('category_id', $typeCategory->id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('title', 'like', $term)
                        ->orWhereHas('vendor', fn ($v) => $v->where('brand_name', 'like', $term));
                });
            })
            ->newestFirst()
            ->paginate(15)
            ->withQueryString();

        $vendors = Vendor::query()->orderBy('brand_name')->get(['id', 'brand_name']);

        $tabCounts = PortfolioItem::query()
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->whereIn('category_id', $typeTabs->pluck('id'))
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        return view('admin.portfolio.index', compact('items', 'vendors', 'typeTabs', 'type', 'tabCounts'));
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin('create');

        $typeTabs = $this->productTypeTabs();
        $type = $this->resolveProductTypeTab($request->string('type')->toString(), $typeTabs);
        $typeCategory = $typeTabs->firstWhere('slug', $type);

        return view('admin.portfolio.create', array_merge(
            $this->formViewData(new PortfolioItem([
                'status' => 'pending',
                'audience' => 'women',
                'category_id' => $typeCategory?->id,
            ])),
            ['type' => $type]
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin('create');

        $this->normalizeProductFormInput($request);
        $data = $request->validate(
            AdminValidationRules::portfolioItem(true),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        ProductDamageDeductionRules::assertWithinCategoryLimit(
            (int) $data['subcategory_id'],
            (int) $data['category_id'],
            $data['damage_deductions'] ?? []
        );

        $imagePath = StoresUploadedFiles::store($request->file('image'), 'portfolio/images');

        $product = PortfolioItem::query()->create([
            'vendor_id' => $data['vendor_id'],
            'category_id' => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price_per_day' => $data['price_per_day'],
            'advance_amount' => $data['advance_amount'] ?? null,
            'audience' => $data['audience'],
            'image_url' => $imagePath,
            'status' => $data['status'],
            'rejection_reason' => $data['status'] === 'rejected' ? ($data['rejection_reason'] ?? null) : null,
            'reviewed_at' => in_array($data['status'], ['approved', 'rejected'], true) ? now() : null,
        ]);

        $this->storeProductGalleryImages($request, $product, $request->file('image'));
        $this->storeProductGalleryVideos($request, $product);
        $this->syncProductVariants($request, $product, $data['variants'] ?? []);
        $this->syncProductDamageDeductions($product, $data['damage_deductions'] ?? []);

        return redirect()
            ->route('admin.portfolio.show', $product)
            ->with('success', 'Product created successfully.');
    }

    public function show(PortfolioItem $portfolio): View
    {
        $portfolio->load(['vendor', 'category', 'subcategory.parent', 'images', 'variants', 'damageDeductions']);

        return view('admin.portfolio.show', compact('portfolio'));
    }

    public function edit(PortfolioItem $portfolio): View
    {
        $this->authorizeAdmin('edit');

        $portfolio->load(['vendor', 'category', 'subcategory.parent', 'images', 'variants', 'damageDeductions']);

        return view('admin.portfolio.edit', $this->formViewData($portfolio));
    }

    public function update(Request $request, PortfolioItem $portfolio): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $this->normalizeProductFormInput($request);

        $data = $request->validate(
            AdminValidationRules::portfolioItem(false),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        ProductDamageDeductionRules::assertWithinCategoryLimit(
            (int) $data['subcategory_id'],
            (int) $data['category_id'],
            $data['damage_deductions'] ?? []
        );

        $portfolio->fill([
            'vendor_id' => $data['vendor_id'],
            'category_id' => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price_per_day' => $data['price_per_day'] ?? $portfolio->price_per_day,
            'advance_amount' => array_key_exists('advance_amount', $data) ? $data['advance_amount'] : $portfolio->advance_amount,
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

        $this->storeProductGalleryImages($request, $portfolio);
        $this->storeProductGalleryVideos($request, $portfolio);
        $this->syncProductVariants($request, $portfolio, $data['variants'] ?? [], true);
        $this->syncProductDamageDeductions($portfolio, $data['damage_deductions'] ?? [], true);

        return redirect()
            ->route('admin.portfolio.show', $portfolio)
            ->with('success', 'Product updated successfully.');
    }

    public function destroyImage(PortfolioItem $portfolio, PortfolioItemImage $image): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        abort_unless($image->portfolio_item_id === $portfolio->id, 404);

        $wasVideo = $image->isVideo();
        StoresUploadedFiles::delete($image->image_path);
        $image->delete();

        return back()->with('success', $wasVideo ? 'Gallery video removed.' : 'Gallery image removed.');
    }

    public function approve(PortfolioItem $portfolio): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $portfolio->update([
            'status' => 'approved',
            'rejection_reason' => null,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Product approved.');
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

        return back()->with('success', 'Product rejected.');
    }

    /** @return array<string, mixed> */
    protected function formViewData(PortfolioItem $portfolio): array
    {
        $portfolio->loadMissing('subcategory.parent');

        return [
            'portfolio' => $portfolio,
            'vendors' => Vendor::query()->orderBy('brand_name')->get(['id', 'brand_name']),
            'serviceCategories' => $this->serviceCategories(),
            'mainCategories' => Category::query()->main()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'subcategories' => Category::query()->sub()->active()->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }

    protected function serviceCategories()
    {
        return Category::query()
            ->service()
            ->active()
            ->whereIn('slug', ['fashion-designer', 'rented-dress', 'rented-jewellery'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function productTypeTabs()
    {
        $preferredOrder = ['fashion-designer', 'rented-dress', 'rented-jewellery'];

        return $this->serviceCategories()
            ->sortBy(function (Category $category) use ($preferredOrder) {
                $index = array_search($category->slug, $preferredOrder, true);

                return $index === false ? 99 : $index;
            })
            ->values();
    }

    protected function resolveProductTypeTab(string $type, $tabs): string
    {
        $slugs = $tabs->pluck('slug')->all();

        if ($type !== '' && in_array($type, $slugs, true)) {
            return $type;
        }

        return $slugs[0] ?? 'fashion-designer';
    }
}

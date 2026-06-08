<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Support\AppliesListDateFilter;
use App\Support\StoresUploadedFiles;
use App\Support\VendorValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends VendorController
{
    use AppliesListDateFilter;
    protected array $typeMap = [
        'fashion-designer' => 'Fashion Designer',
        'rented-dress' => 'Rented Dress',
        'rented-jewellery' => 'Rented Jewellery',
    ];

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);
        $vendor = $this->vendor();
        $type = $request->string('type', 'fashion-designer')->toString();
        $category = Category::query()->where('slug', $type)->first();

        $items = PortfolioItem::query()
            ->where('vendor_id', $vendor->id)
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->with('category');
        $items = $this->applyDateRange($items, $request)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('vendor.products.index', [
            'items' => $items,
            'type' => $type,
            'typeLabel' => $this->typeMap[$type] ?? 'Products',
            'category' => $category,
        ]);
    }

    public function create(Request $request): View
    {
        $type = $request->string('type', 'fashion-designer')->toString();
        $category = Category::query()->where('slug', $type)->firstOrFail();

        return view('vendor.products.form', [
            'item' => new PortfolioItem(['status' => 'pending']),
            'type' => $type,
            'typeLabel' => $this->typeMap[$type] ?? 'Product',
            'category' => $category,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vendor = $this->vendor();
        $type = $request->string('type', 'fashion-designer')->toString();
        $category = Category::query()->where('slug', $type)->firstOrFail();

        $data = $this->validateVendor($request, VendorValidationRules::product(true));

        $imagePath = StoresUploadedFiles::store($request->file('image'), 'portfolio/images');

        PortfolioItem::query()->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'image_url' => $imagePath,
            'status' => 'pending',
        ]);

        return redirect()->route('vendor.products.index', ['type' => $type])
            ->with('success', 'Product submitted for approval.');
    }

    public function edit(PortfolioItem $product): View
    {
        abort_unless($product->vendor_id === $this->vendor()->id, 403);
        $product->load('category');
        $type = $product->category?->slug ?? 'fashion-designer';

        return view('vendor.products.form', [
            'item' => $product,
            'type' => $type,
            'typeLabel' => $this->typeMap[$type] ?? 'Product',
            'category' => $product->category,
        ]);
    }

    public function update(Request $request, PortfolioItem $product): RedirectResponse
    {
        abort_unless($product->vendor_id === $this->vendor()->id, 403);

        $data = $this->validateVendor($request, VendorValidationRules::product(false));

        $product->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
            'rejection_reason' => null,
        ]);

        if ($request->hasFile('image')) {
            $product->image_url = StoresUploadedFiles::replace(
                $request->file('image'),
                $product->image_url,
                'portfolio/images'
            );
        }

        $product->save();
        $type = $product->category?->slug ?? 'fashion-designer';

        return redirect()->route('vendor.products.index', ['type' => $type])
            ->with('success', 'Product updated and sent for re-approval.');
    }

    public function destroy(PortfolioItem $product): RedirectResponse
    {
        abort_unless($product->vendor_id === $this->vendor()->id, 403);
        $type = $product->category?->slug ?? 'fashion-designer';
        $product->delete();

        return redirect()->route('vendor.products.index', ['type' => $type])
            ->with('success', 'Product removed.');
    }
}

<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\PortfolioItemImage;
use App\Support\AppliesListDateFilter;
use App\Support\ManagesPortfolioProducts;
use App\Support\StoresUploadedFiles;
use App\Support\VendorValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends VendorController
{
    use AppliesListDateFilter;
    use ManagesPortfolioProducts;

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
            'item' => new PortfolioItem(['status' => 'pending', 'audience' => 'women']),
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

        $this->normalizeProductFormInput($request);
        $data = $this->validateVendor($request, array_merge(
            VendorValidationRules::product(true),
            $this->productUploadRules(true)
        ));

        $imagePath = StoresUploadedFiles::store($request->file('image'), 'portfolio/images');

        $product = PortfolioItem::query()->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price_per_day' => $data['price_per_day'],
            'advance_amount' => $data['advance_amount'] ?? null,
            'audience' => $data['audience'] ?? 'women',
            'image_url' => $imagePath,
            'status' => 'pending',
        ]);

        $this->storeProductGalleryImages($request, $product, $request->file('image'));
        $this->syncProductVariants($request, $product, $data['variants'] ?? []);
        $this->syncProductDamageDeductions($product, $data['damage_deductions'] ?? []);

        return redirect()->route('vendor.products.index', ['type' => $type])
            ->with('success', 'Product submitted for approval.');
    }

    public function edit(PortfolioItem $product): View
    {
        abort_unless($product->vendor_id === $this->vendor()->id, 403);
        $product->load(['category', 'images', 'variants', 'damageDeductions']);
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

        $this->normalizeProductFormInput($request);
        $data = $this->validateVendor($request, array_merge(
            VendorValidationRules::product(false),
            $this->productUploadRules(false)
        ));

        $product->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price_per_day' => $data['price_per_day'] ?? $product->price_per_day,
            'advance_amount' => array_key_exists('advance_amount', $data) ? $data['advance_amount'] : $product->advance_amount,
            'audience' => $data['audience'] ?? $product->audience,
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

        $this->storeProductGalleryImages($request, $product);
        $this->syncProductVariants($request, $product, $data['variants'] ?? [], true);
        $this->syncProductDamageDeductions($product, $data['damage_deductions'] ?? [], true);

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

    public function destroyImage(PortfolioItem $product, PortfolioItemImage $image): RedirectResponse
    {
        abort_unless($product->vendor_id === $this->vendor()->id, 403);
        abort_unless($image->portfolio_item_id === $product->id, 404);

        StoresUploadedFiles::delete($image->image_path);
        $image->delete();

        return back()->with('success', 'Gallery image removed.');
    }

    /** @return array<string, mixed> */
    protected function productUploadRules(bool $creating): array
    {
        $imageRule = $creating ? 'required' : 'nullable';
        $fileRule = ['image', 'mimes:jpeg,jpg,png,webp', 'max:'.VendorValidationRules::MAX_IMAGE_KB];

        return [
            'image' => [$imageRule, ...$fileRule],
            'gallery_images' => ['nullable', 'array', 'max:10'],
            'gallery_images.*' => $fileRule,
            'variant_images' => ['nullable', 'array', 'max:50'],
            'variant_images.*' => ['nullable', ...$fileRule],
        ];
    }
}

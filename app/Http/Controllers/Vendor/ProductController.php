<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\PortfolioItemImage;
use App\Support\AppliesListDateFilter;
use App\Support\ManagesPortfolioProducts;
use App\Support\ProductDamageDeductionRules;
use App\Support\StoresUploadedFiles;
use App\Support\SubcategoryCatalog;
use App\Support\VendorValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
        $type = $this->resolveAllowedProductType($request->string('type')->toString());
        $category = Category::query()->where('slug', $type)->first();

        $items = PortfolioItem::query()
            ->where('vendor_id', $vendor->id)
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($request->filled('subcategory_id'), fn ($q) => $q->where('subcategory_id', $request->integer('subcategory_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('listing'), function ($q) use ($request) {
                $listing = $request->string('listing')->toString();
                if ($listing === 'active') {
                    $q->where('is_listing_active', true);
                } elseif ($listing === 'inactive') {
                    $q->where(function ($q) {
                        $q->where('is_listing_active', false)->orWhereNull('is_listing_active');
                    });
                }
            })
            ->with(['category', 'subcategory.parent', 'vendor', 'variants']);
        $items = $this->applyDateRange($items, $request)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('vendor.products.index', [
            'items' => $items,
            'type' => $type,
            'typeLabel' => match ($type) {
                'rented-dress' => 'Rented Dresses',
                'rented-jewellery' => 'Rented Jewellery',
                default => $this->typeMap[$type] ?? 'Products',
            },
            'category' => $category,
        ]);
    }

    public function create(Request $request, string $type): View
    {
        $type = $this->resolveAllowedProductType($type);
        $category = Category::query()->where('slug', $type)->firstOrFail();

        return view('vendor.products.form', $this->formViewData(
            new PortfolioItem(['status' => 'pending', 'audience' => 'women']),
            $type,
            $category
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $vendor = $this->vendor();
        $type = $this->resolveAllowedProductType($request->string('type')->toString());
        $category = Category::query()->where('slug', $type)->firstOrFail();

        $this->normalizeProductFormInput($request);
        $data = $this->validateVendor($request, array_merge(
            VendorValidationRules::product(true, $type),
            $this->productUploadRules(true, $type)
        ));

        $subcategory = SubcategoryCatalog::resolveSubcategory((int) $data['subcategory_id']);

        abort_unless($subcategory, 422, 'Select a valid sub-category.');

        $isRental = in_array($type, ['rented-dress', 'rented-jewellery'], true);

        if ($isRental) {
            ProductDamageDeductionRules::assertWithinCategoryLimit(
                $subcategory->id,
                $category->id,
                $data['damage_deductions'] ?? []
            );
        }

        $primaryImage = $request->file('image');
        $imagePath = null;

        if ($primaryImage instanceof UploadedFile) {
            $imagePath = StoresUploadedFiles::store($primaryImage, 'portfolio/images');
        } elseif ($type === 'rented-dress') {
            $mediaImage = $this->firstUploadedMediaImage($request);
            if ($mediaImage instanceof UploadedFile) {
                $imagePath = StoresUploadedFiles::store($mediaImage, 'portfolio/images');
                $primaryImage = $mediaImage;
            }
        } elseif ($type === 'rented-jewellery') {
            $mediaImage = $this->firstUploadedMediaImage($request);
            if ($mediaImage instanceof UploadedFile) {
                $imagePath = StoresUploadedFiles::store($mediaImage, 'portfolio/images');
                $primaryImage = $mediaImage;
            }
        }

        $hasDressVariantImage = $type === 'rented-dress' && collect((array) $request->file('variants', []))
            ->contains(fn ($row) => is_array($row) && ($row['image'] ?? null) instanceof UploadedFile);

        abort_unless(
            filled($imagePath) || $hasDressVariantImage,
            422,
            $type === 'rented-dress'
                ? 'Please upload at least one product image or variant image.'
                : 'Please upload at least one image.'
        );

        $product = PortfolioItem::query()->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price_per_day' => $type === 'rented-dress' ? null : ($data['price_per_day'] ?? null),
            'advance_amount' => $type === 'rented-dress'
                ? null
                : (array_key_exists('advance_amount', $data) ? $data['advance_amount'] : null),
            'audience' => SubcategoryCatalog::audienceFromSubcategory($subcategory) ?? $data['audience'] ?? 'women',
            'image_url' => $imagePath,
            'status' => 'pending',
        ]);

        $this->storeProductGalleryImages($request, $product, $primaryImage instanceof UploadedFile ? $primaryImage : null);
        $this->storeProductGalleryVideos($request, $product);
        $this->ensurePrimaryImageInGallery($product);

        if ($type === 'rented-dress') {
            $this->syncProductVariants($request, $product, $request->input('variants', $data['variants'] ?? []));
            $this->propagateVariantColorImages($product);

            if (blank($product->image_url)) {
                $firstVariantImage = $product->variants()
                    ->whereNotNull('image_path')
                    ->orderBy('sort_order')
                    ->value('image_path');
                if ($firstVariantImage) {
                    $product->update(['image_url' => $firstVariantImage]);
                    $this->ensurePrimaryImageInGallery($product);
                }
            }
        }

        if ($isRental) {
            $this->syncProductDamageDeductions($product, $data['damage_deductions'] ?? []);
        }

        return redirect()->route('vendor.products.index', ['type' => $type])
            ->with('success', 'Product submitted for approval.');
    }

    public function show(PortfolioItem $product): View
    {
        abort_unless($product->vendor_id === $this->vendor()->id, 403);
        $this->ensurePrimaryImageInGallery($product);
        $product->load(['category', 'subcategory.parent', 'images', 'vendor', 'variants', 'damageDeductions']);
        $type = $product->category?->slug ?? 'fashion-designer';

        return view('vendor.products.show', [
            'item' => $product,
            'type' => $type,
            'typeLabel' => $this->typeMap[$type] ?? 'Product',
        ]);
    }

    public function edit(PortfolioItem $product): View
    {
        abort_unless($product->vendor_id === $this->vendor()->id, 403);
        $this->ensurePrimaryImageInGallery($product);
        $product->load(['category', 'subcategory.parent', 'images', 'variants', 'damageDeductions']);
        $type = $product->category?->slug ?? 'fashion-designer';

        return view('vendor.products.form', $this->formViewData($product, $type, $product->category));
    }

    public function update(Request $request, PortfolioItem $product): RedirectResponse
    {
        abort_unless($product->vendor_id === $this->vendor()->id, 403);

        $type = $product->category?->slug ?? $this->resolveAllowedProductType($request->string('type')->toString());
        $isRental = in_array($type, ['rented-dress', 'rented-jewellery'], true);

        $this->normalizeProductFormInput($request);
        $data = $this->validateVendor($request, array_merge(
            VendorValidationRules::product(false, $type),
            $this->productUploadRules(false, $type)
        ));

        if ($isRental) {
            $subcategoryId = array_key_exists('subcategory_id', $data)
                ? (int) $data['subcategory_id']
                : (int) $product->subcategory_id;

            ProductDamageDeductionRules::assertWithinCategoryLimit(
                $subcategoryId,
                (int) $product->category_id,
                $data['damage_deductions'] ?? []
            );
        }

        $updates = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
            'rejection_reason' => null,
        ];

        if ($type !== 'rented-dress') {
            $updates['price_per_day'] = $data['price_per_day'] ?? $product->price_per_day;
            $updates['advance_amount'] = array_key_exists('advance_amount', $data)
                ? $data['advance_amount']
                : $product->advance_amount;
        }

        if (array_key_exists('subcategory_id', $data)) {
            $subcategory = SubcategoryCatalog::resolveSubcategory((int) $data['subcategory_id']);
            abort_unless($subcategory, 422, 'Select a valid sub-category.');
            $updates['subcategory_id'] = $subcategory->id;
            $updates['audience'] = SubcategoryCatalog::audienceFromSubcategory($subcategory) ?? $product->audience;
        } elseif (! empty($data['audience'])) {
            $updates['audience'] = $data['audience'];
        }

        $product->fill($updates);

        $primaryImage = $request->file('image');

        // Color uploads must not replace the banner — only an explicit cover `image` does.
        if ($primaryImage instanceof UploadedFile) {
            $product->image_url = StoresUploadedFiles::replace(
                $primaryImage,
                $product->image_url,
                'portfolio/images'
            );
        } elseif ($type === 'rented-dress' && blank($product->image_url)) {
            $fallback = $this->firstStoredVariantImagePath($request->input('variants', $data['variants'] ?? []));
            if ($fallback) {
                $product->image_url = $fallback;
            }
        } elseif ($type === 'rented-jewellery' && blank($product->image_url)) {
            $mediaImage = $this->firstUploadedMediaImage($request);
            if ($mediaImage instanceof UploadedFile) {
                $product->image_url = StoresUploadedFiles::store($mediaImage, 'portfolio/images');
                $primaryImage = $mediaImage;
            }
        }

        $product->save();

        $this->storeProductGalleryImages($request, $product, $primaryImage instanceof UploadedFile ? $primaryImage : null);
        $this->storeProductGalleryVideos($request, $product);
        $this->ensurePrimaryImageInGallery($product);

        if ($type === 'rented-dress') {
            $this->syncProductVariants($request, $product, $request->input('variants', $data['variants'] ?? []), true);
            $this->propagateVariantColorImages($product);

            if (blank($product->image_url)) {
                $firstVariantImage = $product->variants()
                    ->whereNotNull('image_path')
                    ->orderBy('sort_order')
                    ->value('image_path');
                if ($firstVariantImage) {
                    $product->update(['image_url' => $firstVariantImage]);
                    $this->ensurePrimaryImageInGallery($product);
                }
            }
        }

        if ($isRental) {
            $this->syncProductDamageDeductions($product, $data['damage_deductions'] ?? [], true);
        }

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

    public function toggleListingActive(Request $request, PortfolioItem $product): RedirectResponse
    {
        abort_unless($product->vendor_id === $this->vendor()->id, 403);

        if ($product->status !== 'approved') {
            return back()->with('error', 'Only approved products can be marked active or inactive.');
        }

        $product->update([
            'is_listing_active' => $request->boolean('is_listing_active'),
        ]);

        return back()->with(
            'success',
            $product->is_listing_active ? 'Product is now active.' : 'Product is now inactive.'
        );
    }

    public function destroyImage(PortfolioItem $product, PortfolioItemImage $image): RedirectResponse
    {
        abort_unless($product->vendor_id === $this->vendor()->id, 403);
        abort_unless($image->portfolio_item_id === $product->id, 404);

        $path = $image->image_path;
        $wasPrimary = $product->image_url === $path;
        $image->delete();

        if ($wasPrimary) {
            $next = $product->images()
                ->where('media_type', '!=', PortfolioItemImage::TYPE_VIDEO)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('image_path');

            $product->update(['image_url' => $next]);
        }

        $product->refresh();
        $stillUsed = $product->image_url === $path
            || $product->images()->where('image_path', $path)->exists();

        if (! $stillUsed) {
            StoresUploadedFiles::delete($path);
        }

        return back()->with('success', 'Gallery image removed.');
    }

    /** @return array<string, mixed> */
    protected function formViewData(PortfolioItem $item, string $type, ?Category $category): array
    {
        return [
            'item' => $item,
            'type' => $type,
            'typeLabel' => $this->typeMap[$type] ?? 'Product',
            'category' => $category,
            'mainCategories' => Category::query()->main()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'subcategories' => Category::query()->sub()->active()->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }

    /** @return array<string, mixed> */
    protected function productUploadRules(bool $creating, string $type = ''): array
    {
        // Dress/jewelry use media_files; fashion uses image.
        $imageRequired = $creating && $type === 'fashion-designer';
        $imageRule = $imageRequired ? 'required' : 'nullable';
        $fileRule = ['image', 'mimes:jpeg,jpg,png,webp,svg', 'max:'.VendorValidationRules::MAX_IMAGE_KB];
        $videoRule = VendorValidationRules::productVideoUploadRules();
        $mediaRequired = $creating && $type === 'rented-jewellery';

        return [
            'image' => [$imageRule, ...$fileRule],
            'gallery_images' => ['nullable', 'array', 'max:10'],
            'gallery_images.*' => $fileRule,
            'gallery_videos' => ['nullable', 'array', 'max:5'],
            'gallery_videos.*' => $videoRule,
            'media_files' => [$mediaRequired ? 'required' : 'nullable', 'array', 'max:15'],
            'media_files.*' => ['file', 'max:'.VendorValidationRules::MAX_VIDEO_KB],
            'colors.*.image' => ['nullable', ...$fileRule],
            'variants.*.image' => ['nullable', ...$fileRule],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['string', 'max:50'],
            'colors' => ['nullable', 'array', 'max:50'],
            'colors.*.color' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function resolveAllowedProductType(string $type): string
    {
        $allowed = VendorValidationRules::serviceTypeSlugs($this->vendor()->selectedServiceTypes());

        if ($allowed === []) {
            $allowed = array_keys($this->typeMap);
        }

        if ($type === '' || ! in_array($type, $allowed, true)) {
            return $allowed[0];
        }

        return $type;
    }
}

<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\PortfolioItemDamageDeduction;
use App\Models\PortfolioItemImage;
use App\Models\PortfolioItemVariant;
use App\Support\Api\VendorApiPresenter;
use App\Support\AppliesListDateFilter;
use App\Support\ProductDamageDeductionRules;
use App\Support\ProductVariantUpload;
use App\Support\StoresUploadedFiles;
use App\Support\SubcategoryCatalog;
use App\Support\VendorValidationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ProductController extends VendorApiController
{
    use AppliesListDateFilter;

    /** @var array<string, string> */
    protected array $typeMap = [
        'fashion-designer' => 'Fashion Designer',
        'rented-dress' => 'Rented Dress',
        'rented-jewellery' => 'Rented Jewellery',
    ];

    public function index(Request $request): JsonResponse
    {
        $this->validateListDateRange($request);
        $vendor = $this->vendor($request);
        $type = $request->string('type', 'rented-dress')->toString();
        $category = Category::query()->where('slug', $type)->first();

        $query = PortfolioItem::query()
            ->where('vendor_id', $vendor->id)
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($request->filled('subcategory_id'), fn ($q) => $q->where('subcategory_id', $request->integer('subcategory_id')))
            ->when($request->filled('category_id') && ! $request->filled('subcategory_id'), function ($q) use ($request) {
                $main = Category::query()->find($request->integer('category_id'));

                if ($main?->isMain()) {
                    $q->whereHas('subcategory', fn ($sub) => $sub->where('parent_id', $main->id));
                }
            })
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->with(['category', 'subcategory.parent', 'vendor', 'images', 'variants', 'damageDeductions']);

        $products = $this->applyDateRange($query, $request)
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            ...VendorApiPresenter::paginator($products, fn (PortfolioItem $item) => VendorApiPresenter::productSummary($item)),
            'category_type' => $type,
            'category_label' => $this->typeMap[$type] ?? 'Products',
            'vendor_is_available' => (bool) $vendor->is_listing_active,
        ]);
    }

    public function show(Request $request, PortfolioItem $product): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsProduct($product, $vendor);

        return $this->success(VendorApiPresenter::productDetail($product));
    }

    public function store(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->mergeProductAliases($request);
        $this->normalizeProductFileInputs($request);
        $this->decodeJsonPayloadFields($request);

        $type = $request->string('type', 'rented-dress')->toString();
        $category = Category::query()->where('slug', $type)->firstOrFail();

        $this->assertHasProductImage($request);
        $this->validateProductUploads($request);
        ProductVariantUpload::validateImages($request);
        $data = $this->validateVendor($request, array_merge(
            VendorValidationRules::product(true),
            VendorValidationRules::productTypeRules()
        ));

        $subcategory = SubcategoryCatalog::resolveSubcategory((int) $data['subcategory_id'], $category->id);

        if (! $subcategory) {
            throw ValidationException::withMessages([
                'subcategory_id' => ['Select a valid sub-category for this service type.'],
            ]);
        }

        $this->assertSubcategoryMatchesMainCategory($request, $subcategory);
        SubcategoryCatalog::assertBelongsToServiceCategory($subcategory, $category->id);

        ProductDamageDeductionRules::assertWithinCategoryLimit(
            $subcategory->id,
            $category->id,
            $data['damage_deductions'] ?? []
        );

        $primaryImage = $this->resolvePrimaryImage($request);
        $imagePath = StoresUploadedFiles::store($primaryImage, 'portfolio/images');

        $product = PortfolioItem::query()->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price_per_day' => $data['price_per_day'],
            'advance_amount' => $this->resolveAdvanceAmount($request),
            'audience' => SubcategoryCatalog::audienceFromSubcategory($subcategory) ?? $data['audience'] ?? 'women',
            'image_url' => $imagePath,
            'status' => 'pending',
        ]);

        $this->storeGalleryImages($request, $product, $primaryImage);
        $this->syncVariants($request, $product, $data['variants'] ?? []);
        $this->syncDamageDeductions($product, $data['damage_deductions'] ?? []);

        return $this->success([
            'product' => VendorApiPresenter::productDetail($product->load(['category', 'subcategory.parent', 'vendor', 'images', 'variants', 'damageDeductions'])),
        ], 'Product submitted for approval.', 201);
    }

    public function update(Request $request, PortfolioItem $product): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsProduct($product, $vendor);
        $this->mergeProductAliases($request);
        $this->normalizeProductFileInputs($request);
        $this->decodeJsonPayloadFields($request);

        $this->validateProductUploads($request);
        ProductVariantUpload::validateImages($request);
        $data = $this->validateVendor($request, array_merge(
            VendorValidationRules::product(false),
            VendorValidationRules::productTypeRules()
        ));

        $updates = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
            'rejection_reason' => null,
        ];

        if ($request->filled('type')) {
            $category = Category::query()->where('slug', $request->string('type'))->firstOrFail();
            $updates['category_id'] = $category->id;
        }

        if (array_key_exists('subcategory_id', $data)) {
            $serviceCategoryId = (int) ($updates['category_id'] ?? $product->category_id);
            $subcategory = SubcategoryCatalog::resolveSubcategory((int) $data['subcategory_id'], $serviceCategoryId);

            if (! $subcategory) {
                throw ValidationException::withMessages([
                    'subcategory_id' => ['Select a valid sub-category for this service type.'],
                ]);
            }

            $this->assertSubcategoryMatchesMainCategory($request, $subcategory);
            SubcategoryCatalog::assertBelongsToServiceCategory($subcategory, $serviceCategoryId);

            $updates['subcategory_id'] = $subcategory->id;
            $updates['audience'] = SubcategoryCatalog::audienceFromSubcategory($subcategory) ?? $product->audience;
        } elseif (! empty($data['audience'])) {
            $updates['audience'] = $data['audience'];
        }

        if (array_key_exists('price_per_day', $data)) {
            $updates['price_per_day'] = $data['price_per_day'];
        }

        if ($request->has('advance_amount')) {
            $updates['advance_amount'] = $this->resolveAdvanceAmount($request);
        }

        if (array_key_exists('damage_deductions', $data)) {
            $subcategoryId = (int) ($updates['subcategory_id'] ?? $product->subcategory_id);

            ProductDamageDeductionRules::assertWithinCategoryLimit(
                $subcategoryId,
                (int) ($updates['category_id'] ?? $product->category_id),
                $data['damage_deductions'] ?? []
            );
        }

        $product->fill($updates);

        if ($request->hasFile('image') || $request->hasFile('product_image')) {
            $product->image_url = StoresUploadedFiles::replace(
                $request->file('image') ?? $request->file('product_image'),
                $product->image_url,
                'portfolio/images'
            );
        }

        $product->save();

        $this->storeGalleryImages($request, $product);

        if (array_key_exists('variants', $data)) {
            $this->syncVariants($request, $product, $data['variants'] ?? [], true);
        }

        if (array_key_exists('damage_deductions', $data)) {
            $this->syncDamageDeductions($product, $data['damage_deductions'] ?? [], true);
        }

        return $this->success([
            'product' => VendorApiPresenter::productDetail($product->fresh(['category', 'subcategory.parent', 'vendor', 'images', 'variants', 'damageDeductions'])),
        ], 'Product updated and sent for re-approval.');
    }

    public function destroy(Request $request, PortfolioItem $product): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsProduct($product, $vendor);

        $product->delete();

        return $this->success(null, 'Product removed.');
    }

    public function markAvailable(Request $request, PortfolioItem $product): JsonResponse
    {
        return $this->setProductAvailability($request, $product, true);
    }

    public function markUnavailable(Request $request, PortfolioItem $product): JsonResponse
    {
        return $this->setProductAvailability($request, $product, false);
    }

    public function toggleAvailability(Request $request, PortfolioItem $product): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsProduct($product, $vendor);

        $data = $request->validate([
            'is_available' => ['required', 'boolean'],
        ]);

        return $this->setProductAvailability($request, $product, (bool) $data['is_available']);
    }

    protected function setProductAvailability(Request $request, PortfolioItem $product, bool $isAvailable): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsProduct($product, $vendor);

        if ($product->status !== 'approved') {
            return $this->error('Only approved products can be marked available or unavailable.', 422);
        }

        $product->update(['is_listing_active' => $isAvailable]);
        $product = $product->fresh(['category', 'subcategory.parent', 'vendor', 'images', 'variants', 'damageDeductions']);

        return $this->success([
            'product' => VendorApiPresenter::productDetail($product),
            'is_available' => $product->isCatalogAvailable(),
            'is_listing_active' => (bool) $product->is_listing_active,
            'availability_status' => $product->isCatalogAvailable() ? 'available' : 'unavailable',
        ], $isAvailable ? 'Product is now available.' : 'Product is now unavailable.');
    }

    protected function assertSubcategoryMatchesMainCategory(Request $request, Category $subcategory): void
    {
        $mainCategoryId = $this->resolveMainCategoryId($request);

        if ($mainCategoryId === null) {
            return;
        }

        SubcategoryCatalog::assertBelongsToMainCategory($subcategory, $mainCategoryId);
    }

    protected function resolveMainCategoryId(Request $request): ?int
    {
        foreach (['main_category_id', 'shop_category_id'] as $field) {
            if ($request->filled($field)) {
                return $request->integer($field);
            }
        }

        if ($request->filled('category_id') && ! $request->filled('type')) {
            $category = Category::query()->find($request->integer('category_id'));

            if ($category?->isMain()) {
                return $category->id;
            }
        }

        return null;
    }

    protected function mergeProductAliases(Request $request): void
    {
        if ($request->filled('name') && ! $request->filled('title')) {
            $request->merge(['title' => $request->input('name')]);
        }

        if ($request->filled('product_name') && ! $request->filled('title')) {
            $request->merge(['title' => $request->input('product_name')]);
        }

        if ($request->filled('shop_category_id') && ! $request->filled('main_category_id')) {
            $request->merge(['main_category_id' => $request->input('shop_category_id')]);
        }

        if ($request->filled('product_price') && ! $request->filled('price_per_day')) {
            $request->merge(['price_per_day' => $request->input('product_price')]);
        }

        if ($request->has('advance_amount') && $request->input('advance_amount') === '') {
            $request->merge(['advance_amount' => null]);
        }

        if ($request->filled('category') && ! $request->filled('type')) {
            $category = strtolower((string) $request->input('category'));

            if (in_array($category, ['women', 'men', 'kids'], true)) {
                $request->merge(['audience' => $category]);
            } else {
                $request->merge(['type' => $request->input('category')]);
            }
        }
    }

    protected function normalizeProductFileInputs(Request $request): void
    {
        foreach (['gallery_images', 'images', 'variant_images'] as $key) {
            $this->normalizeFileField($request, $key);
        }

        $this->normalizeIndexedVariantImageFiles($request);
    }

    protected function normalizeIndexedVariantImageFiles(Request $request): void
    {
        $files = ProductVariantUpload::legacyImageFiles($request);

        if ($files !== []) {
            $request->files->set('variant_images', $files);
        }
    }

    protected function normalizeFileField(Request $request, string $key): void
    {
        foreach ([$key, "{$key}[]"] as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $files = $request->file($field);
            $normalized = $files instanceof UploadedFile
                ? [$files]
                : array_values(is_array($files) ? $files : [$files]);

            $request->files->set($key, $normalized);

            if ($field !== $key) {
                $request->files->remove($field);
            }

            return;
        }
    }

    protected function validateProductUploads(Request $request): void
    {
        foreach (VendorValidationRules::productUploadLimits() as $key => $maxCount) {
            if (! $request->hasFile($key)) {
                continue;
            }

            $files = $this->uploadedFiles($request, $key);
            $isGallery = VendorValidationRules::isGalleryMediaKey($key);

            if (count($files) > $maxCount) {
                throw ValidationException::withMessages([
                    $key => ["You may upload at most {$maxCount} file(s)."],
                ]);
            }

            foreach ($files as $index => $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    throw ValidationException::withMessages([
                        $key => ['One or more uploaded files are invalid.'],
                    ]);
                }

                if ($isGallery) {
                    if (! $this->looksLikeImageMime($file) && ! $this->looksLikeVideoMime($file)) {
                        throw ValidationException::withMessages([
                            $key => ['Gallery files must be images (jpeg, jpg, png, webp) or videos (mp4, mov, avi, mkv, webm, etc.).'],
                        ]);
                    }

                    $fileRules = VendorValidationRules::productGalleryMediaRules($file);
                } else {
                    if ($this->looksLikeVideoMime($file)) {
                        throw ValidationException::withMessages([
                            $key => ['Only image files are allowed here. Upload videos via gallery_images.'],
                        ]);
                    }

                    $fileRules = VendorValidationRules::productUploadRules();
                }

                validator(
                    ["{$key}.{$index}" => $file],
                    ["{$key}.{$index}" => $fileRules],
                    VendorValidationRules::messages(),
                    VendorValidationRules::attributes()
                )->validate();
            }
        }
    }

    protected function looksLikeVideoMime(UploadedFile $file): bool
    {
        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());

        return str_starts_with($mime, 'video/')
            || in_array($ext, VendorValidationRules::VIDEO_MIMES, true);
    }

    protected function looksLikeImageMime(UploadedFile $file): bool
    {
        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());

        return str_starts_with($mime, 'image/')
            || in_array($ext, ['jpeg', 'jpg', 'png', 'webp', 'gif'], true);
    }

    /** @return list<UploadedFile> */
    protected function uploadedFiles(Request $request, string $key): array
    {
        $files = $request->file($key);

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (is_array($files)) {
            return array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));
        }

        return [];
    }

    protected function resolveAdvanceAmount(Request $request): ?float
    {
        if (! $request->has('advance_amount')) {
            return null;
        }

        $value = $request->input('advance_amount');

        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    protected function decodeJsonPayloadFields(Request $request): void
    {
        foreach (['variants', 'damage_deductions'] as $field) {
            $value = $request->input($field);

            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $request->merge([$field => $decoded]);
                }
            }
        }
    }

    protected function assertHasProductImage(Request $request): void
    {
        if ($request->hasFile('image') || $request->hasFile('product_image')) {
            return;
        }

        foreach (['gallery_images', 'images'] as $key) {
            if ($request->hasFile($key)) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'image' => ['A product image is required.'],
        ]);
    }

    protected function resolvePrimaryImage(Request $request): UploadedFile
    {
        if ($request->hasFile('image')) {
            return $request->file('image');
        }

        if ($request->hasFile('product_image')) {
            return $request->file('product_image');
        }

        foreach (['gallery_images', 'images'] as $key) {
            if ($request->hasFile($key)) {
                $files = $request->file($key);

                return is_array($files) ? $files[0] : $files;
            }
        }

        throw ValidationException::withMessages([
            'image' => ['A product image is required.'],
        ]);
    }

    protected function storeGalleryImages(Request $request, PortfolioItem $product, ?UploadedFile $skipFile = null): void
    {
        $files = [];

        foreach (['gallery_images', 'images'] as $key) {
            if ($request->hasFile($key)) {
                $files = array_merge($files, $this->uploadedFiles($request, $key));
            }
        }

        if ($files === []) {
            return;
        }

        $sortOrder = (int) ($product->images()->max('sort_order') ?? 0);

        foreach ($files as $file) {
            if ($skipFile && $this->sameUploadedFile($file, $skipFile)) {
                continue;
            }

            $isVideo = $this->looksLikeVideoMime($file);

            $sortOrder++;
            PortfolioItemImage::query()->create([
                'portfolio_item_id' => $product->id,
                'image_path' => StoresUploadedFiles::store(
                    $file,
                    $isVideo ? 'portfolio/videos' : 'portfolio/images'
                ),
                'media_type' => $isVideo ? PortfolioItemImage::TYPE_VIDEO : PortfolioItemImage::TYPE_IMAGE,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    /** @param list<array<string, mixed>> $variants */
    protected function syncVariants(Request $request, PortfolioItem $product, array $variants, bool $replacing = false): void
    {
        if ($replacing) {
            foreach ($product->variants as $existing) {
                StoresUploadedFiles::delete($existing->image_path);
            }
            $product->variants()->delete();
        }

        if ($variants === []) {
            return;
        }

        foreach ($variants as $index => $variant) {
            $imagePath = ProductVariantUpload::storeVariantImage($request, (int) $index, $variant);

            PortfolioItemVariant::query()->create([
                'portfolio_item_id' => $product->id,
                'size' => (string) ($variant['size'] ?? ''),
                'color' => (string) ($variant['color'] ?? ''),
                'price' => (float) ($variant['price'] ?? 0),
                'image_path' => $imagePath,
                'sort_order' => $index + 1,
            ]);
        }
    }

    /** @param list<array<string, mixed>> $rules */
    protected function syncDamageDeductions(PortfolioItem $product, array $rules, bool $replacing = false): void
    {
        if ($replacing) {
            $product->damageDeductions()->delete();
        }

        foreach ($rules as $index => $rule) {
            PortfolioItemDamageDeduction::query()->create([
                'portfolio_item_id' => $product->id,
                'damage_type' => (string) ($rule['damage_type'] ?? $rule['type'] ?? ''),
                'percent' => (float) ($rule['percent'] ?? $rule['amount_percent'] ?? 0),
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function sameUploadedFile(UploadedFile $a, UploadedFile $b): bool
    {
        return $a->getRealPath() === $b->getRealPath();
    }
}

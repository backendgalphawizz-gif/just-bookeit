<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\PortfolioItemDamageDeduction;
use App\Models\PortfolioItemImage;
use App\Models\PortfolioItemVariant;
use App\Support\Api\VendorApiPresenter;
use App\Support\AppliesListDateFilter;
use App\Support\StoresUploadedFiles;
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
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->with(['category', 'vendor', 'variants', 'damageDeductions']);

        $products = $this->applyDateRange($query, $request)
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            ...VendorApiPresenter::paginator($products, fn (PortfolioItem $item) => VendorApiPresenter::productSummary($item)),
            'category_type' => $type,
            'category_label' => $this->typeMap[$type] ?? 'Products',
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
        $data = $this->validateVendor($request, VendorValidationRules::product(true));

        $primaryImage = $this->resolvePrimaryImage($request);
        $imagePath = StoresUploadedFiles::store($primaryImage, 'portfolio/images');

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

        $this->storeGalleryImages($request, $product, $primaryImage);
        $this->syncVariants($request, $product, $data['variants'] ?? []);
        $this->syncDamageDeductions($product, $data['damage_deductions'] ?? []);

        return $this->success([
            'product' => VendorApiPresenter::productDetail($product->load(['category', 'vendor', 'images', 'variants', 'damageDeductions'])),
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
        $data = $this->validateVendor($request, VendorValidationRules::product(false));

        $updates = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
            'rejection_reason' => null,
        ];

        if (array_key_exists('price_per_day', $data)) {
            $updates['price_per_day'] = $data['price_per_day'];
        }

        if (array_key_exists('advance_amount', $data)) {
            $updates['advance_amount'] = $data['advance_amount'];
        }

        if (! empty($data['audience'])) {
            $updates['audience'] = $data['audience'];
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
            'product' => VendorApiPresenter::productDetail($product->fresh(['category', 'vendor', 'images', 'variants', 'damageDeductions'])),
        ], 'Product updated and sent for re-approval.');
    }

    public function destroy(Request $request, PortfolioItem $product): JsonResponse
    {
        $vendor = $this->vendor($request);
        $this->assertOwnsProduct($product, $vendor);

        $product->delete();

        return $this->success(null, 'Product removed.');
    }

    protected function mergeProductAliases(Request $request): void
    {
        if ($request->filled('name') && ! $request->filled('title')) {
            $request->merge(['title' => $request->input('name')]);
        }

        if ($request->filled('product_name') && ! $request->filled('title')) {
            $request->merge(['title' => $request->input('product_name')]);
        }

        if ($request->filled('product_price') && ! $request->filled('price_per_day')) {
            $request->merge(['price_per_day' => $request->input('product_price')]);
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
        $fileRules = VendorValidationRules::productUploadRules();

        foreach (VendorValidationRules::productUploadLimits() as $key => $maxCount) {
            if (! $request->hasFile($key)) {
                continue;
            }

            $files = $this->uploadedFiles($request, $key);

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

                validator(
                    ["{$key}.{$index}" => $file],
                    ["{$key}.{$index}" => $fileRules],
                    VendorValidationRules::messages(),
                    VendorValidationRules::attributes()
                )->validate();
            }
        }
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

            $sortOrder++;
            PortfolioItemImage::query()->create([
                'portfolio_item_id' => $product->id,
                'image_path' => StoresUploadedFiles::store($file, 'portfolio/images'),
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

        $variantImages = $this->uploadedFiles($request, 'variant_images');

        foreach ($variants as $index => $variant) {
            $imagePath = null;

            if (isset($variantImages[$index]) && $variantImages[$index] instanceof UploadedFile) {
                $imagePath = StoresUploadedFiles::store($variantImages[$index], 'portfolio/variants');
            }

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

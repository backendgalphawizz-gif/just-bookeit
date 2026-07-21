<?php

namespace App\Support;

use App\Models\PortfolioItem;
use App\Models\PortfolioItemDamageDeduction;
use App\Models\PortfolioItemImage;
use App\Models\PortfolioItemVariant;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

trait ManagesPortfolioProducts
{
    protected function storeProductGalleryImages(Request $request, PortfolioItem $product, ?UploadedFile $skipFile = null): void
    {
        $files = [];

        foreach (['gallery_images', 'images', 'media_files'] as $key) {
            if ($request->hasFile($key)) {
                $files = array_merge($files, $this->uploadedProductFiles($request, $key));
            }
        }

        if ($files === []) {
            return;
        }

        $sortOrder = (int) ($product->images()->max('sort_order') ?? 0);

        foreach ($files as $file) {
            if ($skipFile && $this->sameUploadedProductFile($file, $skipFile)) {
                continue;
            }

            $isVideo = $this->uploadedProductFileIsVideo($file);
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

    /**
     * Primary banner (`image_url`) is stored separately from gallery uploads.
     * Mirror it into gallery so edit/view lists show every uploaded image.
     */
    protected function ensurePrimaryImageInGallery(PortfolioItem $product): void
    {
        $path = $product->image_url;
        if (! is_string($path) || $path === '') {
            return;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $exists = $product->images()
            ->where('image_path', $path)
            ->exists();

        if ($exists) {
            return;
        }

        $minSort = (int) ($product->images()->min('sort_order') ?? 1);

        PortfolioItemImage::query()->create([
            'portfolio_item_id' => $product->id,
            'image_path' => $path,
            'media_type' => PortfolioItemImage::TYPE_IMAGE,
            'sort_order' => max(0, $minSort - 1),
        ]);
    }

    protected function storeProductGalleryVideos(Request $request, PortfolioItem $product): void
    {
        $files = [];

        foreach (['gallery_videos', 'videos', 'product_videos'] as $key) {
            if ($request->hasFile($key)) {
                $files = array_merge($files, $this->uploadedProductFiles($request, $key));
            }
        }

        if ($files === []) {
            return;
        }

        $sortOrder = (int) ($product->images()->max('sort_order') ?? 0);

        foreach ($files as $file) {
            $sortOrder++;
            PortfolioItemImage::query()->create([
                'portfolio_item_id' => $product->id,
                'image_path' => StoresUploadedFiles::store($file, 'portfolio/videos'),
                'media_type' => PortfolioItemImage::TYPE_VIDEO,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    protected function uploadedProductFileIsVideo(UploadedFile $file): bool
    {
        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());

        return str_starts_with($mime, 'video/')
            || in_array($ext, VendorValidationRules::VIDEO_MIMES, true);
    }

    /** @param list<array<string, mixed>> $variants */
    protected function syncProductVariants(Request $request, PortfolioItem $product, array $variants, bool $replacing = false): void
    {
        $oldVariants = $replacing
            ? $product->variants()->orderBy('sort_order')->get()->values()
            : collect();

        if ($replacing) {
            // Keep files until after recreate so unchanged variants can reuse paths.
            $product->variants()->delete();
        }

        if ($variants === []) {
            foreach ($oldVariants as $existing) {
                StoresUploadedFiles::delete($existing->image_path);
            }

            return;
        }

        $keptPaths = [];
        $sortOrder = 0;
        $oldImageByColor = [];

        foreach ($oldVariants as $existing) {
            $colorKey = trim((string) $existing->color);
            if ($colorKey !== '' && filled($existing->image_path) && ! isset($oldImageByColor[$colorKey])) {
                $oldImageByColor[$colorKey] = $existing->image_path;
            }
        }

        $allowedStoredPaths = $oldVariants
            ->pluck('image_path')
            ->filter()
            ->values()
            ->all();

        foreach ($variants as $index => $variant) {
            $sortOrder++;
            $index = (int) $index;
            $color = trim((string) ($variant['color'] ?? ''));
            $newImagePath = ProductVariantUpload::storeVariantImage($request, $index, $variant);
            $old = $oldVariants->get($index);
            $storedPath = $variant['stored_image_path'] ?? null;
            if (! is_string($storedPath) || $storedPath === '' || ! in_array($storedPath, $allowedStoredPaths, true)) {
                $storedPath = null;
            }

            $imagePath = $newImagePath
                ?? $storedPath
                ?? ($color !== '' ? ($oldImageByColor[$color] ?? null) : null)
                ?? $old?->image_path;

            if ($imagePath !== null && $imagePath !== '') {
                $keptPaths[] = $imagePath;
            }

            PortfolioItemVariant::query()->create([
                'portfolio_item_id' => $product->id,
                'size' => (string) ($variant['size'] ?? ''),
                'color' => (string) ($variant['color'] ?? ''),
                'price' => (float) ($variant['price'] ?? 0),
                'advance_amount' => array_key_exists('advance_amount', $variant) && $variant['advance_amount'] !== null && $variant['advance_amount'] !== ''
                    ? (float) $variant['advance_amount']
                    : null,
                'quantity' => array_key_exists('quantity', $variant) && $variant['quantity'] !== null && $variant['quantity'] !== ''
                    ? max(0, (int) $variant['quantity'])
                    : null,
                'image_path' => $imagePath,
                'sort_order' => $sortOrder,
            ]);
        }

        if ($replacing) {
            foreach ($oldVariants as $existing) {
                $path = $existing->image_path;
                if ($path && ! in_array($path, $keptPaths, true)) {
                    StoresUploadedFiles::delete($path);
                }
            }
        }

        $this->syncDressPricingFromVariants($product);
    }

    /**
     * Rental dresses use per-variant price/advance; keep product fields as display fallbacks.
     */
    protected function syncDressPricingFromVariants(PortfolioItem $product): void
    {
        $product->refreshDressPricingFromVariants();
    }

    /** @param list<array<string, mixed>> $rules */
    protected function syncProductDamageDeductions(PortfolioItem $product, array $rules, bool $replacing = false): void
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

    /** @return list<UploadedFile> */
    protected function uploadedProductFiles(Request $request, string $key): array
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

    protected function sameUploadedProductFile(UploadedFile $a, UploadedFile $b): bool
    {
        return $a->getRealPath() === $b->getRealPath();
    }

    protected function normalizeProductFormInput(Request $request): void
    {
        $this->expandRentalSizeColorVariants($request);

        foreach (['price_per_day', 'advance_amount'] as $field) {
            if ($request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }

        if (is_array($request->input('variants'))) {
            // Keep original keys so uploaded variants[N][image] files stay aligned.
            $variants = array_filter($request->input('variants'), function ($variant): bool {
                if (! is_array($variant)) {
                    return false;
                }

                return trim((string) ($variant['size'] ?? '')) !== ''
                    || trim((string) ($variant['color'] ?? '')) !== ''
                    || ($variant['price'] ?? '') !== '';
            });
            $request->merge(['variants' => $variants]);
        }

        if (is_array($request->input('damage_deductions'))) {
            $rules = array_values(array_filter($request->input('damage_deductions'), function (array $rule): bool {
                return trim((string) ($rule['damage_type'] ?? '')) !== ''
                    || ($rule['percent'] ?? '') !== '';
            }));
            $request->merge(['damage_deductions' => $rules]);
        }
    }

    /**
     * Figma rental form posts sizes[] + colors[N][color/image].
     * Expand into variants[size×color] for the existing sync layer.
     * Color images are stored once here so the same upload is not consumed twice
     * (primary banner + variant).
     */
    protected function expandRentalSizeColorVariants(Request $request): void
    {
        $type = (string) $request->input('type', '');
        if ($type !== 'rented-dress') {
            return;
        }

        if (is_array($request->input('variants')) && $request->input('variants') !== []) {
            return;
        }

        $sizes = collect((array) $request->input('sizes', []))
            ->map(fn ($size) => trim((string) $size))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $colorRows = [];
        foreach ((array) $request->input('colors', []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $color = trim((string) ($row['color'] ?? ''));
            if ($color === '') {
                continue;
            }
            $colorRows[] = [
                'input_index' => (int) $index,
                'color' => $color,
            ];
        }

        if ($sizes === [] && $colorRows === []) {
            return;
        }

        $storedColorPaths = [];
        foreach ((array) $request->file('colors', []) as $index => $row) {
            $file = is_array($row) ? ($row['image'] ?? null) : null;
            if ($file instanceof UploadedFile && $file->isValid()) {
                $storedColorPaths[(int) $index] = StoresUploadedFiles::store($file, 'portfolio/variants');
            }
        }

        $sizeList = $sizes !== [] ? $sizes : ['Free Size'];
        $colorList = $colorRows !== [] ? $colorRows : [['input_index' => null, 'color' => 'Default']];
        $price = $request->input('price_per_day', 0);

        $variants = [];
        $variantIndex = 0;

        foreach ($colorList as $colorRow) {
            $path = $colorRow['input_index'] !== null
                ? ($storedColorPaths[$colorRow['input_index']] ?? null)
                : null;

            foreach ($sizeList as $size) {
                $variants[$variantIndex] = [
                    'size' => $size,
                    'color' => $colorRow['color'],
                    'price' => $price,
                    'stored_image_path' => $path,
                ];
                $variantIndex++;
            }
        }

        $request->merge(['variants' => $variants]);
        $request->attributes->set(
            'rental_color_image_paths',
            array_values(array_filter($storedColorPaths))
        );
    }

    /** @param  list<array<string, mixed>>  $variants */
    protected function firstStoredVariantImagePath(array $variants): ?string
    {
        foreach ($variants as $variant) {
            if (! is_array($variant)) {
                continue;
            }
            $path = $variant['stored_image_path'] ?? null;
            if (is_string($path) && $path !== '') {
                return $path;
            }
        }

        return null;
    }

    protected function firstUploadedColorImage(Request $request): ?UploadedFile
    {
        foreach ((array) $request->file('colors', []) as $row) {
            if (is_array($row) && ($row['image'] ?? null) instanceof UploadedFile) {
                return $row['image'];
            }
        }

        foreach ((array) $request->file('variants', []) as $row) {
            if (is_array($row) && ($row['image'] ?? null) instanceof UploadedFile) {
                return $row['image'];
            }
        }

        return null;
    }

    /** First non-video file from the jewelry media dropzone. */
    protected function firstUploadedMediaImage(Request $request): ?UploadedFile
    {
        foreach ($this->uploadedProductFiles($request, 'media_files') as $file) {
            if (! $this->uploadedProductFileIsVideo($file)) {
                return $file;
            }
        }

        return null;
    }

    protected function propagateVariantColorImages(PortfolioItem $product): void
    {
        $product->load('variants');
        $byColor = $product->variants->groupBy(fn ($variant) => trim((string) $variant->color));

        foreach ($byColor as $variants) {
            $withImage = $variants->first(fn ($variant) => filled($variant->image_path));
            if (! $withImage) {
                continue;
            }

            foreach ($variants as $variant) {
                if (! filled($variant->image_path)) {
                    $variant->update(['image_path' => $withImage->image_path]);
                }
            }
        }
    }
}

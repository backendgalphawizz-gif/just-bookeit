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

        foreach (['gallery_images', 'images'] as $key) {
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

        foreach ($variants as $index => $variant) {
            $sortOrder++;
            $index = (int) $index;
            $newImagePath = ProductVariantUpload::storeVariantImage($request, $index, $variant);
            $old = $oldVariants->get($index);
            $imagePath = $newImagePath ?? $old?->image_path;

            if ($imagePath !== null && $imagePath !== '') {
                $keptPaths[] = $imagePath;
            }

            PortfolioItemVariant::query()->create([
                'portfolio_item_id' => $product->id,
                'size' => (string) ($variant['size'] ?? ''),
                'color' => (string) ($variant['color'] ?? ''),
                'price' => (float) ($variant['price'] ?? 0),
                'image_path' => $imagePath,
                'sort_order' => $sortOrder,
            ]);

            // Replacing an existing variant image: drop the previous file.
            if (
                $replacing
                && $newImagePath !== null
                && $old?->image_path
                && $old->image_path !== $newImagePath
            ) {
                StoresUploadedFiles::delete($old->image_path);
            }
        }

        if ($replacing) {
            foreach ($oldVariants as $existing) {
                $path = $existing->image_path;
                if ($path && ! in_array($path, $keptPaths, true)) {
                    StoresUploadedFiles::delete($path);
                }
            }
        }
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
}

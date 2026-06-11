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

            $sortOrder++;
            PortfolioItemImage::query()->create([
                'portfolio_item_id' => $product->id,
                'image_path' => StoresUploadedFiles::store($file, 'portfolio/images'),
                'sort_order' => $sortOrder,
            ]);
        }
    }

    /** @param list<array<string, mixed>> $variants */
    protected function syncProductVariants(Request $request, PortfolioItem $product, array $variants, bool $replacing = false): void
    {
        $oldVariants = $replacing
            ? $product->variants()->orderBy('sort_order')->get()
            : collect();

        if ($replacing) {
            foreach ($product->variants as $existing) {
                StoresUploadedFiles::delete($existing->image_path);
            }
            $product->variants()->delete();
        }

        if ($variants === []) {
            return;
        }

        $variantImages = $this->uploadedProductFiles($request, 'variant_images');

        foreach ($variants as $index => $variant) {
            $imagePath = null;

            if (isset($variantImages[$index]) && $variantImages[$index] instanceof UploadedFile) {
                $imagePath = StoresUploadedFiles::store($variantImages[$index], 'portfolio/variants');
            } elseif ($old = $oldVariants->get($index)) {
                $imagePath = $old->image_path;
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
            $variants = array_values(array_filter($request->input('variants'), function (array $variant): bool {
                return trim((string) ($variant['size'] ?? '')) !== ''
                    || trim((string) ($variant['color'] ?? '')) !== ''
                    || ($variant['price'] ?? '') !== '';
            }));
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

<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ProductVariantUpload
{
    public static function resolveImage(Request $request, int $index): ?UploadedFile
    {
        $file = $request->file("variants.{$index}.image");

        if ($file instanceof UploadedFile) {
            return $file;
        }

        $legacy = self::legacyImageFiles($request);

        if (isset($legacy[$index]) && $legacy[$index] instanceof UploadedFile) {
            return $legacy[$index];
        }

        return null;
    }

    /** @return array<int, UploadedFile> */
    public static function legacyImageFiles(Request $request): array
    {
        $legacy = $request->file('variant_images');

        if ($legacy instanceof UploadedFile) {
            return [0 => $legacy];
        }

        if (! is_array($legacy)) {
            return [];
        }

        $files = [];

        foreach ($legacy as $index => $file) {
            if ($file instanceof UploadedFile) {
                $files[(int) $index] = $file;
            }
        }

        return $files;
    }

    /** @param array<string, mixed> $variant */
    public static function hasEmbeddedImage(array $variant): bool
    {
        $encoded = $variant['image'] ?? $variant['image_base64'] ?? null;

        if (! is_string($encoded)) {
            return false;
        }

        $encoded = trim($encoded);

        if ($encoded === '') {
            return false;
        }

        if (str_starts_with(strtolower($encoded), 'data:image/')) {
            return true;
        }

        $normalized = str_replace([' ', "\n", "\r"], '', $encoded);

        return strlen($normalized) >= 32 && preg_match('/^[A-Za-z0-9+\/=_-]+$/', $normalized) === 1;
    }

    /** @param array<string, mixed> $variant */
    public static function assertValidEmbeddedImage(array $variant, int $index): void
    {
        if (! self::hasEmbeddedImage($variant)) {
            return;
        }

        $encoded = (string) ($variant['image'] ?? $variant['image_base64'] ?? '');

        if (self::decodeEmbeddedImage($encoded) === null) {
            throw ValidationException::withMessages([
                "variants.{$index}.image" => ['The variant image must be a valid base64-encoded jpeg, jpg, png, or webp image (max 20 MB).'],
            ]);
        }
    }

    /** @param array<string, mixed> $variant */
    public static function storeVariantImage(Request $request, int $index, array $variant, string $directory = 'portfolio/variants'): ?string
    {
        $file = self::resolveImage($request, $index);

        if ($file instanceof UploadedFile && $file->isValid()) {
            return StoresUploadedFiles::store($file, $directory);
        }

        if (! self::hasEmbeddedImage($variant)) {
            return null;
        }

        $encoded = (string) ($variant['image'] ?? $variant['image_base64'] ?? '');

        $decoded = self::decodeEmbeddedImage($encoded);

        if ($decoded === null) {
            throw ValidationException::withMessages([
                "variants.{$index}.image" => ['The variant image must be a valid base64-encoded jpeg, jpg, png, or webp image (max 20 MB).'],
            ]);
        }

        ['extension' => $extension, 'binary' => $binary] = $decoded;
        $filename = trim($directory, '/').'/'.uniqid('variant_', true).'.'.$extension;
        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $binary);

        return $filename;
    }

    /** @return array{extension: string, binary: string}|null */
    protected static function decodeEmbeddedImage(string $data): ?array
    {
        $data = trim($data);

        if ($data === '') {
            return null;
        }

        $extension = 'jpg';

        if (preg_match('/^data:image\/(\w+);base64,/i', $data, $matches)) {
            $extension = strtolower($matches[1]);
            $data = substr($data, (int) strpos($data, ',') + 1);
        }

        $extension = match ($extension) {
            'jpeg' => 'jpg',
            'jpg', 'png', 'webp' => $extension,
            default => null,
        };

        if ($extension === null) {
            return null;
        }

        $binary = base64_decode(str_replace(' ', '+', $data), true);

        if ($binary === false || $binary === '') {
            return null;
        }

        $maxBytes = VendorValidationRules::MAX_IMAGE_KB * 1024;

        if (strlen($binary) > $maxBytes) {
            return null;
        }

        return [
            'extension' => $extension,
            'binary' => $binary,
        ];
    }

    public static function validateImages(Request $request, int $maxVariants = 50): void
    {
        $fileRules = VendorValidationRules::productUploadRules();
        $variants = $request->input('variants');
        $variantIndexes = is_array($variants) ? array_map('intval', array_keys($variants)) : [];
        $legacyIndexes = array_keys(self::legacyImageFiles($request));
        $indexes = array_values(array_unique(array_merge($variantIndexes, $legacyIndexes)));
        sort($indexes);

        if ($indexes === []) {
            return;
        }

        if (max($indexes) >= $maxVariants) {
            throw ValidationException::withMessages([
                'variants' => ["You may upload at most {$maxVariants} variant(s)."],
            ]);
        }

        foreach ($indexes as $index) {
            $variant = is_array($variants[$index] ?? null) ? $variants[$index] : [];
            $file = self::resolveImage($request, $index);

            if ($file instanceof UploadedFile) {
                if (! $file->isValid()) {
                    throw ValidationException::withMessages([
                        self::imageFieldForIndex($request, $index) => ['The variant image is invalid.'],
                    ]);
                }

                validator(
                    [self::imageFieldForIndex($request, $index) => $file],
                    [self::imageFieldForIndex($request, $index) => $fileRules],
                    VendorValidationRules::messages(),
                    VendorValidationRules::attributes()
                )->validate();
            } else {
                self::assertValidEmbeddedImage($variant, $index);
            }
        }
    }

    protected static function imageFieldForIndex(Request $request, int $index): string
    {
        if ($request->hasFile("variants.{$index}.image")) {
            return "variants.{$index}.image";
        }

        return 'variant_images.'.$index;
    }
}

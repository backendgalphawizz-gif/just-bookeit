<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoresUploadedFiles
{
    public static function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }

    /**
     * Store a portfolio product image: resize the original and write a list/admin thumbnail.
     */
    public static function storePortfolioImage(UploadedFile $file, string $directory): string
    {
        return PortfolioImageOptimizer::store($file, $directory);
    }

    public static function replace(?UploadedFile $file, ?string $oldPath, string $directory): ?string
    {
        if (! $file) {
            return $oldPath;
        }

        self::delete($oldPath);

        return self::store($file, $directory);
    }

    public static function replacePortfolioImage(?UploadedFile $file, ?string $oldPath, string $directory): ?string
    {
        if (! $file) {
            return $oldPath;
        }

        self::deletePortfolioImage($oldPath);

        return self::storePortfolioImage($file, $directory);
    }

    public static function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function deletePortfolioImage(?string $path): void
    {
        PortfolioImageOptimizer::deleteWithThumb($path);
    }

    public static function thumbPath(?string $path): ?string
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        return PortfolioImageOptimizer::thumbPathFor($path);
    }

    public static function thumbUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $thumb = self::thumbPath($path);
        if ($thumb && Storage::disk('public')->exists($thumb)) {
            return self::url($thumb);
        }

        return self::url($path);
    }

    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        // Prefer current request origin so images work on 127.0.0.1:8000, not only APP_URL.
        if (! app()->runningInConsole() && app()->bound('request') && request()->getSchemeAndHttpHost()) {
            return request()->getSchemeAndHttpHost().'/storage/'.$normalized;
        }

        return rtrim(config('app.url'), '/').'/storage/'.$normalized;
    }
}

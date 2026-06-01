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

    public static function replace(?UploadedFile $file, ?string $oldPath, string $directory): ?string
    {
        if (! $file) {
            return $oldPath;
        }

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return self::store($file, $directory);
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

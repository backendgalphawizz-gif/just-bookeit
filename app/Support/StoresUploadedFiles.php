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

        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }
}

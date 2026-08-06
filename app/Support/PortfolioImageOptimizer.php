<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Resize portfolio product images on upload and write list/admin thumbnails.
 * Uses PHP GD (no external packages). Non-raster/unsupported images fall back to a normal store.
 */
class PortfolioImageOptimizer
{
    public const MAX_ORIGINAL_EDGE = 1600;

    public const ORIGINAL_QUALITY = 82;

    public const MAX_THUMB_EDGE = 480;

    public const THUMB_QUALITY = 78;

    /**
     * Store an optimized public-disk image and a sibling thumbnail.
     *
     * @return string Relative path of the main (optimized) image on the public disk
     */
    public static function store(UploadedFile $file, string $directory): string
    {
        $directory = trim(str_replace('\\', '/', $directory), '/');

        if (! self::canOptimize($file)) {
            return $file->store($directory, 'public');
        }

        try {
            $binary = self::encodeResized($file->getRealPath(), self::MAX_ORIGINAL_EDGE, self::ORIGINAL_QUALITY);
            if ($binary === null) {
                return $file->store($directory, 'public');
            }

            $filename = uniqid('img_', true).'.jpg';
            $path = $directory.'/'.$filename;
            Storage::disk('public')->put($path, $binary);

            $thumbBinary = self::encodeResized($file->getRealPath(), self::MAX_THUMB_EDGE, self::THUMB_QUALITY);
            if ($thumbBinary !== null) {
                Storage::disk('public')->put(self::thumbPathFor($path), $thumbBinary);
            }

            return $path;
        } catch (Throwable) {
            return $file->store($directory, 'public');
        }
    }

    public static function thumbPathFor(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        $dir = trim(dirname($normalized), '.');
        $base = pathinfo($normalized, PATHINFO_FILENAME);

        if ($dir === '' || $dir === '/') {
            return 'thumbs/'.$base.'.jpg';
        }

        return $dir.'/thumbs/'.$base.'.jpg';
    }

    public static function deleteWithThumb(?string $path): void
    {
        if (! $path) {
            return;
        }

        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            $disk->delete($path);
        }

        $thumb = self::thumbPathFor($path);
        if ($disk->exists($thumb)) {
            $disk->delete($thumb);
        }
    }

    /**
     * Create a thumbnail for an image already on the public disk.
     * Returns the thumb relative path when written, otherwise null.
     */
    public static function ensureThumb(?string $path): ?string
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        if (! extension_loaded('gd')) {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return null;
        }

        $thumb = self::thumbPathFor($path);
        if ($disk->exists($thumb)) {
            return $thumb;
        }

        $absolute = $disk->path($path);
        $binary = self::encodeResized($absolute, self::MAX_THUMB_EDGE, self::THUMB_QUALITY);
        if ($binary === null) {
            return null;
        }

        $disk->put($thumb, $binary);

        return $thumb;
    }

    public static function canOptimize(UploadedFile $file): bool
    {
        if (! extension_loaded('gd')) {
            return false;
        }

        $mime = strtolower((string) ($file->getMimeType() ?: ''));
        $ext = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($ext, ['svg', 'gif', 'heic', 'heif', 'avif', 'bmp'], true)) {
            return false;
        }

        return in_array($mime, ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/webp'], true)
            || in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    protected static function encodeResized(string $sourcePath, int $maxEdge, int $quality): ?string
    {
        if (! is_file($sourcePath)) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if (! is_array($info) || empty($info[0]) || empty($info[1])) {
            return null;
        }

        [$width, $height] = [(int) $info[0], (int) $info[1]];
        if ($width < 1 || $height < 1) {
            return null;
        }

        $source = self::createImageResource($sourcePath, (int) ($info[2] ?? 0));
        if ($source === null) {
            return null;
        }

        $scale = min(1, $maxEdge / max($width, $height));
        $targetW = max(1, (int) round($width * $scale));
        $targetH = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetW, $targetH);
        if ($canvas === false) {
            imagedestroy($source);

            return null;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $targetW, $targetH, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetW, $targetH, $width, $height);
        imagedestroy($source);

        ob_start();
        imagejpeg($canvas, null, max(40, min(95, $quality)));
        imagedestroy($canvas);
        $binary = ob_get_clean();

        return is_string($binary) && $binary !== '' ? $binary : null;
    }

    /** @return \GdImage|resource|null */
    protected static function createImageResource(string $path, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path) ?: null,
            IMAGETYPE_PNG => @imagecreatefrompng($path) ?: null,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            default => null,
        };
    }
}

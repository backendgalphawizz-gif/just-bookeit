<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Shared image/video allow-lists for iPhone-friendly uploads
 * (HEIC/HEIF photos, MOV/HEVC/M4V videos, etc.).
 */
class MediaUploadSupport
{
    /** @var list<string> */
    public const IMAGE_EXTENSIONS = [
        'jpeg', 'jpg', 'png', 'webp', 'gif', 'bmp', 'svg',
        'heic', 'heif', 'avif',
    ];

    /** @var list<string> */
    public const VIDEO_EXTENSIONS = [
        'mp4', 'mov', 'm4v', 'avi', 'mkv', 'webm',
        '3gp', '3gpp', 'mpeg', 'mpg', 'wmv', 'flv', 'ogv',
        'ts', 'm2ts', 'qt', 'hevc', 'h265',
    ];

    /** @var list<string> */
    public const IMAGE_MIMETYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/bmp',
        'image/svg+xml',
        'image/heic',
        'image/heif',
        'image/heic-sequence',
        'image/heif-sequence',
        'image/avif',
    ];

    /** @var list<string> */
    public const VIDEO_MIMETYPES = [
        'video/mp4',
        'video/quicktime',
        'video/x-m4v',
        'video/x-msvideo',
        'video/x-matroska',
        'video/webm',
        'video/3gpp',
        'video/3gpp2',
        'video/mpeg',
        'video/x-ms-wmv',
        'video/x-flv',
        'video/ogg',
        'video/mp2t',
        'video/hevc',
        'video/H265',
        'video/h265',
    ];

    /** @return list<string> */
    public static function imageExtensions(): array
    {
        return self::IMAGE_EXTENSIONS;
    }

    /** @return list<string> */
    public static function videoExtensions(): array
    {
        return self::VIDEO_EXTENSIONS;
    }

    /** @return list<string> */
    public static function mixedExtensions(): array
    {
        return array_values(array_unique(array_merge(self::IMAGE_EXTENSIONS, self::VIDEO_EXTENSIONS)));
    }

    public static function isImageExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::IMAGE_EXTENSIONS, true);
    }

    public static function isVideoExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::VIDEO_EXTENSIONS, true);
    }

    public static function isVideoFile(UploadedFile $file): bool
    {
        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());

        if (self::isVideoExtension($ext) || in_array($mime, self::VIDEO_MIMETYPES, true)) {
            return true;
        }

        return str_starts_with($mime, 'video/');
    }

    public static function isImageFile(UploadedFile $file): bool
    {
        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());

        if (self::isImageExtension($ext) || in_array($mime, self::IMAGE_MIMETYPES, true)) {
            return true;
        }

        // Avoid treating HEIC-as-octet-stream as image unless extension matches (handled above).
        return str_starts_with($mime, 'image/');
    }

    public static function isAllowedFile(UploadedFile $file, string $kind = 'mixed'): bool
    {
        return match ($kind) {
            'image' => self::isImageFile($file),
            'video' => self::isVideoFile($file),
            default => self::isImageFile($file) || self::isVideoFile($file),
        };
    }

    /**
     * HTML accept= value. Prefer MIME + extension pairs so iOS Photos picks HEIC/MOV correctly.
     */
    public static function acceptAttribute(string $kind = 'mixed'): string
    {
        $parts = match ($kind) {
            'image' => array_merge(self::IMAGE_MIMETYPES, array_map(
                fn (string $ext) => '.'.$ext,
                self::IMAGE_EXTENSIONS
            )),
            'video' => array_merge(self::VIDEO_MIMETYPES, array_map(
                fn (string $ext) => '.'.$ext,
                self::VIDEO_EXTENSIONS
            )),
            default => array_merge(
                self::IMAGE_MIMETYPES,
                self::VIDEO_MIMETYPES,
                array_map(fn (string $ext) => '.'.$ext, self::mixedExtensions())
            ),
        };

        return implode(',', array_values(array_unique($parts)));
    }

    /**
     * Laravel rules for iPhone-safe images (no `image` rule — GD rejects HEIC).
     *
     * @return list<string|\Closure>
     */
    public static function imageRules(int $maxKb, bool $required = false): array
    {
        return self::rulesForKind('image', $maxKb, $required);
    }

    /**
     * @return list<string|\Closure>
     */
    public static function videoRules(int $maxKb, bool $required = false): array
    {
        return self::rulesForKind('video', $maxKb, $required);
    }

    /**
     * @return list<string|\Closure>
     */
    public static function mixedRules(int $maxKb, bool $required = false): array
    {
        return self::rulesForKind('mixed', $maxKb, $required);
    }

    /**
     * @return list<string|\Closure>
     */
    protected static function rulesForKind(string $kind, int $maxKb, bool $required): array
    {
        $label = match ($kind) {
            'image' => 'image (JPG, PNG, WebP, HEIC/HEIF, …)',
            'video' => 'video (MP4, MOV, HEVC/H.265, M4V, …)',
            default => 'image or video (JPG/HEIC, MP4/MOV/HEVC, …)',
        };

        return array_values(array_filter([
            $required ? 'required' : 'nullable',
            'file',
            'max:'.$maxKb,
            function (string $attribute, mixed $value, \Closure $fail) use ($kind, $label): void {
                if ($value === null || $value === '') {
                    return;
                }

                if (! $value instanceof UploadedFile) {
                    $fail("The {$attribute} must be a valid {$label}.");

                    return;
                }

                if (! self::isAllowedFile($value, $kind)) {
                    $fail("Please upload a valid {$label}.");
                }
            },
        ]));
    }
}

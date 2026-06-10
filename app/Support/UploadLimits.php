<?php

namespace App\Support;

class UploadLimits
{
    public const PER_FILE_MAX_KB = 4096;

    public static function postMaxSizeBytes(): int
    {
        return self::iniSizeToBytes((string) ini_get('post_max_size'));
    }

    public static function uploadMaxFilesizeBytes(): int
    {
        return self::iniSizeToBytes((string) ini_get('upload_max_filesize'));
    }

    public static function perFileMaxBytes(): int
    {
        return self::PER_FILE_MAX_KB * 1024;
    }

    /** Bytes available for file uploads in one multipart request (reserves space for text fields). */
    public static function safeMultipartUploadBytes(): int
    {
        $postMax = self::postMaxSizeBytes();
        $perFile = self::perFileMaxBytes();
        $reserved = 512 * 1024;

        if ($postMax >= PHP_INT_MAX / 2) {
            return 64 * 1024 * 1024;
        }

        return max($perFile, (int) floor($postMax * 0.92) - $reserved);
    }

    public static function postTooLargeMessage(): string
    {
        $postMb = self::formatMegabytes(self::postMaxSizeBytes());
        $perFileMb = (int) round(self::perFileMaxBytes() / (1024 * 1024));

        return 'Total upload size exceeds the server limit ('.$postMb.' MB per request). '
            .'Each image may be up to '.$perFileMb.' MB, but uploading many images at once can exceed that limit. '
            .'Remove some files or save with fewer new uploads, then try again.';
    }

    public static function formatMegabytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0';
        }

        return rtrim(rtrim(number_format($bytes / (1024 * 1024), 1, '.', ''), '0'), '.');
    }

    public static function iniSizeToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($value, -1));

        if (in_array($unit, ['g', 'm', 'k'], true)) {
            $number = (float) substr($value, 0, -1);

            return (int) round(match ($unit) {
                'g' => $number * 1024 * 1024 * 1024,
                'm' => $number * 1024 * 1024,
                'k' => $number * 1024,
                default => $number,
            });
        }

        return (int) round((float) $value);
    }
}

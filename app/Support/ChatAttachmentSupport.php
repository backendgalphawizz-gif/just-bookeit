<?php

namespace App\Support;

class ChatAttachmentSupport
{
    /** 20 MB */
    public const MAX_KB = 20480;

    /** @return list<string> */
    public static function imageExtensions(): array
    {
        return ['jpeg', 'jpg', 'png', 'webp', 'gif', 'bmp', 'svg', 'heic', 'heif', 'avif'];
    }

    /** @return list<string> */
    public static function videoExtensions(): array
    {
        return ['mp4', 'webm', 'mov', 'qt', 'quicktime', '3gp', '3gpp', 'avi', 'mkv', 'm4v'];
    }

    public static function acceptAttribute(): string
    {
        return '*/*';
    }

    /** @return array<int, string> */
    public static function validationRules(bool $requiredWithoutBody = false): array
    {
        $rules = [
            'nullable',
            'file',
            'max:'.self::MAX_KB,
        ];

        if ($requiredWithoutBody) {
            array_unshift($rules, 'required_without:body');
        }

        return $rules;
    }

    public static function typeFromPath(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        if ($extension === '') {
            return 'file';
        }

        if (in_array($extension, self::imageExtensions(), true)) {
            return 'image';
        }

        if (in_array($extension, self::videoExtensions(), true)) {
            return 'video';
        }

        return 'file';
    }

    public static function displayName(?string $path, ?string $fallback = null): string
    {
        if (filled($fallback)) {
            return (string) $fallback;
        }

        if (! filled($path)) {
            return 'Attachment';
        }

        $basename = basename((string) $path);
        $extension = strtoupper(pathinfo($basename, PATHINFO_EXTENSION));

        return $extension !== '' ? "File .{$extension}" : 'Attachment';
    }
}

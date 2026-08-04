<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class ChatAttachmentSupport
{
    /** 20 MB */
    public const MAX_KB = 20480;

    /** @return list<string> */
    public static function imageExtensions(): array
    {
        return MediaUploadSupport::imageExtensions();
    }

    /** @return list<string> */
    public static function videoExtensions(): array
    {
        return MediaUploadSupport::videoExtensions();
    }

    public static function acceptAttribute(): string
    {
        return MediaUploadSupport::acceptAttribute('mixed');
    }

    /** @return array<int, string|\Closure> */
    public static function validationRules(bool $requiredWithoutBody = false): array
    {
        $rules = MediaUploadSupport::mixedRules(self::MAX_KB);

        if ($requiredWithoutBody) {
            array_unshift($rules, 'required_without:body');
            // Drop the leading nullable from mixedRules — required_without handles emptiness.
            $rules = array_values(array_filter(
                $rules,
                fn ($rule) => $rule !== 'nullable'
            ));
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

        if (MediaUploadSupport::isImageExtension($extension)) {
            return 'image';
        }

        if (MediaUploadSupport::isVideoExtension($extension)) {
            return 'video';
        }

        return 'file';
    }

    public static function typeFromUpload(UploadedFile $file): string
    {
        if (MediaUploadSupport::isVideoFile($file)) {
            return 'video';
        }

        if (MediaUploadSupport::isImageFile($file)) {
            return 'image';
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

<?php

namespace App\Support;

class ChatAttachmentSupport
{
    public const MAX_KB = 20480;

  /** @return list<string> */
    public static function allowedMimes(): array
    {
        return [
            'jpeg', 'jpg', 'png', 'webp', 'gif',
            'mp4', 'webm', 'mov', 'qt', 'quicktime', '3gp', '3gpp',
        ];
    }

    public static function acceptAttribute(): string
    {
        return 'image/jpeg,image/jpg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime,video/3gpp';
    }

    /** @return array<int, string> */
    public static function validationRules(bool $requiredWithoutBody = false): array
    {
        $rules = [
            'nullable',
            'file',
            'mimes:'.implode(',', self::allowedMimes()),
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

        return match ($extension) {
            'mp4', 'webm', 'mov', 'qt', '3gp', '3gpp' => 'video',
            'jpeg', 'jpg', 'png', 'webp', 'gif' => 'image',
            default => null,
        };
    }
}

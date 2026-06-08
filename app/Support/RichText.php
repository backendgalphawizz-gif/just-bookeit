<?php

namespace App\Support;

class RichText
{
    /** @var string */
    public const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><blockquote><div><span>';

    public static function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $clean = strip_tags($html, self::ALLOWED_TAGS);
        $clean = preg_replace('/\s*on\w+\s*=\s*("|\').*?\1/i', '', $clean) ?? $clean;
        $clean = preg_replace('/href\s*=\s*("|\')\s*javascript:[^"\']*\1/i', 'href="#"', $clean) ?? $clean;

        return trim($clean);
    }

    public static function forDisplay(?string $content): string
    {
        if ($content === null || trim($content) === '') {
            return '';
        }

        if ($content === strip_tags($content)) {
            return nl2br(e($content), false);
        }

        return self::sanitize($content);
    }

    public static function isEmpty(?string $content): bool
    {
        if ($content === null || trim($content) === '') {
            return true;
        }

        $text = trim(strip_tags($content));

        return $text === '' || $text === "\u{FEFF}";
    }
}

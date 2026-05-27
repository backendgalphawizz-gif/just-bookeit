<?php

namespace App\Support;

class AssetManifest
{
    protected static ?array $manifest = null;

    public static function url(string $entry): ?string
    {
        $file = static::manifest()[$entry]['file'] ?? null;

        return $file ? asset('build/'.$file) : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function manifest(): array
    {
        if (static::$manifest !== null) {
            return static::$manifest;
        }

        $path = public_path('build/manifest.json');

        if (! is_file($path)) {
            return static::$manifest = [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return static::$manifest = is_array($decoded) ? $decoded : [];
    }
}

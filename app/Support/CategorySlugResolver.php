<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Str;

class CategorySlugResolver
{
    public static function forCategory(
        string $name,
        string $type,
        ?int $parentId = null,
        ?int $ignoreCategoryId = null,
    ): string {
        return self::ensureUnique(self::baseSlug($name, $type, $parentId), $ignoreCategoryId);
    }

    public static function baseSlug(string $name, string $type, ?int $parentId = null): string
    {
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = 'category';
        }

        if ($type === Category::TYPE_SUB && $parentId) {
            $parent = Category::query()->find($parentId);

            if ($parent?->slug) {
                return $parent->slug.'-'.$slug;
            }
        }

        return $slug;
    }

    public static function ensureUnique(string $baseSlug, ?int $ignoreCategoryId = null): string
    {
        $slug = $baseSlug !== '' ? $baseSlug : 'category';
        $candidate = $slug;
        $suffix = 1;

        while (self::slugExists($candidate, $ignoreCategoryId)) {
            $candidate = $slug.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected static function slugExists(string $slug, ?int $ignoreCategoryId): bool
    {
        $query = Category::query()->where('slug', $slug);

        if ($ignoreCategoryId) {
            $query->where('id', '!=', $ignoreCategoryId);
        }

        return $query->exists();
    }
}

<?php

namespace App\Support\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class CustomerBookingTab
{
    public const TABS = [
        'fashion_designer',
        'rental_dress',
        'rental_jewellery',
    ];

    /** @var array<string, string> */
    protected const TAB_TO_CATEGORY_SLUG = [
        'fashion_designer' => 'fashion-designer',
        'rental_dress' => 'rented-dress',
        'rental_jewellery' => 'rented-jewellery',
    ];

    /** @var array<string, string> */
    protected const ALIASES = [
        'fashion-designer' => 'fashion_designer',
        'fashiondesigner' => 'fashion_designer',
        'designers' => 'fashion_designer',
        'designer' => 'fashion_designer',
        'rented-dress' => 'rental_dress',
        'rented_dress' => 'rental_dress',
        'rental' => 'rental_dress',
        'dress' => 'rental_dress',
        'rented-jewellery' => 'rental_jewellery',
        'rented_jewellery' => 'rental_jewellery',
        'jewellery' => 'rental_jewellery',
        'jewelry' => 'rental_jewellery',
    ];

    public static function validationRule(): array
    {
        return ['nullable', 'string', Rule::in(self::acceptedValues())];
    }

    public static function acceptedValues(): array
    {
        return array_values(array_unique([
            ...self::TABS,
            ...array_keys(self::ALIASES),
        ]));
    }

    public static function normalize(?string $tab): ?string
    {
        if ($tab === null || trim($tab) === '') {
            return null;
        }

        $key = strtolower(str_replace('-', '_', trim($tab)));

        if (isset(self::TAB_TO_CATEGORY_SLUG[$key])) {
            return $key;
        }

        $aliasKey = str_replace('-', '_', strtolower(trim($tab)));

        return self::ALIASES[$aliasKey]
            ?? self::ALIASES[strtolower(trim($tab))]
            ?? null;
    }

    public static function categorySlug(?string $tab): ?string
    {
        $normalized = self::normalize($tab);

        return $normalized ? (self::TAB_TO_CATEGORY_SLUG[$normalized] ?? null) : null;
    }

    public static function applyToQuery(Builder $query, ?string $tab): Builder
    {
        $slug = self::categorySlug($tab);

        if ($slug === null) {
            return $query;
        }

        return $query->whereHas('category', fn (Builder $category) => $category->where('slug', $slug));
    }
}

<?php

namespace App\Support;

use App\Models\ProductColor;
use App\Models\ProductSize;
use Illuminate\Support\Collection;

class ProductOptionCatalog
{
    /** @var list<string> */
    public const DEFAULT_SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

    /** @var array<string, string> */
    public const DEFAULT_COLORS = [
        'Black' => '#111111',
        'White' => '#FFFFFF',
        'Red' => '#E11D48',
        'Blue' => '#2563EB',
        'Green' => '#16A34A',
        'Pink' => '#EC4899',
        'Gold' => '#CA8A04',
        'Silver' => '#A8A29E',
        'Maroon' => '#9F1239',
        'Ivory' => '#FFFFF0',
        'Navy Blue' => '#1E3A8A',
        'Rose Gold' => '#B76E79',
    ];

    /** @return Collection<int, ProductSize> */
    public static function sizes(bool $activeOnly = true): Collection
    {
        $query = ProductSize::query()->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /** @return Collection<int, ProductColor> */
    public static function colors(bool $activeOnly = true): Collection
    {
        $query = ProductColor::query()->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /** @return list<string> */
    public static function sizeNames(bool $activeOnly = true): array
    {
        $names = self::sizes($activeOnly)->pluck('name')->filter()->values()->all();

        return $names !== [] ? $names : self::DEFAULT_SIZES;
    }

    /** @return list<string> */
    public static function colorNames(bool $activeOnly = true): array
    {
        $names = self::colors($activeOnly)->pluck('name')->filter()->values()->all();

        return $names !== [] ? $names : array_keys(self::DEFAULT_COLORS);
    }

    /** @return array<string, string> lowercase name => hex */
    public static function colorCssMap(bool $activeOnly = true): array
    {
        $map = [];

        foreach (self::colors($activeOnly) as $color) {
            $key = strtolower(trim((string) $color->name));
            if ($key === '') {
                continue;
            }
            $map[$key] = $color->displayHex();
        }

        if ($map !== []) {
            return $map;
        }

        $fallback = [];
        foreach (self::DEFAULT_COLORS as $name => $hex) {
            $fallback[strtolower($name)] = $hex;
        }

        return $fallback;
    }

    public static function hexForName(?string $name, bool $activeOnly = true): ?string
    {
        $key = strtolower(trim((string) $name));
        if ($key === '') {
            return null;
        }

        $map = self::colorCssMap($activeOnly);

        return $map[$key] ?? null;
    }

    /** @return list<array<string, mixed>> */
    public static function sizeApiItems(bool $activeOnly = true): array
    {
        $items = self::sizes($activeOnly)
            ->map(fn (ProductSize $size) => [
                'id' => $size->id,
                'name' => $size->name,
                'sort_order' => (int) $size->sort_order,
            ])
            ->values()
            ->all();

        if ($items !== []) {
            return $items;
        }

        return collect(self::DEFAULT_SIZES)
            ->values()
            ->map(fn (string $name, int $index) => [
                'id' => null,
                'name' => $name,
                'sort_order' => $index + 1,
            ])
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public static function colorApiItems(bool $activeOnly = true): array
    {
        $items = self::colors($activeOnly)
            ->map(fn (ProductColor $color) => [
                'id' => $color->id,
                'name' => $color->name,
                'hex_code' => $color->displayHex(),
                'sort_order' => (int) $color->sort_order,
            ])
            ->values()
            ->all();

        if ($items !== []) {
            return $items;
        }

        return collect(self::DEFAULT_COLORS)
            ->map(fn (string $hex, string $name) => [
                'id' => null,
                'name' => $name,
                'hex_code' => $hex,
                'sort_order' => 0,
            ])
            ->values()
            ->map(function (array $item, int $index) {
                $item['sort_order'] = $index + 1;

                return $item;
            })
            ->all();
    }
}

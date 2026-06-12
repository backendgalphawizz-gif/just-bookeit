<?php

namespace App\Support\Api;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogFilter
{
    public const AUDIENCES = ['women', 'men', 'kids'];

    public const SERVICE_SLUGS = [
        'fashion-designer',
        'rented-dress',
        'rented-jewellery',
    ];

    /** @var array<string, string> */
    protected const AUDIENCE_ALIASES = [
        'woman' => 'women',
        'womens' => 'women',
        'female' => 'women',
        'man' => 'men',
        'mens' => 'men',
        'male' => 'men',
        'kid' => 'kids',
        'child' => 'kids',
        'children' => 'kids',
    ];

    /** @var array<string, string> */
    protected const SERVICE_ALIASES = [
        'fashion_designer' => 'fashion-designer',
        'fashiondesigner' => 'fashion-designer',
        'designer' => 'fashion-designer',
        'designers' => 'fashion-designer',
        'rented_dress' => 'rented-dress',
        'rental_dress' => 'rented-dress',
        'rental-dress' => 'rented-dress',
        'rental' => 'rented-dress',
        'rental_cloth' => 'rented-dress',
        'rental-cloth' => 'rented-dress',
        'rented_cloth' => 'rented-dress',
        'rented-cloth' => 'rented-dress',
        'dress' => 'rented-dress',
        'rented_jewellery' => 'rented-jewellery',
        'rental_jewellery' => 'rented-jewellery',
        'rental-jewellery' => 'rented-jewellery',
        'jewellery' => 'rented-jewellery',
        'jewelry' => 'rented-jewellery',
    ];

    /** @return list<string> */
    public static function acceptedAudiences(): array
    {
        return array_values(array_unique([
            ...self::AUDIENCES,
            ...array_keys(self::AUDIENCE_ALIASES),
        ]));
    }

    /** @return list<string> */
    public static function acceptedServices(): array
    {
        return array_values(array_unique([
            ...self::SERVICE_SLUGS,
            ...array_keys(self::SERVICE_ALIASES),
        ]));
    }

    /** @return array<string, array<int, string>> */
    public static function validationRules(): array
    {
        return [
            'audience' => ['nullable', 'string', Rule::in(self::acceptedAudiences())],
            'shop_category' => ['nullable', 'string', Rule::in(self::acceptedAudiences())],
            'shop_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'service' => ['nullable', 'string', Rule::in(self::acceptedServices())],
            'service_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    public static function normalizeAudience(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $key = strtolower(str_replace('-', '_', trim($value)));

        if (in_array($key, self::AUDIENCES, true)) {
            return $key;
        }

        return self::AUDIENCE_ALIASES[$key] ?? null;
    }

    public static function normalizeServiceSlug(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $trimmed = strtolower(trim($value));
        $slugKey = str_replace('-', '_', $trimmed);

        if (in_array($trimmed, self::SERVICE_SLUGS, true)) {
            return $trimmed;
        }

        $resolved = self::SERVICE_ALIASES[$slugKey] ?? self::SERVICE_ALIASES[$trimmed] ?? null;

        return $resolved && in_array($resolved, self::SERVICE_SLUGS, true) ? $resolved : null;
    }

    public static function audienceFromCategory(Category $category): ?string
    {
        if ($category->type !== Category::TYPE_MAIN) {
            return null;
        }

        return self::normalizeAudience($category->slug) ?? self::normalizeAudience($category->name);
    }

    public static function resolveAudience(Request $request): ?string
    {
        if ($request->filled('audience')) {
            return self::normalizeAudience($request->string('audience')->toString());
        }

        if ($request->filled('shop_category')) {
            return self::normalizeAudience($request->string('shop_category')->toString());
        }

        if ($request->filled('shop_category_id')) {
            $category = Category::query()->find($request->integer('shop_category_id'));

            return $category ? self::audienceFromCategory($category) : null;
        }

        if ($request->filled('category_id')) {
            $category = Category::query()->find($request->integer('category_id'));

            if ($category && $category->type === Category::TYPE_MAIN) {
                return self::audienceFromCategory($category);
            }
        }

        return null;
    }

    public static function resolveServiceCategoryId(Request $request): ?int
    {
        if ($request->filled('service_id')) {
            $category = Category::query()->find($request->integer('service_id'));

            return ($category && $category->type === Category::TYPE_SERVICE) ? $category->id : null;
        }

        if ($request->filled('service')) {
            $slug = self::normalizeServiceSlug($request->string('service')->toString());

            if ($slug !== null) {
                return Category::query()
                    ->where('type', Category::TYPE_SERVICE)
                    ->where('slug', $slug)
                    ->value('id');
            }
        }

        if ($request->filled('category_id')) {
            $category = Category::query()->find($request->integer('category_id'));

            if ($category && $category->type === Category::TYPE_SERVICE) {
                return $category->id;
            }
        }

        return null;
    }

    public static function applyToQuery(Builder $query, Request $request): Builder
    {
        self::applyCustomerCatalogConstraints($query);

        $audience = self::resolveAudience($request);

        if ($audience !== null) {
            $query->where('audience', $audience);
        }

        $serviceCategoryId = self::resolveServiceCategoryId($request);

        if ($serviceCategoryId !== null) {
            $query->where('category_id', $serviceCategoryId);
        }

        return $query;
    }

    public static function applyCustomerCatalogConstraints(Builder $query): Builder
    {
        return $query
            ->where('status', 'approved')
            ->whereHas('vendor', fn (Builder $vendor) => $vendor
                ->where('status', 'active')
                ->where('is_listing_active', true));
    }

    /** @return array<string, mixed> */
    public static function applied(Request $request): array
    {
        $audience = self::resolveAudience($request);
        $serviceCategoryId = self::resolveServiceCategoryId($request);
        $serviceCategory = $serviceCategoryId
            ? Category::query()->find($serviceCategoryId)
            : null;

        return array_filter([
            'audience' => $audience,
            'service' => $serviceCategory ? [
                'id' => $serviceCategory->id,
                'name' => $serviceCategory->name,
                'slug' => $serviceCategory->slug,
            ] : null,
        ], fn ($value) => $value !== null);
    }
}

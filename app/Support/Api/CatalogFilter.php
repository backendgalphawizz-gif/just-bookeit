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
            'subcategory_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('type', Category::TYPE_SUB)],
            'subcategory' => ['nullable', 'string', 'max:100'],
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

    public static function resolveMainCategoryId(Request $request): ?int
    {
        if ($request->filled('shop_category_id')) {
            $category = Category::query()->find($request->integer('shop_category_id'));

            return ($category && $category->isMain()) ? $category->id : null;
        }

        if ($request->filled('category_id')) {
            $category = Category::query()->find($request->integer('category_id'));

            if ($category?->isMain()) {
                return $category->id;
            }
        }

        return null;
    }

    public static function resolveSubcategoryId(Request $request): ?int
    {
        if ($request->filled('subcategory_id')) {
            $subcategory = Category::query()->find($request->integer('subcategory_id'));

            return ($subcategory && $subcategory->isSub()) ? $subcategory->id : null;
        }

        if ($request->filled('subcategory')) {
            $slug = strtolower(trim($request->string('subcategory')->toString()));

            if ($slug !== '') {
                $mainCategoryId = self::resolveMainCategoryId($request);
                if ($mainCategoryId === null) {
                    $audience = $request->filled('audience')
                        ? self::normalizeAudience($request->string('audience')->toString())
                        : ($request->filled('shop_category')
                            ? self::normalizeAudience($request->string('shop_category')->toString())
                            : null);
                    if ($audience !== null) {
                        $mainCategoryId = Category::query()
                            ->where('type', Category::TYPE_MAIN)
                            ->where('slug', $audience)
                            ->value('id');
                    }
                }

                $subcategory = self::findSubcategoryBySlug($slug, $mainCategoryId);

                return ($subcategory && $subcategory->isSub()) ? $subcategory->id : null;
            }
        }

        if ($request->filled('category_id')) {
            $category = Category::query()->find($request->integer('category_id'));

            if ($category?->isSub()) {
                return $category->id;
            }
        }

        return null;
    }

    public static function resolveAudience(Request $request): ?string
    {
        if ($request->filled('audience')) {
            return self::normalizeAudience($request->string('audience')->toString());
        }

        if ($request->filled('shop_category')) {
            return self::normalizeAudience($request->string('shop_category')->toString());
        }

        $subcategoryId = self::resolveSubcategoryId($request);

        if ($subcategoryId) {
            $subcategory = Category::query()->with('parent')->find($subcategoryId);

            return $subcategory ? self::audienceFromCategory($subcategory->parent) : null;
        }

        $mainCategoryId = self::resolveMainCategoryId($request);

        if ($mainCategoryId) {
            $category = Category::query()->find($mainCategoryId);

            return $category ? self::audienceFromCategory($category) : null;
        }

        return null;
    }

    public static function resolveServiceCategoryId(Request $request): ?int
    {
        if ($request->filled('service_id')) {
            $category = Category::query()->find($request->integer('service_id'));

            return ($category && $category->isService()) ? $category->id : null;
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

        $subcategoryId = self::resolveSubcategoryId($request);

        if ($subcategoryId !== null) {
            $query->where('subcategory_id', $subcategoryId);
        } elseif (($mainCategoryId = self::resolveMainCategoryId($request)) !== null) {
            $query->whereHas('subcategory', fn (Builder $sub) => $sub->where('parent_id', $mainCategoryId));
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
        $subcategoryId = self::resolveSubcategoryId($request);
        $subcategory = $subcategoryId ? Category::query()->with('parent')->find($subcategoryId) : null;
        $mainCategoryId = self::resolveMainCategoryId($request);
        $mainCategory = $mainCategoryId ? Category::query()->find($mainCategoryId) : null;

        return array_filter([
            'audience' => $audience,
            'vendor_id' => $request->filled('vendor_id') ? $request->integer('vendor_id') : null,
            'service' => $serviceCategory ? [
                'id' => $serviceCategory->id,
                'name' => $serviceCategory->name,
                'slug' => $serviceCategory->slug,
            ] : null,
            'category' => $mainCategory ? [
                'id' => $mainCategory->id,
                'name' => $mainCategory->name,
                'slug' => $mainCategory->slug,
            ] : null,
            'subcategory' => $subcategory ? [
                'id' => $subcategory->id,
                'name' => $subcategory->name,
                'slug' => $subcategory->slug,
                'parent_id' => $subcategory->parent_id,
            ] : null,
        ], fn ($value) => $value !== null);
    }

    protected static function findSubcategoryBySlug(string $slug, ?int $mainCategoryId = null): ?Category
    {
        $query = Category::query()->where('type', Category::TYPE_SUB);

        $exact = (clone $query)->where('slug', $slug)->first();
        if ($exact) {
            return $exact;
        }

        if ($mainCategoryId) {
            $main = Category::query()->find($mainCategoryId);
            if ($main) {
                $prefixed = $main->slug.'-'.str($slug)->slug()->toString();
                $prefixedMatch = (clone $query)->where('slug', $prefixed)->where('parent_id', $mainCategoryId)->first();
                if ($prefixedMatch) {
                    return $prefixedMatch;
                }
            }
        }

        $suffix = '-'.str($slug)->slug()->toString();

        return (clone $query)
            ->when($mainCategoryId, fn ($builder) => $builder->where('parent_id', $mainCategoryId))
            ->where(function ($builder) use ($slug, $suffix) {
                $builder->where('slug', 'like', '%'.$suffix)
                    ->orWhere('slug', 'like', $slug.'%');
            })
            ->orderBy('sort_order')
            ->first();
    }
}

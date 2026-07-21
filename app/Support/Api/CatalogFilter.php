<?php

namespace App\Support\Api;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogFilter
{
    public const BROWSE_CATEGORIES = 'categories';

    public const BROWSE_SERVICES = 'services';

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
            'city' => ['nullable', 'string', 'max:100'],
            'browse' => ['nullable', 'string', Rule::in([self::BROWSE_CATEGORIES, self::BROWSE_SERVICES])],
            ...VendorProximityFilter::validationRules(),
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
        if ($request->filled('service_category_id')) {
            $category = Category::query()->find($request->integer('service_category_id'));

            return ($category && $category->isService()) ? $category->id : null;
        }

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

    /** @return list<int>|null Null means no service filter should be applied. */
    public static function productServiceCategoryIds(?int $serviceCategoryId): ?array
    {
        if ($serviceCategoryId === null) {
            return null;
        }

        return [$serviceCategoryId];
    }

    /** @return list<int>|null Null means no service filter should be applied. */
    public static function subcategoryServiceCategoryIds(?int $serviceCategoryId): ?array
    {
        if ($serviceCategoryId === null) {
            return null;
        }

        return [$serviceCategoryId];
    }

    public static function applySubcategoryServiceFilter(Builder $query, ?int $serviceCategoryId): Builder
    {
        $ids = self::subcategoryServiceCategoryIds($serviceCategoryId);

        if ($ids === null) {
            return $query;
        }

        return $query->whereIn('service_category_id', $ids);
    }

    public static function applyToQuery(Builder $query, Request $request, string $browseMode = self::BROWSE_CATEGORIES): Builder
    {
        self::applyCustomerCatalogConstraints($query);

        if ($browseMode === self::BROWSE_SERVICES) {
            $serviceCategoryId = self::resolveServiceCategoryId($request);

            if ($serviceCategoryId !== null) {
                $query->where('category_id', $serviceCategoryId);
            } else {
                $query->whereHas('category', fn (Builder $category) => $category->where('type', Category::TYPE_SERVICE));
            }

            self::applyVendorCity($query, $request->string('city')->toString());
            VendorProximityFilter::applyToCatalogQuery($query, $request);

            return $query;
        }

        $audience = self::resolveAudience($request);

        if ($audience !== null) {
            $query->where('audience', $audience);
        }

        $serviceCategoryId = self::resolveServiceCategoryId($request);
        $serviceCategoryIds = self::productServiceCategoryIds($serviceCategoryId);

        if ($serviceCategoryIds !== null) {
            $query->whereIn('category_id', $serviceCategoryIds);
        }

        $subcategoryId = self::resolveSubcategoryId($request);

        if ($subcategoryId !== null) {
            $query->where('subcategory_id', $subcategoryId);
        } elseif (($mainCategoryId = self::resolveMainCategoryId($request)) !== null) {
            $query->whereHas('subcategory', fn (Builder $sub) => $sub->where('parent_id', $mainCategoryId));
        } else {
            $query->whereNotNull('subcategory_id');
        }

        self::applyVendorCity($query, $request->string('city')->toString());
        VendorProximityFilter::applyToCatalogQuery($query, $request);

        return $query;
    }

    public static function applyVendorCity(Builder $query, ?string $city): Builder
    {
        $city = trim((string) $city);

        if ($city === '') {
            return $query;
        }

        return $query->whereHas('vendor', fn (Builder $vendor) => self::applyCityOnVendorQuery($vendor, $city));
    }

    public static function applyCityOnVendorQuery(Builder $query, string $city): Builder
    {
        return $query->whereRaw('LOWER(TRIM(city)) = ?', [strtolower(trim($city))]);
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
    public static function applied(Request $request, string $browseMode = self::BROWSE_CATEGORIES): array
    {
        $audience = self::resolveAudience($request);
        $serviceCategoryId = self::resolveServiceCategoryId($request);
        $serviceCategory = $serviceCategoryId
            ? Category::query()->find($serviceCategoryId)
            : null;
        $subcategoryId = self::resolveSubcategoryId($request);
        $subcategory = $subcategoryId ? Category::query()->with(['parent', 'serviceCategory'])->find($subcategoryId) : null;
        $mainCategoryId = self::resolveMainCategoryId($request);
        $mainCategory = $mainCategoryId ? Category::query()->find($mainCategoryId) : null;

        $city = trim($request->string('city')->toString());
        $browse = $request->filled('browse')
            ? $request->string('browse')->toString()
            : $browseMode;

        return array_filter([
            'browse' => in_array($browse, [self::BROWSE_CATEGORIES, self::BROWSE_SERVICES], true) ? $browse : null,
            'audience' => $audience,
            'city' => $city !== '' ? $city : null,
            'vendor_id' => $request->filled('vendor_id') ? $request->integer('vendor_id') : null,
            ...VendorProximityFilter::appliedMeta($request),
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
                'service_category_id' => $subcategory->service_category_id,
                'service_type' => $subcategory->serviceCategory?->slug,
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

<?php

namespace App\Support;

use App\Models\Category;
use App\Support\Api\CatalogFilter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubcategoryCatalog
{
    public static function audienceFromSubcategory(?Category $subcategory): ?string
    {
        if (! $subcategory || $subcategory->type !== Category::TYPE_SUB) {
            return null;
        }

        $subcategory->loadMissing('parent');

        return $subcategory->parent
            ? CatalogFilter::audienceFromCategory($subcategory->parent)
            : null;
    }

    public static function resolveSubcategory(int $subcategoryId, ?int $serviceCategoryId = null): ?Category
    {
        $query = Category::query()
            ->where('id', $subcategoryId)
            ->where('type', Category::TYPE_SUB)
            ->where('is_active', true);

        if ($serviceCategoryId) {
            CatalogFilter::applySubcategoryServiceFilter($query, $serviceCategoryId);
        }

        return $query->with(['parent', 'serviceCategory'])->first();
    }

    /** @return array<string, array<int, mixed>> */
    public static function subcategoryIdRules(bool $required = true): array
    {
        $rules = [
            Rule::exists('categories', 'id')->where('type', Category::TYPE_SUB),
        ];

        array_unshift($rules, $required ? 'required' : 'sometimes');

        return ['subcategory_id' => $rules];
    }

    public static function assertBelongsToMainCategory(Category $subcategory, int $mainCategoryId): void
    {
        $subcategory->loadMissing('parent');

        if ($subcategory->parent_id !== $mainCategoryId) {
            throw ValidationException::withMessages([
                'subcategory_id' => ['Select a sub-category that belongs to the chosen category.'],
            ]);
        }
    }

    public static function assertBelongsToServiceCategory(Category $subcategory, int $serviceCategoryId): void
    {
        if (! $subcategory->service_category_id) {
            return;
        }

        $allowedIds = CatalogFilter::subcategoryServiceCategoryIds($serviceCategoryId) ?? [$serviceCategoryId];

        if (! in_array($subcategory->service_category_id, $allowedIds, true)) {
            throw ValidationException::withMessages([
                'subcategory_id' => ['Select a sub-category that matches the chosen service type.'],
            ]);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public static function mainCategoryIdRules(bool $required = false): array
    {
        $rules = [
            'nullable',
            'integer',
            Rule::exists('categories', 'id')->where('type', Category::TYPE_MAIN),
        ];

        if ($required) {
            $rules[0] = 'required';
        }

        return ['category_id' => $rules];
    }
}

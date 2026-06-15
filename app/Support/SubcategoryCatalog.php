<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Validation\Rule;

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

    public static function resolveSubcategory(int $subcategoryId): ?Category
    {
        return Category::query()
            ->where('id', $subcategoryId)
            ->where('type', Category::TYPE_SUB)
            ->where('is_active', true)
            ->with('parent')
            ->first();
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

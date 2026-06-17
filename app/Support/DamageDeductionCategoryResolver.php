<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DamageDeductionCategoryResolver
{
    public const OTHER = '__other__';

    /** @param list<array<string, mixed>> $rules */
    public function resolveCatalogRules(array $rules, string $errorKey): array
    {
        return collect($rules)
            ->map(fn (array $rule) => $this->resolveCatalogRule($rule, $errorKey))
            ->all();
    }

    /** @param list<array<string, mixed>> $rules */
    public function resolveServiceRules(array $rules, string $errorKey): array
    {
        return collect($rules)
            ->map(fn (array $rule) => $this->resolveServiceRule($rule, $errorKey))
            ->all();
    }

    /** @param array<string, mixed> $rule */
    protected function resolveCatalogRule(array $rule, string $errorKey): array
    {
        $rule['category_id'] = $this->resolveMainCategoryId(
            $rule['category_id'] ?? null,
            $rule['category_name'] ?? null,
            $errorKey
        );
        unset($rule['category_name']);

        if (($rule['subcategory_id'] ?? '') === self::OTHER) {
            $rule['subcategory_id'] = $this->findOrCreateSubcategory(
                (int) $rule['category_id'],
                $rule['subcategory_name'] ?? null,
                $errorKey
            );
            unset($rule['subcategory_name']);
        } elseif (! filled($rule['subcategory_id'] ?? null)) {
            unset($rule['subcategory_id']);
        }

        return $rule;
    }

    /** @param array<string, mixed> $rule */
    protected function resolveServiceRule(array $rule, string $errorKey): array
    {
        if (filled($rule['category_id'] ?? null)) {
            $rule['category_id'] = $this->resolveMainCategoryId(
                $rule['category_id'],
                $rule['category_name'] ?? null,
                $errorKey
            );
            unset($rule['category_name']);
        } else {
            unset($rule['category_name']);
        }

        if (($rule['subcategory_id'] ?? '') === self::OTHER) {
            if (! filled($rule['category_id'] ?? null)) {
                throw ValidationException::withMessages([
                    $errorKey => 'Select a category before adding a new sub-category.',
                ]);
            }

            $rule['subcategory_id'] = $this->findOrCreateSubcategory(
                (int) $rule['category_id'],
                $rule['subcategory_name'] ?? null,
                $errorKey
            );
            unset($rule['subcategory_name']);
        } elseif (! filled($rule['subcategory_id'] ?? null)) {
            unset($rule['subcategory_id']);
        }

        $rule['service_category_id'] = $this->resolveServiceCategoryId(
            $rule['service_category_id'] ?? null,
            $rule['service_category_name'] ?? null,
            $errorKey
        );
        unset($rule['service_category_name']);

        return $rule;
    }

    protected function resolveMainCategoryId(mixed $categoryId, mixed $name, string $errorKey): int
    {
        if ((string) $categoryId !== self::OTHER) {
            return (int) $categoryId;
        }

        return $this->findOrCreateMainCategory($name, $errorKey)->id;
    }

    protected function resolveServiceCategoryId(mixed $categoryId, mixed $name, string $errorKey): int
    {
        if ((string) $categoryId !== self::OTHER) {
            return (int) $categoryId;
        }

        return $this->findOrCreateServiceCategory($name, $errorKey)->id;
    }

    protected function findOrCreateMainCategory(mixed $name, string $errorKey): Category
    {
        $validatedName = $this->validatedName($name, 'category', $errorKey);

        return $this->findOrCreateCategory(
            $validatedName,
            Category::TYPE_MAIN,
            null,
            fn ($query) => $query->main()
        );
    }

    protected function findOrCreateSubcategory(int $parentId, mixed $name, string $errorKey): int
    {
        $parent = Category::query()->main()->find($parentId);

        if (! $parent) {
            throw ValidationException::withMessages([
                $errorKey => 'Select a valid category before adding a sub-category.',
            ]);
        }

        $validatedName = $this->validatedName($name, 'sub-category', $errorKey);

        return $this->findOrCreateCategory(
            $validatedName,
            Category::TYPE_SUB,
            $parent->id,
            fn ($query) => $query->sub()->where('parent_id', $parent->id)
        )->id;
    }

    protected function findOrCreateServiceCategory(mixed $name, string $errorKey): Category
    {
        $validatedName = $this->validatedName($name, 'service category', $errorKey);

        return $this->findOrCreateCategory(
            $validatedName,
            Category::TYPE_SERVICE,
            null,
            fn ($query) => $query->service()
        );
    }

    protected function validatedName(mixed $name, string $label, string $errorKey): string
    {
        $validatedName = trim((string) $name);

        if ($validatedName === '') {
            throw ValidationException::withMessages([
                $errorKey => 'Enter a name for the new '.$label.'.',
            ]);
        }

        if (strlen($validatedName) > 255 || ! preg_match(AdminValidationRules::REGEX_TITLE, $validatedName)) {
            throw ValidationException::withMessages([
                $errorKey => 'The new '.$label.' name contains invalid characters.',
            ]);
        }

        return $validatedName;
    }

    /** @param callable(\Illuminate\Database\Eloquent\Builder): \Illuminate\Database\Eloquent\Builder $scope */
    protected function findOrCreateCategory(
        string $name,
        string $type,
        ?int $parentId,
        callable $scope
    ): Category {
        $slug = Str::slug($name);
        $needle = strtolower($name);

        $existing = $scope(Category::query())
            ->where(function ($query) use ($needle, $slug) {
                $query->whereRaw('LOWER(name) = ?', [$needle])
                    ->orWhere('slug', $slug);
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        $slug = $this->uniqueSlug($slug);

        $maxSort = (int) ($scope(Category::query())->max('sort_order') ?? 0);

        return Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'type' => $type,
            'parent_id' => $parentId,
            'is_active' => true,
            'sort_order' => $maxSort + 1,
        ]);
    }

    protected function uniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug !== '' ? $baseSlug : 'category';
        $candidate = $slug;
        $suffix = 1;

        while (Category::query()->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}

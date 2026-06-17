<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\PlatformSetting;
use App\Support\AdminValidationRules;
use App\Support\DamageDeductionCategoryResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DamageDeductionSettingsController extends AdminController
{
    protected string $permissionModule = 'settings';

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'catalog') === 'service' ? 'service' : 'catalog';

        $mainCategories = Category::query()
            ->main()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $subcategories = Category::query()
            ->sub()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $serviceCategories = Category::query()
            ->service()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.settings.damage-deduction', [
            'tab' => $tab,
            'mainCategories' => $mainCategories,
            'subcategories' => $subcategories,
            'serviceCategories' => $serviceCategories,
            'catalogRules' => PlatformSetting::damageDeductionRulesForSettings(),
            'serviceRules' => PlatformSetting::serviceDamageDeductionRulesForSettings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $tab = $request->input('tab', 'catalog') === 'service' ? 'service' : 'catalog';
        $resolver = new DamageDeductionCategoryResolver;

        if ($tab === 'catalog') {
            $incomingCatalogRules = $this->prepareIncomingRules(
                $request->input('damage_deduction_rules', []),
                'damage_deduction_rules'
            );
            $incomingCatalogRules = $resolver->resolveCatalogRules(
                $incomingCatalogRules,
                'damage_deduction_rules'
            );
            $incomingServiceRules = $this->prunedStoredServiceRules();
        } else {
            $incomingServiceRules = $this->prepareIncomingRules(
                $request->input('service_damage_deduction_rules', []),
                'service_damage_deduction_rules'
            );
            $incomingServiceRules = $resolver->resolveServiceRules(
                $incomingServiceRules,
                'service_damage_deduction_rules'
            );
            $incomingCatalogRules = $this->prunedStoredCatalogRules();
        }

        $request->merge([
            'tab' => $tab,
            'damage_deduction_rules' => $incomingCatalogRules,
            'service_damage_deduction_rules' => $incomingServiceRules,
        ]);

        $validationRules = $tab === 'service'
            ? array_merge(
                ['tab' => ['nullable', 'string', Rule::in(['catalog', 'service'])]],
                AdminValidationRules::settingsDamageDeductionServiceRules()
            )
            : array_merge(
                ['tab' => ['nullable', 'string', Rule::in(['catalog', 'service'])]],
                AdminValidationRules::settingsDamageDeductionCatalogRules()
            );

        $data = $request->validate(
            $validationRules,
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        $catalogRules = collect($data['damage_deduction_rules'] ?? $incomingCatalogRules);
        $serviceRules = collect($data['service_damage_deduction_rules'] ?? $incomingServiceRules);

        if ($catalogRules->isEmpty() && $serviceRules->isEmpty()) {
            throw ValidationException::withMessages([
                $tab === 'service' ? 'service_damage_deduction_rules' : 'damage_deduction_rules' => 'Configure at least one damage deduction rule.',
            ]);
        }

        $subcategories = Category::query()
            ->sub()
            ->get(['id', 'parent_id'])
            ->keyBy('id');

        $normalizedCatalog = $catalogRules
            ->map(function (array $rule) use ($subcategories) {
                $categoryId = (int) $rule['category_id'];
                $subcategoryId = filled($rule['subcategory_id'] ?? null)
                    ? (int) $rule['subcategory_id']
                    : null;

                if ($subcategoryId !== null) {
                    $subcategory = $subcategories->get($subcategoryId);

                    if (! $subcategory || (int) $subcategory->parent_id !== $categoryId) {
                        throw ValidationException::withMessages([
                            'damage_deduction_rules' => 'Each sub-category must belong to its selected category.',
                        ]);
                    }
                }

                return [
                    'category_id' => $categoryId,
                    'subcategory_id' => $subcategoryId,
                    'max_percent' => (float) $rule['max_percent'],
                ];
            });

        if ($normalizedCatalog
            ->map(fn (array $rule) => $rule['category_id'].'-'.($rule['subcategory_id'] ?? 'all'))
            ->duplicates()
            ->isNotEmpty()) {
            throw ValidationException::withMessages([
                'damage_deduction_rules' => 'Each category and sub-category combination can only appear once.',
            ]);
        }

        $normalizedService = $serviceRules
            ->map(function (array $rule) use ($subcategories) {
                $categoryId = filled($rule['category_id'] ?? null) ? (int) $rule['category_id'] : null;
                $subcategoryId = filled($rule['subcategory_id'] ?? null)
                    ? (int) $rule['subcategory_id']
                    : null;

                if ($subcategoryId !== null) {
                    if ($categoryId === null) {
                        throw ValidationException::withMessages([
                            'service_damage_deduction_rules' => 'Select a category when choosing a sub-category.',
                        ]);
                    }

                    $subcategory = $subcategories->get($subcategoryId);

                    if (! $subcategory || (int) $subcategory->parent_id !== $categoryId) {
                        throw ValidationException::withMessages([
                            'service_damage_deduction_rules' => 'Each sub-category must belong to its selected category.',
                        ]);
                    }
                }

                $normalized = [
                    'service_category_id' => (int) $rule['service_category_id'],
                    'max_percent' => (float) $rule['max_percent'],
                ];

                if ($categoryId !== null) {
                    $normalized['category_id'] = $categoryId;
                    $normalized['subcategory_id'] = $subcategoryId;
                }

                return $normalized;
            });

        if ($normalizedService
            ->map(fn (array $rule) => ($rule['category_id'] ?? 'all').'-'.($rule['subcategory_id'] ?? 'all').'-'.$rule['service_category_id'])
            ->duplicates()
            ->isNotEmpty()) {
            throw ValidationException::withMessages([
                'service_damage_deduction_rules' => 'Each category, sub-category, and service category combination can only appear once.',
            ]);
        }

        $normalized = $normalizedCatalog
            ->values()
            ->merge($normalizedService->values())
            ->all();

        DB::transaction(function () use ($normalized) {
            PlatformSetting::set(
                'refund_damage_deduction_rules',
                $normalized,
                'damage_deduction',
                'json'
            );
        });

        return redirect()
            ->route('admin.settings.damage-deduction.index', ['tab' => $tab])
            ->with('success', 'Damage deduction rules saved successfully.');
    }

    /** @param list<array<string, mixed>> $rules */
    protected function prepareIncomingRules(array $rules, string $errorKey): array
    {
        return collect($rules)
            ->map(function (array $rule) {
                foreach (['category_id', 'subcategory_id', 'service_category_id', 'max_percent'] as $field) {
                    if (array_key_exists($field, $rule) && $rule[$field] === '') {
                        unset($rule[$field]);
                    }
                }

                foreach (['category_name', 'subcategory_name', 'service_category_name'] as $field) {
                    if (array_key_exists($field, $rule)) {
                        $rule[$field] = trim((string) $rule[$field]);
                    }
                }

                if (($rule['subcategory_id'] ?? '') === DamageDeductionCategoryResolver::OTHER) {
                    return $rule;
                }

                if (! filled($rule['subcategory_id'] ?? null)) {
                    unset($rule['subcategory_id']);
                }

                return $rule;
            })
            ->filter(function (array $rule) use ($errorKey) {
                $hasCategory = filled($rule['category_id'] ?? null)
                    || filled($rule['category_name'] ?? null);
                $hasServiceCategory = filled($rule['service_category_id'] ?? null)
                    || filled($rule['service_category_name'] ?? null);
                $hasPercent = array_key_exists('max_percent', $rule) && $rule['max_percent'] !== null && $rule['max_percent'] !== '';

                if ($errorKey === 'service_damage_deduction_rules') {
                    return $hasServiceCategory || $hasCategory || $hasPercent;
                }

                return $hasCategory || $hasPercent;
            })
            ->values()
            ->all();
    }

    /** @return list<array{category_id: int, subcategory_id: int|null, max_percent: float}> */
    protected function prunedStoredCatalogRules(): array
    {
        $subcategories = Category::query()
            ->sub()
            ->get(['id', 'parent_id'])
            ->keyBy('id');
        $mainCategoryIds = Category::query()->main()->pluck('id')->all();
        $mainCategoryIds = array_fill_keys($mainCategoryIds, true);

        return collect(PlatformSetting::damageDeductionRulesForSettings())
            ->filter(function (array $rule) use ($subcategories, $mainCategoryIds) {
                if (! isset($mainCategoryIds[(int) $rule['category_id']])) {
                    return false;
                }

                if (filled($rule['subcategory_id'] ?? null)) {
                    $subcategory = $subcategories->get((int) $rule['subcategory_id']);

                    return $subcategory && (int) $subcategory->parent_id === (int) $rule['category_id'];
                }

                return true;
            })
            ->map(fn (array $rule) => [
                'category_id' => (int) $rule['category_id'],
                'subcategory_id' => filled($rule['subcategory_id'] ?? null) ? (int) $rule['subcategory_id'] : null,
                'max_percent' => (float) $rule['max_percent'],
            ])
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    protected function prunedStoredServiceRules(): array
    {
        $subcategories = Category::query()
            ->sub()
            ->get(['id', 'parent_id'])
            ->keyBy('id');
        $mainCategoryIds = Category::query()->main()->pluck('id')->all();
        $mainCategoryIds = array_fill_keys($mainCategoryIds, true);
        $serviceCategoryIds = Category::query()->service()->pluck('id')->all();
        $serviceCategoryIds = array_fill_keys($serviceCategoryIds, true);

        return collect(PlatformSetting::serviceDamageDeductionRulesForSettings())
            ->filter(function (array $rule) use ($subcategories, $mainCategoryIds, $serviceCategoryIds) {
                if (! isset($serviceCategoryIds[(int) $rule['service_category_id']])) {
                    return false;
                }

                if (filled($rule['category_id'] ?? null)) {
                    if (! isset($mainCategoryIds[(int) $rule['category_id']])) {
                        return false;
                    }

                    if (filled($rule['subcategory_id'] ?? null)) {
                        $subcategory = $subcategories->get((int) $rule['subcategory_id']);

                        return $subcategory && (int) $subcategory->parent_id === (int) $rule['category_id'];
                    }
                }

                return true;
            })
            ->map(function (array $rule) {
                $normalized = [
                    'service_category_id' => (int) $rule['service_category_id'],
                    'max_percent' => (float) $rule['max_percent'],
                ];

                if (filled($rule['category_id'] ?? null)) {
                    $normalized['category_id'] = (int) $rule['category_id'];
                    $normalized['subcategory_id'] = filled($rule['subcategory_id'] ?? null)
                        ? (int) $rule['subcategory_id']
                        : null;
                }

                return $normalized;
            })
            ->values()
            ->all();
    }
}

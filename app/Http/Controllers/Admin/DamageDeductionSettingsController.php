<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\PlatformSetting;
use App\Support\AdminValidationRules;
use App\Support\DamageDeductionCategoryResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $incomingCatalogRules = collect($request->input('damage_deduction_rules', []))
            ->map(function (array $rule) {
                if (! filled($rule['subcategory_id'] ?? null) || ($rule['subcategory_id'] ?? '') === DamageDeductionCategoryResolver::OTHER) {
                    if (($rule['subcategory_id'] ?? '') !== DamageDeductionCategoryResolver::OTHER) {
                        unset($rule['subcategory_id']);
                    }
                }

                return $rule;
            })
            ->values()
            ->all();

        $incomingServiceRules = collect($request->input('service_damage_deduction_rules', []))
            ->map(function (array $rule) {
                if (! filled($rule['subcategory_id'] ?? null) || ($rule['subcategory_id'] ?? '') === DamageDeductionCategoryResolver::OTHER) {
                    if (($rule['subcategory_id'] ?? '') !== DamageDeductionCategoryResolver::OTHER) {
                        unset($rule['subcategory_id']);
                    }
                }

                return $rule;
            })
            ->values()
            ->all();

        $resolver = new DamageDeductionCategoryResolver;

        $incomingCatalogRules = $resolver->resolveCatalogRules(
            $incomingCatalogRules,
            'damage_deduction_rules'
        );

        $incomingServiceRules = $resolver->resolveServiceRules(
            $incomingServiceRules,
            'service_damage_deduction_rules'
        );

        $request->merge([
            'damage_deduction_rules' => $incomingCatalogRules,
            'service_damage_deduction_rules' => $incomingServiceRules,
        ]);

        $data = $request->validate(
            AdminValidationRules::settingsDamageDeductionRules(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        $catalogRules = collect($data['damage_deduction_rules'] ?? []);
        $serviceRules = collect($data['service_damage_deduction_rules'] ?? []);

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

        PlatformSetting::set(
            'refund_damage_deduction_rules',
            $normalized,
            'damage_deduction',
            'json'
        );

        return redirect()
            ->route('admin.settings.damage-deduction.index', ['tab' => $tab])
            ->with('success', 'Damage deduction rules saved successfully.');
    }
}

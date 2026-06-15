<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PlatformSetting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("platform_setting.{$key}", 3600, fn () => static::query()->where('key', $key)->first());

        if (! $setting || $setting->value === null || $setting->value === '') {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true) ?? $default,
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'text'): void
    {
        if ($type === 'boolean') {
            $value = $value ? '1' : '0';
        }

        if ($type === 'json' && (is_array($value) || is_object($value))) {
            $value = json_encode($value);
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'type' => $type]
        );

        Cache::forget("platform_setting.{$key}");
    }

    public static function getGroup(string $group): array
    {
        return static::query()
            ->where('group', $group)
            ->pluck('value', 'key')
            ->all();
    }

    public static function mediaUrl(string $key): ?string
    {
        $path = static::get($key);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }

    /** @return list<array{category_id: int, subcategory_id: int|null, max_percent: float}|array{service_category_id: int, max_percent: float}> */
    public static function damageDeductionRules(): array
    {
        return static::normalizedDamageDeductionRules();
    }

    /** @return list<array{category_id: int, category_name: string, subcategory_id: int|null, subcategory_name: string, max_percent: float}> */
    public static function damageDeductionRulesForSettings(): array
    {
        return collect(static::normalizedDamageDeductionRules())
            ->filter(fn (array $rule) => isset($rule['category_id']) && ! isset($rule['service_category_id']))
            ->map(function (array $rule) {
                $main = Category::query()->find($rule['category_id']);
                $sub = isset($rule['subcategory_id']) ? Category::query()->find($rule['subcategory_id']) : null;

                return [
                    'category_id' => $rule['category_id'],
                    'category_name' => $main?->name ?? '',
                    'subcategory_id' => $rule['subcategory_id'] ?? null,
                    'subcategory_name' => $sub?->name ?? '',
                    'max_percent' => $rule['max_percent'],
                ];
            })
            ->values()
            ->all();
    }

    /** @return list<array{category_id: int|null, category_name: string, subcategory_id: int|null, subcategory_name: string, service_category_id: int, service_category_name: string, max_percent: float}> */
    public static function serviceDamageDeductionRulesForSettings(): array
    {
        return collect(static::normalizedDamageDeductionRules())
            ->filter(fn (array $rule) => isset($rule['service_category_id']))
            ->map(function (array $rule) {
                $main = isset($rule['category_id']) ? Category::query()->find($rule['category_id']) : null;
                $sub = isset($rule['subcategory_id']) ? Category::query()->find($rule['subcategory_id']) : null;
                $serviceCategory = Category::query()->find($rule['service_category_id']);

                return [
                    'category_id' => $rule['category_id'] ?? null,
                    'category_name' => $main?->name ?? '',
                    'subcategory_id' => $rule['subcategory_id'] ?? null,
                    'subcategory_name' => $sub?->name ?? '',
                    'service_category_id' => $rule['service_category_id'],
                    'service_category_name' => $serviceCategory?->name ?? '',
                    'max_percent' => $rule['max_percent'],
                ];
            })
            ->values()
            ->all();
    }

    public static function maxDamagePercentForPortfolioItem(?int $subcategoryId, ?int $serviceCategoryId = null): ?float
    {
        $rules = static::normalizedDamageDeductionRules();
        $mainCategoryId = $subcategoryId
            ? Category::query()->whereKey($subcategoryId)->value('parent_id')
            : null;

        if ($serviceCategoryId) {
            foreach ($rules as $rule) {
                if (($rule['service_category_id'] ?? null) !== (int) $serviceCategoryId) {
                    continue;
                }

                if (($rule['subcategory_id'] ?? null) !== null
                    && $subcategoryId
                    && (int) $rule['subcategory_id'] === (int) $subcategoryId) {
                    return $rule['max_percent'];
                }
            }

            foreach ($rules as $rule) {
                if (($rule['service_category_id'] ?? null) !== (int) $serviceCategoryId) {
                    continue;
                }

                if (($rule['subcategory_id'] ?? null) === null
                    && ($rule['category_id'] ?? null) !== null
                    && $mainCategoryId
                    && (int) $rule['category_id'] === (int) $mainCategoryId) {
                    return $rule['max_percent'];
                }
            }

            foreach ($rules as $rule) {
                if (($rule['service_category_id'] ?? null) === (int) $serviceCategoryId
                    && ! isset($rule['category_id'])) {
                    return $rule['max_percent'];
                }
            }
        }

        if ($subcategoryId) {
            foreach ($rules as $rule) {
                if (isset($rule['service_category_id'])) {
                    continue;
                }

                if (($rule['subcategory_id'] ?? null) !== null
                    && (int) $rule['subcategory_id'] === (int) $subcategoryId) {
                    return $rule['max_percent'];
                }
            }

            if ($mainCategoryId) {
                foreach ($rules as $rule) {
                    if (isset($rule['service_category_id'])) {
                        continue;
                    }

                    if (($rule['category_id'] ?? null) === (int) $mainCategoryId
                        && ($rule['subcategory_id'] ?? null) === null) {
                        return $rule['max_percent'];
                    }
                }
            }
        }

        return null;
    }

    public static function maxDamagePercentForServiceCategory(?int $serviceCategoryId): ?float
    {
        if (! $serviceCategoryId) {
            return null;
        }

        foreach (static::normalizedDamageDeductionRules() as $rule) {
            if (($rule['service_category_id'] ?? null) === $serviceCategoryId) {
                return $rule['max_percent'];
            }
        }

        return null;
    }

    /** @deprecated Use maxDamagePercentForServiceCategory() */
    public static function maxDamagePercentForProduct(?string $productType): ?float
    {
        $needle = strtolower(trim((string) $productType));

        if ($needle === '') {
            return null;
        }

        $category = Category::query()
            ->service()
            ->where(function ($query) use ($needle, $productType) {
                $query->whereRaw('LOWER(name) = ?', [$needle])
                    ->orWhere('slug', str($productType)->slug()->toString());
            })
            ->first();

        return $category ? static::maxDamagePercentForServiceCategory($category->id) : null;
    }

    /** @return list<array{service_category_id: int, max_percent: float}> */
    protected static function normalizedDamageDeductionRules(): array
    {
        $rules = static::get('refund_damage_deduction_rules', []);

        if (! is_array($rules)) {
            return [];
        }

        return collect($rules)
            ->map(function (array $rule) {
                if (isset($rule['service_category_id'])) {
                    $normalized = [
                        'service_category_id' => (int) $rule['service_category_id'],
                        'max_percent' => (float) ($rule['max_percent'] ?? 0),
                    ];

                    if (isset($rule['category_id'])) {
                        $normalized['category_id'] = (int) $rule['category_id'];
                        $normalized['subcategory_id'] = filled($rule['subcategory_id'] ?? null)
                            ? (int) $rule['subcategory_id']
                            : null;
                    }

                    return $normalized;
                }

                if (isset($rule['category_id'])) {
                    return [
                        'category_id' => (int) $rule['category_id'],
                        'subcategory_id' => filled($rule['subcategory_id'] ?? null)
                            ? (int) $rule['subcategory_id']
                            : null,
                        'max_percent' => (float) ($rule['max_percent'] ?? 0),
                    ];
                }

                $label = trim((string) ($rule['product_type'] ?? ''));

                if ($label === '') {
                    return null;
                }

                $category = Category::query()
                    ->service()
                    ->where(function ($query) use ($label) {
                        $query->whereRaw('LOWER(name) = ?', [strtolower($label)])
                            ->orWhere('slug', str($label)->slug()->toString());
                    })
                    ->first();

                if (! $category) {
                    return null;
                }

                return [
                    'service_category_id' => $category->id,
                    'max_percent' => (float) ($rule['max_percent'] ?? 0),
                ];
            })
            ->filter()
            ->unique(fn (array $rule) => isset($rule['service_category_id'])
                ? 'service:'.($rule['category_id'] ?? 'all').':'.($rule['subcategory_id'] ?? 'all').':'.$rule['service_category_id']
                : 'catalog:'.$rule['category_id'].':'.($rule['subcategory_id'] ?? 'all'))
            ->values()
            ->all();
    }
}

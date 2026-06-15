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

    /** @return list<array{service_category_id: int, max_percent: float}> */
    public static function damageDeductionRules(): array
    {
        return static::normalizedDamageDeductionRules();
    }

    /** @return list<array{service_category_id: int, service_category_name: string, max_percent: float}> */
    public static function damageDeductionRulesForSettings(): array
    {
        $rules = collect(static::normalizedDamageDeductionRules())->keyBy('service_category_id');

        return Category::query()
            ->service()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'service_category_id' => $category->id,
                'service_category_name' => $category->name,
                'max_percent' => (float) ($rules->get($category->id)['max_percent'] ?? 100),
            ])
            ->values()
            ->all();
    }

    public static function maxDamagePercentForServiceCategory(?int $serviceCategoryId): ?float
    {
        if (! $serviceCategoryId) {
            return null;
        }

        foreach (static::normalizedDamageDeductionRules() as $rule) {
            if ($rule['service_category_id'] === $serviceCategoryId) {
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
                    return [
                        'service_category_id' => (int) $rule['service_category_id'],
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
            ->unique('service_category_id')
            ->values()
            ->all();
    }
}

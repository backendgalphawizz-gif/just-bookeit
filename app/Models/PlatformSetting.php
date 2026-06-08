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

    /** @return list<array{product_type: string, max_percent: float}> */
    public static function damageDeductionRules(): array
    {
        $rules = static::get('refund_damage_deduction_rules', []);

        if (! is_array($rules)) {
            return [];
        }

        return collect($rules)
            ->map(fn (array $rule) => [
                'product_type' => trim((string) ($rule['product_type'] ?? '')),
                'max_percent' => (float) ($rule['max_percent'] ?? 0),
            ])
            ->filter(fn (array $rule) => $rule['product_type'] !== '')
            ->values()
            ->all();
    }

    public static function maxDamagePercentForProduct(?string $productType): ?float
    {
        $needle = strtolower(trim((string) $productType));
        $rules = static::damageDeductionRules();

        if ($needle !== '') {
            foreach ($rules as $rule) {
                if (strtolower($rule['product_type']) === $needle) {
                    return $rule['max_percent'];
                }
            }
        }

        foreach ($rules as $rule) {
            if (strtolower($rule['product_type']) === 'other') {
                return $rule['max_percent'];
            }
        }

        return $rules[0]['max_percent'] ?? null;
    }
}

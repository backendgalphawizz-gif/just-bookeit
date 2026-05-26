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
}

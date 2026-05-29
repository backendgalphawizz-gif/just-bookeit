<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    public const AUDIENCE_USER = 'user';

    public const AUDIENCE_VENDOR = 'vendor';

    public const AUDIENCE_DRIVER = 'driver';

    public const AUDIENCES = [
        self::AUDIENCE_USER,
        self::AUDIENCE_VENDOR,
        self::AUDIENCE_DRIVER,
    ];

    protected $fillable = [
        'audience',
        'question',
        'answer',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeForAudience($query, string $audience)
    {
        return $query->where('audience', $audience);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function audienceLabel(string $audience): string
    {
        return match ($audience) {
            self::AUDIENCE_USER => 'Customer / User app',
            self::AUDIENCE_VENDOR => 'Vendor app',
            self::AUDIENCE_DRIVER => 'Driver app',
            default => ucfirst($audience),
        };
    }
}

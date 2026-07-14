<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    public const AUDIENCE_CUSTOMER = 'customer';

    public const AUDIENCE_VENDOR = 'vendor';

    public const AUDIENCE_DRIVER = 'driver';

    public const AUDIENCES = [
        self::AUDIENCE_CUSTOMER,
        self::AUDIENCE_VENDOR,
        self::AUDIENCE_DRIVER,
    ];

    protected $fillable = [
        'audience',
        'title',
        'subtitle',
        'redirect_url',
        'image_path',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeForAudience(Builder $query, string $audience): Builder
    {
        return $query->where('audience', $audience);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public static function audienceLabel(string $audience): string
    {
        return match ($audience) {
            self::AUDIENCE_VENDOR => 'Vendor',
            self::AUDIENCE_DRIVER => 'Driver',
            default => 'Customer',
        };
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => StoresUploadedFiles::url($this->image_path));
    }
}

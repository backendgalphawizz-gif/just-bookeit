<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const TYPE_MAIN = 'main';

    public const TYPE_SERVICE = 'service';

    public const TYPE_SUB = 'sub';

    public const TYPES = [
        self::TYPE_MAIN,
        self::TYPE_SERVICE,
        self::TYPE_SUB,
    ];

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'image_path',
        'type',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function subcategories(): HasMany
    {
        return $this->children()->where('type', self::TYPE_SUB);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class, 'subcategory_id');
    }

    public function scopeMain(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_MAIN);
    }

    public function scopeService(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_SERVICE);
    }

    public function scopeSub(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_SUB);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isMain(): bool
    {
        return $this->type === self::TYPE_MAIN;
    }

    public function isService(): bool
    {
        return $this->type === self::TYPE_SERVICE;
    }

    public function isSub(): bool
    {
        return $this->type === self::TYPE_SUB;
    }

    public function imageUrl(): ?string
    {
        return StoresUploadedFiles::url($this->image_path);
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_MAIN => 'Categories',
            self::TYPE_SUB => 'Sub-categories',
            self::TYPE_SERVICE => 'Service categories',
            default => ucfirst($type),
        };
    }
}

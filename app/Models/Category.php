<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const TYPE_MAIN = 'main';

    public const TYPE_SERVICE = 'service';

    public const TYPES = [
        self::TYPE_MAIN,
        self::TYPE_SERVICE,
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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function imageUrl(): ?string
    {
        return StoresUploadedFiles::url($this->image_path);
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_MAIN => 'Categories',
            self::TYPE_SERVICE => 'Service categories',
            default => ucfirst($type),
        };
    }
}

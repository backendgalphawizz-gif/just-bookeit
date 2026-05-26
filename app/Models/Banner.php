<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'cta_label',
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

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->image_path || ! Storage::disk('public')->exists($this->image_path)) {
                return null;
            }

            return '/storage/'.ltrim(str_replace('\\', '/', $this->image_path), '/');
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioItem extends Model
{
    public const PENDING_STATUS = 'pending';

    protected $fillable = [
        'vendor_id',
        'category_id',
        'title',
        'description',
        'image_url',
        'status',
        'rejection_reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function displayImageUrl(): ?string
    {
        if (! $this->image_url) {
            return null;
        }

        if (str_starts_with($this->image_url, 'http://') || str_starts_with($this->image_url, 'https://')) {
            return $this->image_url;
        }

        return '/storage/'.ltrim($this->image_url, '/');
    }

    public function rentalPriceLabel(): string
    {
        $price = 800 + (($this->id ?? 1) * 173) % 2700;

        return '₹'.number_format($price, 0).' / day';
    }
}

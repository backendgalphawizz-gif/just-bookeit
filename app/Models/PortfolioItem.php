<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortfolioItem extends Model
{
    public const PENDING_STATUS = 'pending';

    protected $fillable = [
        'vendor_id',
        'category_id',
        'title',
        'description',
        'image_url',
        'audience',
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

    public function images(): HasMany
    {
        return $this->hasMany(PortfolioItemImage::class)->orderBy('sort_order');
    }

    /** @return list<string> */
    public function galleryImageUrls(): array
    {
        $urls = [];

        if ($primary = $this->displayImageUrl()) {
            $urls[] = $primary;
        }

        foreach ($this->images as $image) {
            if ($url = $image->imageUrl()) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
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

    public function rentalPriceAmount(): int
    {
        return 800 + (($this->id ?? 1) * 173) % 2700;
    }

    public function rentalPriceLabel(): string
    {
        return '₹'.number_format($this->rentalPriceAmount(), 0).' / day';
    }

    public function isApprovedForCatalog(): bool
    {
        return in_array($this->status, ['approved', 'pending'], true);
    }
}

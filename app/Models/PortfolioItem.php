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
        'subcategory_id',
        'title',
        'description',
        'price_per_day',
        'advance_amount',
        'image_url',
        'audience',
        'status',
        'rejection_reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'price_per_day' => 'decimal:2',
            'advance_amount' => 'decimal:2',
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

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PortfolioItemImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(PortfolioItemVariant::class)->orderBy('sort_order');
    }

    public function damageDeductions(): HasMany
    {
        return $this->hasMany(PortfolioItemDamageDeduction::class)->orderBy('sort_order');
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
        if ($this->price_per_day !== null) {
            return (int) round((float) $this->price_per_day);
        }

        $variantPrice = $this->relationLoaded('variants')
            ? $this->variants->min('price')
            : $this->variants()->min('price');

        if ($variantPrice !== null) {
            return (int) round((float) $variantPrice);
        }

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

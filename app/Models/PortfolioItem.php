<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
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
        'is_listing_active',
        'rejection_reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'price_per_day' => 'decimal:2',
            'advance_amount' => 'decimal:2',
            'is_listing_active' => 'boolean',
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

    public function galleryImages(): HasMany
    {
        return $this->images()->where(function ($query) {
            $query->where('media_type', PortfolioItemImage::TYPE_IMAGE)
                ->orWhereNull('media_type');
        });
    }

    public function galleryVideos(): HasMany
    {
        return $this->images()->where('media_type', PortfolioItemImage::TYPE_VIDEO);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(PortfolioItemVariant::class)->orderBy('sort_order');
    }

    public function availableVariants(): Collection
    {
        $this->loadMissing('variants');

        return $this->variants->filter(function (PortfolioItemVariant $variant) {
            return filled($variant->size) || filled($variant->color) || (float) $variant->price > 0;
        })->values();
    }

    public function hasVariants(): bool
    {
        return $this->availableVariants()->isNotEmpty();
    }

    public function findVariant(?int $variantId): ?PortfolioItemVariant
    {
        if (! $variantId) {
            return null;
        }

        $this->loadMissing('variants');

        return $this->variants->firstWhere('id', $variantId);
    }

    public function dailyRateFor(?PortfolioItemVariant $variant = null): float
    {
        if ($variant && (float) $variant->price > 0) {
            return (float) $variant->price;
        }

        if ($this->price_per_day !== null && (float) $this->price_per_day > 0) {
            return (float) $this->price_per_day;
        }

        if ($variant && (float) $variant->price >= 0) {
            return (float) $variant->price;
        }

        return (float) $this->rentalPriceAmount();
    }

    public function rentalPriceLabelFor(?PortfolioItemVariant $variant = null): string
    {
        return '₹'.number_format((int) round($this->dailyRateFor($variant)), 0).' / day';
    }

    public function damageDeductions(): HasMany
    {
        return $this->hasMany(PortfolioItemDamageDeduction::class)->orderBy('sort_order');
    }

    /** @return list<string> */
    public function galleryMediaUrls(): array
    {
        $this->loadMissing('images');

        $urls = [];

        if ($primary = $this->displayImageUrl()) {
            $urls[] = $primary;
        }

        foreach ($this->images->sortBy('sort_order') as $media) {
            if ($url = $media->mediaUrl()) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    /** @return list<string> */
    public function galleryImageUrls(): array
    {
        $this->loadMissing('images');

        $urls = [];

        if ($primary = $this->displayImageUrl()) {
            $urls[] = $primary;
        }

        foreach ($this->images as $image) {
            if ($image->isVideo()) {
                continue;
            }

            if ($url = $image->imageUrl()) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    /** @return list<string> */
    public function galleryVideoUrls(): array
    {
        $this->loadMissing('images');

        return $this->images
            ->filter(fn (PortfolioItemImage $media) => $media->isVideo())
            ->map(fn (PortfolioItemImage $media) => $media->mediaUrl())
            ->filter()
            ->values()
            ->all();
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

    public function isCatalogAvailable(): bool
    {
        $this->loadMissing('vendor');

        return $this->status === 'approved'
            && (bool) ($this->is_listing_active ?? true)
            && ($this->vendor?->status === 'active')
            && (bool) ($this->vendor?->is_listing_active ?? false);
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

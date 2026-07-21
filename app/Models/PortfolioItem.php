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

    public function advanceAmountFor(?PortfolioItemVariant $variant = null): float
    {
        if ($variant && $variant->advance_amount !== null) {
            return round(max(0, (float) $variant->advance_amount), 2);
        }

        if ($this->advance_amount !== null) {
            return round(max(0, (float) $this->advance_amount), 2);
        }

        return 0.0;
    }

    public function rentalPriceLabelFor(?PortfolioItemVariant $variant = null): string
    {
        $amount = '₹'.number_format((int) round($this->dailyRateFor($variant)), 0);

        return $this->requiresRentalPeriod() ? $amount.' / day' : $amount;
    }

    public function serviceCategorySlug(): ?string
    {
        $this->loadMissing('category');

        return $this->category?->slug;
    }

    public function isFashionDesignerService(): bool
    {
        return $this->serviceCategorySlug() === 'fashion-designer';
    }

    /** Rental dress/jewellery need a period; fashion designer is a flat booking. */
    public function requiresRentalPeriod(): bool
    {
        return in_array($this->serviceCategorySlug(), ['rented-dress', 'rented-jewellery'], true);
    }

    public function damageDeductions(): HasMany
    {
        return $this->hasMany(PortfolioItemDamageDeduction::class)->orderBy('sort_order');
    }

    /** @return list<string> */
    public function galleryMediaUrls(): array
    {
        return array_values(array_map(
            fn (array $item) => $item['url'],
            $this->galleryMediaItems()
        ));
    }

    /**
     * Ordered gallery entries for product pages (primary image, gallery images, then videos).
     *
     * @return list<array{type: string, url: string, poster: ?string}>
     */
    public function galleryMediaItems(): array
    {
        $this->loadMissing('images');

        $poster = $this->displayImageUrl();
        $items = [];
        $seen = [];

        $push = function (string $type, ?string $url) use (&$items, &$seen, $poster): void {
            if ($url === null || $url === '' || isset($seen[$type.':'.$url])) {
                return;
            }

            $seen[$type.':'.$url] = true;
            $items[] = [
                'type' => $type,
                'url' => $url,
                'poster' => $type === 'video' ? $poster : null,
            ];
        };

        $push('image', $poster);

        foreach ($this->images->sortBy('sort_order') as $media) {
            $push($media->isVideo() ? 'video' : 'image', $media->mediaUrl());
        }

        return $items;
    }

    /** @return list<string> */
    public function galleryImageUrls(): array
    {
        return array_values(array_map(
            fn (array $item) => $item['url'],
            array_filter(
                $this->galleryMediaItems(),
                fn (array $item) => $item['type'] === 'image'
            )
        ));
    }

    /** @return list<string> */
    public function galleryVideoUrls(): array
    {
        return array_values(array_map(
            fn (array $item) => $item['url'],
            array_filter(
                $this->galleryMediaItems(),
                fn (array $item) => $item['type'] === 'video'
            )
        ));
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

    /**
     * Rental dresses price/advance live on variants; mirror mins onto the product for listings.
     */
    public function refreshDressPricingFromVariants(): void
    {
        $this->loadMissing(['category', 'variants']);

        if ($this->category?->slug !== 'rented-dress') {
            return;
        }

        $variants = $this->variants;
        if ($variants->isEmpty()) {
            $this->forceFill([
                'price_per_day' => null,
                'advance_amount' => null,
            ])->save();

            return;
        }

        $minPrice = $variants->min('price');
        $advances = $variants->pluck('advance_amount')->filter(fn ($value) => $value !== null);

        $this->forceFill([
            'price_per_day' => $minPrice !== null ? (float) $minPrice : null,
            'advance_amount' => $advances->isNotEmpty() ? (float) $advances->min() : null,
        ])->save();
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
        $amount = '₹'.number_format($this->rentalPriceAmount(), 0);

        return $this->requiresRentalPeriod() ? $amount.' / day' : $amount;
    }

    public function isApprovedForCatalog(): bool
    {
        return in_array($this->status, ['approved', 'pending'], true);
    }
}

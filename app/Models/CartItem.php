<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'customer_id',
        'vendor_id',
        'portfolio_item_id',
        'portfolio_item_variant_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function portfolioItem(): BelongsTo
    {
        return $this->belongsTo(PortfolioItem::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(PortfolioItemVariant::class, 'portfolio_item_variant_id');
    }

    public function unitDailyRate(): float
    {
        $this->loadMissing(['portfolioItem.variants', 'variant']);

        return $this->portfolioItem?->dailyRateFor($this->variant) ?? 0;
    }

    public function unitAdvanceAmount(): float
    {
        $this->loadMissing(['portfolioItem', 'variant']);

        return $this->portfolioItem?->advanceAmountFor($this->variant) ?? 0.0;
    }
}

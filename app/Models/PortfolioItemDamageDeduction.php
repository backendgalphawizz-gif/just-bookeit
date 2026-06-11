<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioItemDamageDeduction extends Model
{
    protected $fillable = [
        'portfolio_item_id',
        'damage_type',
        'percent',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'percent' => 'decimal:2',
        ];
    }

    public function portfolioItem(): BelongsTo
    {
        return $this->belongsTo(PortfolioItem::class);
    }
}

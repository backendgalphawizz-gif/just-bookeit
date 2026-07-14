<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'portfolio_item_id',
        'vendor_id',
        'quantity',
        'unit_price',
        'line_amount',
        'item_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_amount' => 'decimal:2',
            'item_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function portfolioItem(): BelongsTo
    {
        return $this->belongsTo(PortfolioItem::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function title(): string
    {
        return (string) ($this->item_snapshot['title'] ?? 'Item');
    }

    public function displayImageUrl(): ?string
    {
        $path = $this->item_snapshot['image_url'] ?? null;

        return $path ? \App\Support\StoresUploadedFiles::url($path) : null;
    }

    public function variantLabel(): ?string
    {
        $label = collect([
            $this->item_snapshot['size'] ?? null,
            $this->item_snapshot['color'] ?? null,
        ])->filter()->implode(' · ');

        return $label !== '' ? $label : null;
    }

    public function categoryName(): ?string
    {
        $name = $this->item_snapshot['category'] ?? null;

        return filled($name) ? (string) $name : null;
    }
}

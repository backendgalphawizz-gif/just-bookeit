<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioItemVariant extends Model
{
    protected $fillable = [
        'portfolio_item_id',
        'size',
        'color',
        'price',
        'image_path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function portfolioItem(): BelongsTo
    {
        return $this->belongsTo(PortfolioItem::class);
    }

    public function imageUrl(): ?string
    {
        return StoresUploadedFiles::url($this->image_path);
    }
}

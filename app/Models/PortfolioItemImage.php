<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioItemImage extends Model
{
    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    protected $fillable = [
        'portfolio_item_id',
        'image_path',
        'media_type',
        'sort_order',
    ];

    public function portfolioItem(): BelongsTo
    {
        return $this->belongsTo(PortfolioItem::class);
    }

    public function isVideo(): bool
    {
        return ($this->media_type ?? self::TYPE_IMAGE) === self::TYPE_VIDEO;
    }

    public function isImage(): bool
    {
        return ! $this->isVideo();
    }

    public function imageUrl(): ?string
    {
        return StoresUploadedFiles::url($this->image_path);
    }

    public function mediaUrl(): ?string
    {
        return $this->imageUrl();
    }
}

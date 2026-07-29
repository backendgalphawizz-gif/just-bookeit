<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminInboxNotification extends Model
{
    public const TYPE_PRODUCT_SUBMITTED = 'product_submitted';

    public const TYPE_PRODUCT_RESUBMITTED = 'product_resubmitted';

    protected $fillable = [
        'type',
        'portfolio_item_id',
        'vendor_id',
        'title',
        'message',
        'action_url',
        'read_at',
        'read_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function portfolioItem(): BelongsTo
    {
        return $this->belongsTo(PortfolioItem::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function readByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'read_by_admin_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}

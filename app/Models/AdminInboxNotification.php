<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminInboxNotification extends Model
{
    public const TYPE_PRODUCT_SUBMITTED = 'product_submitted';

    public const TYPE_PRODUCT_RESUBMITTED = 'product_resubmitted';

    public const TYPE_CONTACT_MESSAGE = 'contact_message';

    public const TYPE_SUPPORT_TICKET = 'support_ticket';

    public const TYPE_VENDOR_PENDING = 'vendor_pending';

    public const TYPE_DRIVER_PENDING = 'driver_pending';

    public const TYPE_REFUND_REQUESTED = 'refund_requested';

    public const TYPE_WITHDRAWAL_REQUESTED = 'withdrawal_requested';

    public const TYPE_DISPUTE_RAISED = 'dispute_raised';

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

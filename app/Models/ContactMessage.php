<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    public const STATUS_UNREAD = 'unread';

    public const STATUS_READ = 'read';

    public const INQUIRY_TYPES = [
        'booking' => 'Booking',
        'order_tracking' => 'Order Tracking',
        'support' => 'Support',
        'general' => 'General',
    ];

    protected $fillable = [
        'inquiry_type',
        'email',
        'subject',
        'message',
        'status',
        'read_at',
        'read_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function readByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'read_by_admin_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('status', self::STATUS_UNREAD);
    }

    public function isUnread(): bool
    {
        return $this->status === self::STATUS_UNREAD;
    }

    public function markAsRead(?Admin $admin = null): void
    {
        if (! $this->isUnread()) {
            return;
        }

        $this->forceFill([
            'status' => self::STATUS_READ,
            'read_at' => now(),
            'read_by_admin_id' => $admin?->id,
        ])->save();
    }

    public function inquiryTypeLabel(): string
    {
        return self::INQUIRY_TYPES[$this->inquiry_type] ?? ucfirst(str_replace('_', ' ', $this->inquiry_type));
    }
}

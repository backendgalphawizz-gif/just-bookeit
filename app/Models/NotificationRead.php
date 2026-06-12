<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRead extends Model
{
    public const TYPE_CUSTOMER = 'customer';

    public const TYPE_VENDOR = 'vendor';

    protected $fillable = [
        'notification_log_id',
        'recipient_type',
        'recipient_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(NotificationLog::class, 'notification_log_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}

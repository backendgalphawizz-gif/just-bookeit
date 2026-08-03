<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationLog extends Model
{
    protected $fillable = [
        'admin_id',
        'title',
        'message',
        'channel',
        'audience',
        'target_vendor_id',
        'status',
        'recipients_count',
        'scheduled_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function targetVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'target_vendor_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(NotificationRead::class);
    }
}

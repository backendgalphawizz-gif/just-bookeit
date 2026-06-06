<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    public const STATUSES = ['pending', 'in_progress', 'resolved', 'closed'];

    protected $fillable = [
        'customer_id',
        'subject',
        'email',
        'description',
        'status',
        'admin_reply',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function statusLabel(): string
    {
        return str_replace('_', ' ', ucfirst($this->status));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispute extends Model
{
    public const OPEN_STATUSES = ['raised', 'under_review'];

    public const CHAT_CLOSED_STATUSES = ['resolved', 'closed'];

    protected $fillable = [
        'order_id',
        'raised_by',
        'subject',
        'status',
        'resolution_note',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class)->orderBy('created_at');
    }

    public function isChatOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isChatClosed(): bool
    {
        return in_array($this->status, self::CHAT_CLOSED_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return str_replace('_', ' ', ucfirst($this->status));
    }
}

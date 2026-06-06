<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    public const SENDER_CUSTOMER = 'customer';

    public const SENDER_VENDOR = 'vendor';

    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'body',
        'attachment_path',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function attachmentUrl(): ?string
    {
        return StoresUploadedFiles::url($this->attachment_path);
    }

    public function isFromCustomer(): bool
    {
        return $this->sender_type === self::SENDER_CUSTOMER;
    }
}

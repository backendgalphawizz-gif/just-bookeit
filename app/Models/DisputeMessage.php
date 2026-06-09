<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisputeMessage extends Model
{
    public const SENDER_CUSTOMER = 'customer';

    public const SENDER_ADMIN = 'admin';

    protected $fillable = [
        'dispute_id',
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

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }

    public function attachmentUrl(): ?string
    {
        return StoresUploadedFiles::url($this->attachment_path);
    }

    public function isFromAdmin(): bool
    {
        return $this->sender_type === self::SENDER_ADMIN;
    }

    public function senderLabel(): string
    {
        if ($this->isFromAdmin()) {
            $admin = Admin::query()->find($this->sender_id);

            return $admin?->name ?? 'Support team';
        }

        $customer = Customer::query()->find($this->sender_id);

        return $customer?->name ?? 'Customer';
    }
}

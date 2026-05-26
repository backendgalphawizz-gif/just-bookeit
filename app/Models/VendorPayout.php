<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayout extends Model
{
    public const OPEN_STATUSES = ['pending', 'scheduled', 'processing'];

    protected $fillable = [
        'payout_code',
        'vendor_id',
        'gross_amount',
        'commission_amount',
        'net_amount',
        'status',
        'reference',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}

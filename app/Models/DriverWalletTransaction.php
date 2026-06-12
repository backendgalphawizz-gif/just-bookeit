<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverWalletTransaction extends Model
{
    public const TYPE_DELIVERY_CREDIT = 'delivery_credit';

    public const TYPE_WITHDRAWAL_DEBIT = 'withdrawal_debit';

    protected $fillable = [
        'transaction_code',
        'driver_id',
        'order_id',
        'type',
        'direction',
        'amount',
        'balance_after',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

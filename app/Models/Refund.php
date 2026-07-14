<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    public const OPEN_STATUSES = ['requested', 'under_review', 'approved'];

    protected $fillable = [
        'order_id',
        'checkout_order_id',
        'customer_id',
        'amount',
        'reason',
        'status',
        'source',
        'auto_processed',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'auto_processed' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    public function checkoutOrder(): BelongsTo
    {
        return $this->belongsTo(CheckoutOrder::class);
    }

    public function histories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RefundHistory::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

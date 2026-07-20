<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckoutOrder extends Model
{
    public const STATUSES = [
        'new',
        'pending_acceptance',
        'processing',
        'partially_delivered',
        'completed',
        'partially_cancelled',
        'cancelled',
        'refunded',
    ];

    public const PAYMENT_STATUSES = ['pending', 'advance_paid', 'success', 'failed', 'refunded', 'partially_refunded'];

    protected $fillable = [
        'order_number',
        'customer_id',
        'status',
        'payment_status',
        'payment_method',
        'paid_at',
        'amount',
        'delivery_fee',
        'tax_amount',
        'advance_amount',
        'amount_paid',
        'grand_total',
        'amount_refunded',
        'delivery_address',
        'billing_address',
        'city',
        'pincode',
        'rental_start_date',
        'rental_end_date',
        'customer_notes',
        'measure_height_cm',
        'measure_chest_cm',
        'measure_waist_cm',
        'measurement_type',
        'measure_extra',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'advance_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'amount_refunded' => 'decimal:2',
            'paid_at' => 'datetime',
            'rental_start_date' => 'date',
            'rental_end_date' => 'date',
            'measure_height_cm' => 'decimal:2',
            'measure_chest_cm' => 'decimal:2',
            'measure_waist_cm' => 'decimal:2',
            'measure_extra' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'checkout_order_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'checkout_order_id');
    }

    public function refundableBalance(): float
    {
        return max(0, round((float) $this->grand_total - (float) $this->amount_refunded, 2));
    }

    public static function statusLabelFor(string $status): string
    {
        return match ($status) {
            'new' => 'New',
            'pending_acceptance' => 'Pending acceptance',
            'processing' => 'Processing',
            'partially_delivered' => 'Partially delivered',
            'completed' => 'Completed',
            'partially_cancelled' => 'Partially cancelled',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public function statusLabel(): string
    {
        return self::statusLabelFor((string) $this->status);
    }

    public function scopePaymentConfirmed(Builder $query): Builder
    {
        return $query->whereIn('payment_status', ['success', 'advance_paid']);
    }

    public function isPaymentConfirmed(): bool
    {
        return in_array($this->payment_status, ['success', 'advance_paid'], true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorWalletTransaction extends Model
{
    public const TYPE_PAYMENT_CREDIT = 'payment_credit';

    public const TYPE_HOLD_RELEASE = 'hold_release';

    public const TYPE_REFUND_DEBIT = 'refund_debit';

    public const TYPE_REFUND_REVERSAL = 'refund_reversal';

    public const TYPE_WITHDRAWAL_DEBIT = 'withdrawal_debit';

    public const WALLET_DIGITAL = 'digital';

    public const WALLET_ACTUAL = 'actual';

    protected $fillable = [
        'vendor_id',
        'order_id',
        'refund_id',
        'withdrawal_request_id',
        'type',
        'wallet',
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function withdrawalRequest(): BelongsTo
    {
        return $this->belongsTo(VendorWithdrawalRequest::class, 'withdrawal_request_id');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PAYMENT_CREDIT => 'Payment received',
            self::TYPE_HOLD_RELEASE => 'Released to wallet',
            self::TYPE_REFUND_DEBIT => 'Refund deduction',
            self::TYPE_REFUND_REVERSAL => 'Refund reversed',
            self::TYPE_WITHDRAWAL_DEBIT => 'Withdrawal paid',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function walletLabel(): string
    {
        return $this->wallet === self::WALLET_DIGITAL ? 'Digital wallet' : 'Actual wallet';
    }
}

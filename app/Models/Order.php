<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public const IN_PROGRESS_STATUSES = ['accepted', 'in_progress', 'in_transit', 'pending_acceptance'];

    public const STATUSES = [
        'new',
        'pending_acceptance',
        'accepted',
        'in_progress',
        'in_transit',
        'delivered',
        'cancelled',
        'refunded',
    ];

    public const PAYMENT_STATUSES = ['pending', 'success', 'failed', 'refunded'];

    protected $fillable = [
        'order_number',
        'customer_id',
        'vendor_id',
        'driver_id',
        'category_id',
        'order_type',
        'item_title',
        'item_description',
        'size',
        'color',
        'quantity',
        'event_date',
        'rental_start_date',
        'rental_end_date',
        'return_due_date',
        'delivery_address',
        'pickup_address',
        'city',
        'pincode',
        'amount',
        'security_deposit',
        'delivery_fee',
        'customer_notes',
        'admin_notes',
        'payment_status',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'security_deposit' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'quantity' => 'integer',
            'event_date' => 'date',
            'rental_start_date' => 'date',
            'rental_end_date' => 'date',
            'return_due_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class);
    }

    public function isRental(): bool
    {
        return $this->order_type === 'rental';
    }

    public function orderTypeLabel(): string
    {
        return $this->order_type === 'sale' ? 'Purchase' : 'Rental';
    }

    public function statusLabel(): string
    {
        return str_replace('_', ' ', ucfirst($this->status));
    }

    public function itemDisplayName(): string
    {
        return $this->item_title ?: $this->category?->name ?: 'Clothing item';
    }

    public function grandTotal(): float
    {
        return (float) $this->amount
            + (float) ($this->security_deposit ?? 0)
            + (float) ($this->delivery_fee ?? 0);
    }

    /** @return array<int, array{key: string, label: string, state: string}> */
    public function workflowSteps(): array
    {
        $steps = [
            ['key' => 'new', 'label' => 'Order placed'],
            ['key' => 'pending_acceptance', 'label' => 'Vendor review'],
            ['key' => 'accepted', 'label' => 'Confirmed'],
            ['key' => 'in_progress', 'label' => 'Preparing'],
            ['key' => 'in_transit', 'label' => 'Out for delivery'],
            ['key' => 'delivered', 'label' => $this->isRental() ? 'Delivered / On rent' : 'Delivered'],
        ];

        if (in_array($this->status, ['cancelled', 'refunded'], true)) {
            return array_map(fn (array $step): array => [
                ...$step,
                'state' => 'muted',
            ], $steps);
        }

        $currentIndex = array_search($this->status, array_column($steps, 'key'), true);

        return array_map(function (array $step, int $index) use ($currentIndex): array {
            if ($currentIndex === false) {
                return [...$step, 'state' => 'upcoming'];
            }

            if ($index < $currentIndex) {
                return [...$step, 'state' => 'done'];
            }

            if ($index === $currentIndex) {
                return [...$step, 'state' => 'current'];
            }

            return [...$step, 'state' => 'upcoming'];
        }, $steps, array_keys($steps));
    }

    /** @return array<int, array<string, mixed>> */
    public function quickStatusActions(): array
    {
        $route = fn (string $status) => route('admin.orders.update-status', $this);

        return match ($this->status) {
            'new' => [
                ['label' => 'Send to vendor', 'url' => $route('pending_acceptance'), 'status' => 'pending_acceptance', 'variant' => 'primary'],
                ['label' => 'Accept order', 'url' => $route('accepted'), 'status' => 'accepted', 'variant' => 'success'],
                ['label' => 'Cancel', 'url' => $route('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this order?'],
            ],
            'pending_acceptance' => [
                ['label' => 'Vendor accepted', 'url' => $route('accepted'), 'status' => 'accepted', 'variant' => 'success'],
                ['label' => 'Cancel', 'url' => $route('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this order?'],
            ],
            'accepted' => [
                ['label' => 'Start preparing', 'url' => $route('in_progress'), 'status' => 'in_progress', 'variant' => 'primary'],
                ['label' => 'Cancel', 'url' => $route('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this order?'],
            ],
            'in_progress' => [
                ['label' => 'Dispatch / in transit', 'url' => $route('in_transit'), 'status' => 'in_transit', 'variant' => 'primary'],
                ['label' => 'Cancel', 'url' => $route('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this order?'],
            ],
            'in_transit' => [
                ['label' => 'Mark delivered', 'url' => $route('delivered'), 'status' => 'delivered', 'variant' => 'success'],
            ],
            default => [],
        };
    }
}

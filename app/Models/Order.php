<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
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
        'item_image_path',
        'reference_image_paths',
        'size',
        'color',
        'quantity',
        'event_date',
        'rental_start_date',
        'rental_end_date',
        'return_due_date',
        'delivery_address',
        'billing_address',
        'pickup_address',
        'city',
        'pincode',
        'amount',
        'security_deposit',
        'delivery_fee',
        'tax_amount',
        'customer_notes',
        'admin_notes',
        'damage_note',
        'damage_deduct_percent',
        'measure_height_cm',
        'measure_chest_cm',
        'measure_waist_cm',
        'payment_status',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'security_deposit' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'damage_deduct_percent' => 'decimal:2',
            'quantity' => 'integer',
            'measure_height_cm' => 'integer',
            'measure_chest_cm' => 'integer',
            'measure_waist_cm' => 'integer',
            'reference_image_paths' => 'array',
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
        return ($this->order_type ?? 'rental') === 'rental';
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

    public function itemImageUrl(): ?string
    {
        return StoresUploadedFiles::url($this->item_image_path);
    }

    /** @return array<int, string> */
    public function referenceImageUrls(): array
    {
        return collect($this->reference_image_paths ?? [])
            ->map(fn ($path) => StoresUploadedFiles::url($path))
            ->filter()
            ->values()
            ->all();
    }

    public function subtotal(): float
    {
        return (float) $this->amount;
    }

    public function damageDeduction(): float
    {
        if (! $this->damage_deduct_percent) {
            return 0;
        }

        return round($this->subtotal() * ((float) $this->damage_deduct_percent / 100), 2);
    }

    public function grandTotal(): float
    {
        return max(0, $this->subtotal()
            + (float) ($this->delivery_fee ?? 0)
            + (float) ($this->tax_amount ?? 0)
            - $this->damageDeduction());
    }

    public function rentalDurationDays(): ?int
    {
        if (! $this->rental_start_date || ! $this->rental_end_date) {
            return null;
        }

        return $this->rental_start_date->diffInDays($this->rental_end_date) + 1;
    }

    /** @return array<int, array{label: string, time: ?string, state: string}> */
    public function trackBookingSteps(): array
    {
        $steps = [
            ['keys' => ['new', 'pending_acceptance', 'accepted', 'in_progress', 'in_transit', 'delivered'], 'label' => 'Booking placed', 'min' => 'new'],
            ['keys' => ['pending_acceptance', 'accepted', 'in_progress', 'in_transit', 'delivered'], 'label' => 'Accepted by designer', 'min' => 'pending_acceptance'],
            ['keys' => ['in_transit', 'delivered'], 'label' => 'In transit', 'min' => 'in_transit'],
            ['keys' => ['delivered'], 'label' => 'Delivered', 'min' => 'delivered'],
        ];

        if (in_array($this->status, ['cancelled', 'refunded'], true)) {
            return array_map(fn (array $step): array => [
                'label' => $step['label'],
                'time' => null,
                'state' => 'cancelled',
            ], $steps);
        }

        $rank = array_flip(self::STATUSES);
        $current = $rank[$this->status] ?? 0;

        return array_map(function (array $step) use ($rank, $current): array {
            $minRank = $rank[$step['min']] ?? 0;
            $maxRank = max(array_map(fn ($k) => $rank[$k] ?? 0, $step['keys']));

            if ($current >= $maxRank) {
                $state = 'done';
            } elseif ($current >= $minRank) {
                $state = 'current';
            } else {
                $state = 'upcoming';
            }

            $time = null;
            if ($state === 'done' && $step['min'] === 'new') {
                $time = $this->created_at->format('M d, H:i');
            } elseif ($state === 'current') {
                $time = 'In progress';
            } elseif ($state === 'done') {
                $time = 'Completed';
            }

            return [
                'label' => $step['label'],
                'time' => $time,
                'state' => $state,
            ];
        }, $steps);
    }

    /** @return array<int, array<string, mixed>> */
    public function quickStatusActions(): array
    {
        $route = fn (string $status) => route('admin.orders.update-status', $this);

        return match ($this->status) {
            'new' => [
                ['label' => 'Send to designer', 'url' => $route('pending_acceptance'), 'status' => 'pending_acceptance', 'variant' => 'primary'],
                ['label' => 'Accept booking', 'url' => $route('accepted'), 'status' => 'accepted', 'variant' => 'success'],
                ['label' => 'Cancel booking', 'url' => $route('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this booking?'],
            ],
            'pending_acceptance' => [
                ['label' => 'Designer accepted', 'url' => $route('accepted'), 'status' => 'accepted', 'variant' => 'success'],
                ['label' => 'Cancel booking', 'url' => $route('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this booking?'],
            ],
            'accepted' => [
                ['label' => 'Start preparing outfit', 'url' => $route('in_progress'), 'status' => 'in_progress', 'variant' => 'primary'],
                ['label' => 'Cancel booking', 'url' => $route('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this booking?'],
            ],
            'in_progress' => [
                ['label' => 'Dispatch for delivery', 'url' => $route('in_transit'), 'status' => 'in_transit', 'variant' => 'primary'],
            ],
            'in_transit' => [
                ['label' => 'Mark delivered', 'url' => $route('delivered'), 'status' => 'delivered', 'variant' => 'success'],
            ],
            default => [],
        };
    }
}

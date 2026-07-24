<?php

namespace App\Models;

use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public const STATUS_PENDING = 'pending_acceptance';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        'in_progress',
        'delivered',
        'rental_active',
        'rework',
        're_intransit',
        'returned',
        're_delivered',
        'completed',
        self::STATUS_CANCELLED,
    ];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending acceptance',
        self::STATUS_ACCEPTED => 'Accepted',
        'in_progress' => 'In Transit',
        'delivered' => 'Delivered',
        'rental_active' => 'Rental Active',
        'rework' => 'Rework Requested',
        're_intransit' => 'Return In Transit',
        'returned' => 'Returned to Vendor',
        're_delivered' => 'Re-delivered',
        'completed' => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'order_id',
        'portfolio_item_id',
        'vendor_id',
        'quantity',
        'unit_price',
        'line_amount',
        'status',
        'cancellation_reason',
        'responded_at',
        'driver_id',
        'driver_assigned_at',
        'driver_delivery_status',
        'driver_pickup_at',
        'damage_note',
        'damage_amount',
        'damage_deduct_percent',
        'item_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_amount' => 'decimal:2',
            'damage_amount' => 'decimal:2',
            'damage_deduct_percent' => 'decimal:2',
            'item_snapshot' => 'array',
            'responded_at' => 'datetime',
            'driver_assigned_at' => 'datetime',
            'driver_pickup_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function portfolioItem(): BelongsTo
    {
        return $this->belongsTo(PortfolioItem::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function canAssignDriver(): bool
    {
        if (in_array($this->status, ['in_progress', 're_intransit'], true)) {
            return true;
        }

        // Premature "returned" before return pickup — admin can assign return driver.
        return $this->status === 'returned'
            && blank($this->driver_pickup_at)
            && \App\Support\OrderItemStatusSupport::isRentalItem($this);
    }

    /** @return array<int, array{label: string, time: ?string, state: string}> */
    public function trackSteps(): array
    {
        $order = $this->relationLoaded('order') ? $this->order : $this->order()->first();
        if (! $order) {
            return [];
        }

        $isRental = \App\Support\OrderItemStatusSupport::isRentalItem($this, $order);
        $trackStatus = $this->status;
        // Premature returned (no return pickup yet) tracks as Return In Transit.
        if ($trackStatus === 'returned' && blank($this->driver_pickup_at) && $isRental) {
            $trackStatus = 're_intransit';
        }

        return $order->trackBookingSteps($trackStatus, $isRental);
    }

    public function title(): string
    {
        return (string) ($this->item_snapshot['title'] ?? 'Item');
    }

    public function displayImageUrl(): ?string
    {
        $path = $this->item_snapshot['image_url'] ?? null;

        return $path ? StoresUploadedFiles::url($path) : null;
    }

    public function variantLabel(): ?string
    {
        $label = collect([
            $this->item_snapshot['size'] ?? null,
            $this->item_snapshot['color'] ?? null,
        ])->filter()->implode(' · ');

        return $label !== '' ? $label : null;
    }

    public function size(): ?string
    {
        $size = $this->item_snapshot['size'] ?? null;

        return filled($size) ? (string) $size : null;
    }

    public function color(): ?string
    {
        $color = $this->item_snapshot['color'] ?? null;

        return filled($color) ? (string) $color : null;
    }

    public function variantId(): ?int
    {
        $id = $this->item_snapshot['variant_id'] ?? null;

        return $id !== null ? (int) $id : null;
    }

    public function damageDeduction(): float
    {
        // Prefer the exact amount recorded by the vendor (never recompute from percent).
        if ($this->damage_amount !== null) {
            return round((float) $this->damage_amount, 2);
        }

        if (! $this->damage_deduct_percent) {
            return 0.0;
        }

        return round((float) $this->line_amount * ((float) $this->damage_deduct_percent / 100), 2);
    }

    public function hasDamageRecord(): bool
    {
        return $this->damage_amount !== null
            || $this->damage_deduct_percent !== null
            || filled($this->damage_note);
    }

    /**
     * Persist the vendor-provided amount as-is. Percent is stored only when the vendor sent percent
     * (amount is then derived once at save time — never recalculated later for display).
     *
     * @return array{damage_amount: float|null, damage_deduct_percent: float|null}
     */
    public static function resolveDamageFields(
        float $baseAmount,
        float|int|string|null $amount,
        float|int|string|null $percent,
        string $baseLabel = 'line amount'
    ): array {
        if ($amount !== null && $amount !== '') {
            $amount = round((float) $amount, 2);
            if ($amount < 0) {
                throw new \InvalidArgumentException('Damage amount cannot be negative.');
            }
            if ($baseAmount <= 0) {
                throw new \InvalidArgumentException("No {$baseLabel} for damage calculation.");
            }
            if ($amount > $baseAmount) {
                throw new \InvalidArgumentException("Damage amount cannot exceed {$baseLabel}.");
            }

            return [
                'damage_amount' => $amount,
                'damage_deduct_percent' => null,
            ];
        }

        if ($percent === null || $percent === '') {
            return [
                'damage_amount' => null,
                'damage_deduct_percent' => null,
            ];
        }

        $percent = round((float) $percent, 2);
        if ($percent < 0 || $percent > 100) {
            throw new \InvalidArgumentException('Damage percent must be between 0 and 100.');
        }

        return [
            'damage_amount' => round($baseAmount * ($percent / 100), 2),
            'damage_deduct_percent' => $percent,
        ];
    }

    public function advanceAmount(): float
    {
        if (array_key_exists('advance_amount', $this->item_snapshot ?? []) && $this->item_snapshot['advance_amount'] !== null) {
            return round((float) $this->item_snapshot['advance_amount'], 2);
        }

        $this->loadMissing(['portfolioItem.variants']);
        $variant = null;
        $variantId = $this->variantId();
        if ($variantId && $this->portfolioItem) {
            $variant = $this->portfolioItem->findVariant($variantId);
        }
        $unit = $this->portfolioItem?->advanceAmountFor($variant) ?? 0.0;

        return round($unit * max(1, (int) $this->quantity), 2);
    }

    public function categoryName(): ?string
    {
        $name = $this->item_snapshot['category'] ?? null;

        return filled($name) ? (string) $name : null;
    }

    public function categorySlug(): ?string
    {
        $slug = $this->item_snapshot['category_slug'] ?? $this->item_snapshot['service_type'] ?? null;

        if (filled($slug)) {
            return (string) $slug;
        }

        $this->loadMissing('portfolioItem.category');

        return $this->portfolioItem?->category?->slug;
    }

    public function rentalStartDate(): ?string
    {
        $value = $this->item_snapshot['rental_start_date'] ?? null;

        return filled($value) ? (string) $value : null;
    }

    public function rentalEndDate(): ?string
    {
        $value = $this->item_snapshot['rental_end_date'] ?? null;

        return filled($value) ? (string) $value : null;
    }

    public function rentalDurationDays(): ?int
    {
        $start = $this->rentalStartDate();
        $end = $this->rentalEndDate();

        if (! $start || ! $end) {
            return null;
        }

        try {
            $days = \Carbon\Carbon::parse($start)->startOfDay()->diffInDays(\Carbon\Carbon::parse($end)->startOfDay()) + 1;
        } catch (\Throwable) {
            return null;
        }

        return max(1, (int) $days);
    }

    public function customerNotes(): ?string
    {
        $value = $this->item_snapshot['customer_notes'] ?? null;

        return filled($value) ? (string) $value : null;
    }

    public function measurementProfileId(): ?int
    {
        $value = $this->item_snapshot['measurement_profile_id'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    public function serviceType(): ?string
    {
        $value = $this->item_snapshot['service_type'] ?? $this->categorySlug();

        return filled($value) ? (string) $value : null;
    }

    /** @return list<string> */
    public function referenceImagePaths(): array
    {
        $paths = $this->item_snapshot['reference_image_paths'] ?? [];

        return is_array($paths) ? array_values(array_filter($paths, 'is_string')) : [];
    }

    /** @return list<string> */
    public function referenceImageUrls(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $path) => StoresUploadedFiles::url($path),
            $this->referenceImagePaths()
        )));
    }

    public function statusLabel(): string
    {
        $driverStatus = $this->driver_delivery_status;
        if (! $driverStatus && $this->relationLoaded('order') && $this->order) {
            $driverStatus = \App\Support\OrderItemDriverDeliverySupport::effectiveDriverDeliveryStatus($this, $this->order);
        }

        if ($this->status === 'in_progress' || ($this->status === 'accepted' && $driverStatus)) {
            return match ($driverStatus) {
                Order::DRIVER_STATUS_PICKED_UP => 'Picked up',
                Order::DRIVER_STATUS_OUT_FOR_DELIVERY => 'Out for delivery',
                Order::DRIVER_STATUS_ACCEPTED => $this->status === 'accepted'
                    ? 'Accepted · Driver assigned'
                    : 'In Transit · Driver assigned',
                Order::DRIVER_STATUS_RESCHEDULED => 'Rescheduled',
                default => self::STATUS_LABELS[$this->status] ?? 'In Transit',
            };
        }

        if ($this->status === 're_intransit') {
            return match ($driverStatus) {
                Order::DRIVER_STATUS_PICKED_UP => 'Return picked up',
                Order::DRIVER_STATUS_OUT_FOR_DELIVERY => 'Return out for delivery',
                Order::DRIVER_STATUS_ACCEPTED => 'Return In Transit · Driver assigned',
                Order::DRIVER_STATUS_RESCHEDULED => 'Return rescheduled',
                default => self::STATUS_LABELS['re_intransit'],
            };
        }

        return self::STATUS_LABELS[$this->status]
            ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function driverDeliveryStatusLabel(): ?string
    {
        $status = $this->driver_delivery_status
            ?? ($this->relationLoaded('order')
                ? \App\Support\OrderItemDriverDeliverySupport::effectiveDriverDeliveryStatus($this, $this->order)
                : null);

        return match ($status) {
            Order::DRIVER_STATUS_ACCEPTED => 'Accepted',
            Order::DRIVER_STATUS_PICKED_UP => 'Picked up',
            Order::DRIVER_STATUS_OUT_FOR_DELIVERY => 'Out for delivery',
            Order::DRIVER_STATUS_RESCHEDULED => 'Rescheduled',
            default => null,
        };
    }

    public function isPickedUp(): bool
    {
        $status = $this->driver_delivery_status
            ?? ($this->relationLoaded('order')
                ? \App\Support\OrderItemDriverDeliverySupport::effectiveDriverDeliveryStatus($this, $this->order)
                : null);

        return in_array($status, [
            Order::DRIVER_STATUS_PICKED_UP,
            Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
        ], true);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canAccept(): bool
    {
        return $this->isPending();
    }

    public function canReject(): bool
    {
        return $this->isPending();
    }

    public function canUpdateStatus(): bool
    {
        return ! in_array($this->status, [self::STATUS_CANCELLED, 'completed'], true);
    }

    public function refundableLineTotal(): float
    {
        return round((float) $this->line_amount, 2);
    }
}

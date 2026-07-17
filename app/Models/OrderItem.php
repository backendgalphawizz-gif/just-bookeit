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
        self::STATUS_CANCELLED,
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
        'item_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_amount' => 'decimal:2',
            'item_snapshot' => 'array',
            'responded_at' => 'datetime',
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

    public function categoryName(): ?string
    {
        $name = $this->item_snapshot['category'] ?? null;

        return filled($name) ? (string) $name : null;
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
        $value = $this->item_snapshot['service_type'] ?? null;

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
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending acceptance',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canAccept(): bool
    {
        return $this->isPending();
    }

    public function canReject(): bool
    {
        return $this->isPending();
    }

    public function refundableLineTotal(): float
    {
        return round((float) $this->line_amount, 2);
    }
}

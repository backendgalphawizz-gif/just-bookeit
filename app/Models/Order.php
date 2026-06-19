<?php

namespace App\Models;

use App\Support\OrderDispatchSupport;
use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public const IN_PROGRESS_STATUSES = ['accepted', 'in_progress', 're_intransit', 'rework', 'pending_acceptance'];

    public const STATUSES = [
        'new',
        'pending_acceptance',
        'accepted',
        'in_progress',
        'delivered',
        'returned',
        'rework',
        're_intransit',
        're_delivered',
        'cancelled',
        'refunded',
    ];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'new' => 'New',
        'pending_acceptance' => 'Pending acceptance',
        'accepted' => 'Accepted',
        'in_progress' => 'In progress',
        'delivered' => 'Delivered',
        'returned' => 'Returned',
        'rework' => 'Rework',
        're_intransit' => 'Re-in transit',
        're_delivered' => 'Re-delivered',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ];

    public const PAYMENT_STATUSES = ['pending', 'success', 'failed', 'refunded'];

    public const DRIVER_STATUS_ACCEPTED = 'accepted';

    public const DRIVER_STATUS_PICKED_UP = 'picked_up';

    public const DRIVER_STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const DRIVER_STATUS_RESCHEDULED = 'rescheduled';

    protected $fillable = [
        'order_number',
        'customer_id',
        'vendor_id',
        'driver_id',
        'category_id',
        'portfolio_item_id',
        'subcategory_id',
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
        'cancellation_reason',
        'admin_notes',
        'damage_note',
        'damage_deduct_percent',
        'measure_height_cm',
        'measure_chest_cm',
        'measure_waist_cm',
        'measurement_type',
        'measure_extra',
        'payment_status',
        'payment_method',
        'paid_at',
        'wallet_release_at',
        'wallet_settled_at',
        'vendor_net_amount',
        'vendor_wallet_held_amount',
        'wallet_hold_status',
        'status',
        'delivery_otp',
        'driver_delivery_status',
        'driver_assigned_at',
        'driver_pickup_at',
        'driver_delivered_at',
        'driver_earning',
        'driver_rejection_reason',
        'driver_scheduled_for',
        'driver_rescheduled_at',
        'cod_collected_at',
        'driver_delivery_proof_path',
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
            'measure_extra' => 'array',
            'reference_image_paths' => 'array',
            'event_date' => 'date',
            'rental_start_date' => 'date',
            'rental_end_date' => 'date',
            'return_due_date' => 'date',
            'paid_at' => 'datetime',
            'wallet_release_at' => 'datetime',
            'wallet_settled_at' => 'datetime',
            'driver_assigned_at' => 'datetime',
            'driver_pickup_at' => 'datetime',
            'driver_delivered_at' => 'datetime',
            'driver_rescheduled_at' => 'datetime',
            'driver_scheduled_for' => 'date',
            'cod_collected_at' => 'datetime',
            'driver_earning' => 'decimal:2',
            'vendor_net_amount' => 'decimal:2',
            'vendor_wallet_held_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Order $order) {
            if ($order->isDirty('status') && in_array($order->status, ['in_progress', 're_intransit'], true)) {
                OrderDispatchSupport::prepareForTransit($order);
            }

            if (
                $order->isDirty('driver_id')
                && $order->driver_id !== null
                && OrderDispatchSupport::isDispatchStatus($order->status)
                && blank($order->driver_delivery_status)
            ) {
                $order->driver_delivery_status = self::DRIVER_STATUS_ACCEPTED;
                $order->driver_assigned_at = $order->driver_assigned_at ?? now();
            }
        });

        static::creating(function (Order $order) {
            OrderDispatchSupport::preparePickupAddress($order);
        });
    }

    public static function generateDeliveryOtpValue(): string
    {
        return (string) random_int(1000, 9999);
    }

    public function ensureDeliveryOtp(): ?string
    {
        if (! in_array($this->status, ['in_progress', 're_intransit'], true)) {
            return null;
        }

        if ($this->delivery_otp) {
            return $this->delivery_otp;
        }

        $otp = self::generateDeliveryOtpValue();
        $this->forceFill(['delivery_otp' => $otp])->saveQuietly();

        return $otp;
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

    public function portfolioItem(): BelongsTo
    {
        return $this->belongsTo(PortfolioItem::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(OrderReview::class);
    }

    public function isCod(): bool
    {
        return $this->payment_method === 'cod';
    }

    public function isRental(): bool
    {
        return ($this->order_type ?? 'rental') === 'rental';
    }

    public function orderTypeLabel(): string
    {
        return $this->order_type === 'sale' ? 'Purchase' : 'Rental';
    }

    public static function statusLabelFor(?string $status): string
    {
        if (! $status) {
            return '—';
        }

        return self::STATUS_LABELS[$status] ?? str_replace('_', ' ', ucfirst($status));
    }

    public function statusLabel(): string
    {
        return self::statusLabelFor($this->status);
    }

    public function itemDisplayName(): string
    {
        return $this->item_title ?: $this->category?->name ?: 'Clothing item';
    }

    public function itemImageUrl(): ?string
    {
        return StoresUploadedFiles::url($this->item_image_path);
    }

    public function deliveryProofImageUrl(): ?string
    {
        return StoresUploadedFiles::url($this->driver_delivery_proof_path);
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

        return (int) ($this->rental_start_date->diffInDays($this->rental_end_date) + 1);
    }

    public function rentalDaysElapsed(): ?int
    {
        if (! $this->rental_start_date) {
            return null;
        }

        $start = $this->rental_start_date->copy()->startOfDay();
        $today = now()->startOfDay();

        if ($today->lt($start)) {
            return 0;
        }

        $end = $this->rental_end_date?->copy()->startOfDay() ?? $today;
        $cap = $today->gt($end) ? $end : $today;

        return (int) ($start->diffInDays($cap) + 1);
    }

    public function rentalDaysRemaining(): ?int
    {
        if (! $this->rental_end_date) {
            return null;
        }

        $end = $this->rental_end_date->copy()->startOfDay();
        $today = now()->startOfDay();

        if ($today->gt($end)) {
            return 0;
        }

        if ($this->rental_start_date && $today->lt($this->rental_start_date->copy()->startOfDay())) {
            return $this->rentalDurationDays();
        }

        return (int) $today->diffInDays($end);
    }

    public function rentalProgressPercent(): ?int
    {
        $duration = $this->rentalDurationDays();
        $elapsed = $this->rentalDaysElapsed();

        if (! $duration || $elapsed === null || $duration <= 0) {
            return null;
        }

        return (int) min(100, round(($elapsed / $duration) * 100));
    }

    public function rentalTrackingPhase(): string
    {
        if (! $this->isRental()) {
            return 'not_rental';
        }

        if (in_array($this->status, ['cancelled', 'refunded'], true)) {
            return 'cancelled';
        }

        if (! $this->rental_start_date || ! $this->rental_end_date) {
            return 'unscheduled';
        }

        $today = now()->startOfDay();
        $start = $this->rental_start_date->copy()->startOfDay();
        $end = $this->rental_end_date->copy()->startOfDay();
        $returnDue = ($this->return_due_date ?? $this->rental_end_date)->copy()->startOfDay();

        if ($today->lt($start)) {
            return 'upcoming';
        }

        if ($today->lte($end)) {
            return 'active';
        }

        if ($today->lte($returnDue)) {
            return 'awaiting_return';
        }

        return 'overdue';
    }

    public function rentalPhaseLabel(): string
    {
        return match ($this->rentalTrackingPhase()) {
            'upcoming' => $this->rental_start_date
                ? 'Starts '.$this->rental_start_date->diffForHumans(now()->startOfDay(), true).' from now'
                : 'Rental upcoming',
            'active' => 'Rental in progress',
            'awaiting_return' => 'Awaiting return',
            'overdue' => 'Return overdue',
            'unscheduled' => 'Rental dates not set',
            'cancelled' => 'Rental cancelled',
            default => 'Rental',
        };
    }

    /** @return array<string, mixed>|null */
    public function rentalTrackingSummary(): ?array
    {
        if (! $this->isRental()) {
            return null;
        }

        return [
            'duration_days' => $this->rentalDurationDays(),
            'days_elapsed' => $this->rentalDaysElapsed(),
            'days_remaining' => $this->rentalDaysRemaining(),
            'progress_percent' => $this->rentalProgressPercent(),
            'phase' => $this->rentalTrackingPhase(),
            'phase_label' => $this->rentalPhaseLabel(),
            'start_date' => $this->rental_start_date?->format('M d, Y'),
            'end_date' => $this->rental_end_date?->format('M d, Y'),
            'return_due_date' => ($this->return_due_date ?? $this->rental_end_date)?->format('M d, Y'),
            'event_date' => $this->event_date?->format('M d, Y'),
        ];
    }

    /** @return array<int, array{label: string, time: ?string, state: string, detail: ?string}> */
    public function trackRentalSteps(): array
    {
        if (! $this->isRental()) {
            return [];
        }

        $cancelled = in_array($this->status, ['cancelled', 'refunded'], true);
        $today = now()->startOfDay();
        $start = $this->rental_start_date?->copy()->startOfDay();
        $end = $this->rental_end_date?->copy()->startOfDay();
        $returnDue = ($this->return_due_date ?? $this->rental_end_date)?->copy()->startOfDay();
        $duration = $this->rentalDurationDays();

        $steps = [
            $this->rentalTrackStep(
                'Booking placed',
                $this->created_at->format('M d, Y · H:i'),
                $cancelled ? 'cancelled' : 'done'
            ),
        ];

        $deliveryState = match (true) {
            $cancelled => 'cancelled',
            $this->status === 'delivered' => 'done',
            $this->status === 'in_progress' => 'current',
            $this->status === 'accepted' => 'upcoming',
            default => 'upcoming',
        };
        $deliveryTime = match ($deliveryState) {
            'done' => 'Delivered to customer',
            'current' => 'Out for delivery',
            default => null,
        };
        $steps[] = $this->rentalTrackStep('Outfit delivered', $deliveryTime, $deliveryState);

        if (! $start || ! $end) {
            $steps[] = $this->rentalTrackStep('Rental schedule', 'Dates not set', $cancelled ? 'cancelled' : 'upcoming', null);

            return $steps;
        }

        $startState = $cancelled ? 'cancelled' : ($today->lt($start) ? 'upcoming' : 'done');
        $steps[] = $this->rentalTrackStep('Rental starts', $start->format('M d, Y'), $startState);

        $periodState = 'upcoming';
        $periodTime = null;
        $periodDetail = $duration ? $duration.' day rental duration' : null;

        if ($cancelled) {
            $periodState = 'cancelled';
        } elseif ($today->gte($start) && $today->lte($end)) {
            $periodState = 'current';
            $elapsed = $this->rentalDaysElapsed();
            $periodTime = 'Day '.$elapsed.' of '.$duration;
        } elseif ($today->gt($end)) {
            $periodState = 'done';
            $periodTime = $duration.' days completed';
        }

        $steps[] = $this->rentalTrackStep('Rental period', $periodTime, $periodState, $periodDetail);

        $returnState = 'upcoming';
        $returnTime = $returnDue?->format('M d, Y');

        if ($returnDue) {
            if ($cancelled) {
                $returnState = 'cancelled';
            } elseif ($today->gt($returnDue)) {
                $returnState = 'current';
                $overdueDays = (int) $returnDue->diffInDays($today);
                $returnTime = 'Overdue by '.$overdueDays.' day'.($overdueDays === 1 ? '' : 's');
            } elseif ($today->equalTo($returnDue)) {
                $returnState = 'current';
                $returnTime = 'Due today';
            } elseif ($today->gt($end)) {
                $returnState = 'current';
            }
        }

        $steps[] = $this->rentalTrackStep('Return due', $returnTime, $returnState);

        return $steps;
    }

    /** @return array{label: string, time: ?string, state: string, detail: ?string} */
    protected function rentalTrackStep(string $label, ?string $time, string $state, ?string $detail = null): array
    {
        return [
            'label' => $label,
            'time' => $time,
            'state' => $state,
            'detail' => $detail,
        ];
    }

    /** @return array<int, array{label: string, time: ?string, state: string}> */
    public function trackBookingSteps(): array
    {
        $steps = [
            ['keys' => ['new', 'pending_acceptance', 'accepted', 'in_progress', 'delivered'], 'label' => 'Booking placed', 'min' => 'new'],
            ['keys' => ['pending_acceptance', 'accepted', 'in_progress', 'delivered'], 'label' => 'Accepted by designer', 'min' => 'pending_acceptance'],
            ['keys' => ['in_progress', 'delivered'], 'label' => 'In progress', 'min' => 'in_progress'],
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
                ['label' => 'Mark delivered', 'url' => $route('delivered'), 'status' => 'delivered', 'variant' => 'success'],
            ],
            'delivered' => $this->isRental()
                ? [
                    ['label' => 'Start return pickup', 'url' => $route('re_intransit'), 'status' => 're_intransit', 'variant' => 'primary'],
                    ['label' => 'Mark returned', 'url' => $route('returned'), 'status' => 'returned', 'variant' => 'primary'],
                ]
                : [
                    ['label' => 'Mark returned', 'url' => $route('returned'), 'status' => 'returned', 'variant' => 'primary'],
                    ['label' => 'Send for rework', 'url' => $route('rework'), 'status' => 'rework', 'variant' => 'primary'],
                ],
            're_intransit' => $this->isRental()
                ? [
                    ['label' => 'Mark returned', 'url' => $route('returned'), 'status' => 'returned', 'variant' => 'success'],
                ]
                : [
                    ['label' => 'Mark re-delivered', 'url' => $route('re_delivered'), 'status' => 're_delivered', 'variant' => 'success'],
                ],
            'rework' => [
                ['label' => 'Dispatch rework', 'url' => $route('re_intransit'), 'status' => 're_intransit', 'variant' => 'primary'],
            ],
            default => [],
        };
    }
}

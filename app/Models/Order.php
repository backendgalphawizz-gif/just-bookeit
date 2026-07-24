<?php

namespace App\Models;

use App\Support\OrderDispatchSupport;
use App\Support\OrderItemStatusSupport;
use App\Support\StoresUploadedFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public const IN_PROGRESS_STATUSES = [
        'accepted',
        'in_progress',
        'rental_active',
        're_intransit',
        'rework',
        'pending_acceptance',
    ];

    public const STATUSES = [
        'new',
        'pending_acceptance',
        'accepted',
        'in_progress',
        'delivered',
        'rental_active',
        'rework',
        're_intransit',
        'returned',
        're_delivered',
        'completed',
        'cancelled',
        'refunded',
    ];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'new' => 'New',
        'pending_acceptance' => 'Pending acceptance',
        'accepted' => 'Accepted',
        'in_progress' => 'In Transit',
        'delivered' => 'Delivered',
        'rental_active' => 'Rental Active',
        'rework' => 'Rework Requested',
        're_intransit' => 'Return In Transit',
        'returned' => 'Returned to Vendor',
        're_delivered' => 'Re-delivered',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ];

    public const PAYMENT_STATUSES = ['pending', 'advance_paid', 'success', 'failed', 'refunded'];

    public const DRIVER_STATUS_ACCEPTED = 'accepted';

    public const DRIVER_STATUS_PICKED_UP = 'picked_up';

    public const DRIVER_STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const DRIVER_STATUS_RESCHEDULED = 'rescheduled';

    protected $fillable = [
        'checkout_order_id',
        'order_number',
        'sub_order_number',
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
        'advance_amount',
        'amount_paid',
        'customer_notes',
        'cancellation_reason',
        'admin_notes',
        'damage_note',
        'damage_amount',
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
            'advance_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'damage_amount' => 'decimal:2',
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

            // Admin/system driver assignment is treated as accepted — no separate driver accept step.
            if ($order->isDirty('driver_id') && ! $order->isDirty('driver_delivery_status')) {
                if ($order->driver_id === null) {
                    $order->driver_delivery_status = null;
                    $order->driver_assigned_at = null;
                    $order->driver_pickup_at = null;
                    $order->driver_scheduled_for = null;
                    $order->driver_rescheduled_at = null;
                } else {
                    $order->driver_delivery_status = self::DRIVER_STATUS_ACCEPTED;
                    $order->driver_assigned_at = $order->driver_assigned_at ?? now();
                    $order->driver_pickup_at = null;
                    $order->driver_scheduled_for = null;
                    $order->driver_rescheduled_at = null;
                    $order->driver_rejection_reason = null;
                }
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

    public function checkoutOrder(): BelongsTo
    {
        return $this->belongsTo(CheckoutOrder::class);
    }

    public function orderItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
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

    public function scopePaymentConfirmed(Builder $query): Builder
    {
        return $query->whereIn('payment_status', ['success', 'advance_paid']);
    }

    public function isPaymentConfirmed(): bool
    {
        return in_array($this->payment_status, ['success', 'advance_paid'], true);
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
        return $this->hasOne(Refund::class)->latestOfMany();
    }

    public function refunds(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Refund::class);
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
        $this->loadMissing('category');
        $slug = $this->category?->slug;

        if ($slug === 'fashion-designer') {
            return false;
        }

        if (in_array($slug, ['rented-dress', 'rented-jewellery'], true)) {
            return true;
        }

        return ($this->order_type ?? 'rental') === 'rental';
    }

    public function requiresRentalPeriod(): bool
    {
        return $this->isRental();
    }

    public function orderTypeLabel(): string
    {
        if (! $this->isRental()) {
            return $this->order_type === 'sale' ? 'Purchase' : 'Service';
        }

        return 'Rental';
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
        $this->loadMissing('orderItems');

        if ($this->orderItems->isNotEmpty()) {
            $hasItemDamage = $this->orderItems->contains(
                fn (OrderItem $item) => $item->hasDamageRecord()
            );

            if ($hasItemDamage) {
                return round(
                    (float) $this->orderItems->sum(fn (OrderItem $item) => $item->damageDeduction()),
                    2
                );
            }
        }

        // Prefer exact amount recorded by the vendor (never recompute from percent).
        if ($this->damage_amount !== null) {
            return round((float) $this->damage_amount, 2);
        }

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

    /**
     * Rental clock starts only after the outfit is delivered to the customer.
     */
    public function hasRentalPeriodStarted(): bool
    {
        if (! $this->isRental()) {
            return false;
        }

        return in_array($this->status, [
            'delivered',
            're_intransit',
            'returned',
            'rework',
            're_delivered',
        ], true);
    }

    public function rentalDaysElapsed(): ?int
    {
        if (! $this->rental_start_date) {
            return null;
        }

        if (! $this->hasRentalPeriodStarted()) {
            return 0;
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

        if (! $this->hasRentalPeriodStarted()) {
            return $this->rentalDurationDays();
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
        if (! $this->hasRentalPeriodStarted()) {
            return 0;
        }

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

        // Do not start the rental period until the outfit is delivered.
        if (! $this->hasRentalPeriodStarted()) {
            return 'awaiting_delivery';
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
            'awaiting_delivery' => 'Rental starts after delivery',
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
            'started' => $this->hasRentalPeriodStarted(),
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
        $started = $this->hasRentalPeriodStarted();
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
            $started => 'done',
            $this->status === 'in_progress' => 'current',
            $this->status === 'accepted' => 'upcoming',
            default => 'upcoming',
        };
        $deliveryTime = match ($deliveryState) {
            'done' => 'Delivered to customer',
            'current' => 'Out for delivery',
            default => 'Rental period starts after delivery',
        };
        $steps[] = $this->rentalTrackStep('Outfit delivered', $deliveryTime, $deliveryState);

        if (! $start || ! $end) {
            $steps[] = $this->rentalTrackStep('Rental schedule', 'Dates not set', $cancelled ? 'cancelled' : 'upcoming', null);

            return $steps;
        }

        $startState = $cancelled
            ? 'cancelled'
            : (! $started ? 'upcoming' : ($today->lt($start) ? 'upcoming' : 'done'));
        $startDetail = ! $started ? 'Starts when outfit is delivered' : null;
        $steps[] = $this->rentalTrackStep(
            'Rental starts',
            $started ? $start->format('M d, Y') : 'After delivery',
            $startState,
            $startDetail
        );

        $periodState = 'upcoming';
        $periodTime = null;
        $periodDetail = $duration ? $duration.' day rental duration' : null;

        if ($cancelled) {
            $periodState = 'cancelled';
        } elseif (! $started) {
            $periodState = 'upcoming';
            $periodTime = 'Waiting for delivery';
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
            } elseif (! $started) {
                $returnState = 'upcoming';
                $returnTime = 'After rental ends';
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

    /**
     * Status used for the booking-level tracking timeline.
     * Uses the furthest (most advanced) active item so mixed bookings do not
     * stay stuck on a stale booking.status or an earlier dispatch leg
     * (e.g. one item In Transit must not hide another on Return In Transit).
     * Prefer per-item trackSteps() when rendering multi-item bookings.
     */
    public function statusForTracking(): string
    {
        $this->loadMissing('orderItems');
        $active = $this->orderItems->where('status', '!=', OrderItem::STATUS_CANCELLED)->values();

        if ($active->isEmpty()) {
            return $this->status;
        }

        $best = $this->status;
        $bestRank = OrderItemStatusSupport::STATUS_RANK[$best] ?? -1;

        foreach ($active as $item) {
            $trackStatus = $item->status;
            // Premature "returned" (no return pickup yet) still tracks as Return In Transit.
            if ($trackStatus === 'returned' && blank($item->driver_pickup_at)) {
                $trackStatus = 're_intransit';
            }

            $rank = OrderItemStatusSupport::STATUS_RANK[$trackStatus] ?? -1;
            if ($rank > $bestRank) {
                $bestRank = $rank;
                $best = $trackStatus;
            }
        }

        return $best;
    }

    /**
     * @param  string|null  $forStatus  Override status (e.g. line-item status for item-wise tracking).
     * @param  bool|null  $asRental  Override rental vs designer track (per line item). Null = booking isRental().
     * @return array<int, array{label: string, time: ?string, state: string}>
     */
    public function trackBookingSteps(?string $forStatus = null, ?bool $asRental = null): array
    {
        $status = $forStatus ?? $this->statusForTracking();
        $isRental = $asRental ?? $this->isRental();

        $steps = [
            ['keys' => ['new', 'pending_acceptance', 'accepted', 'in_progress', 'delivered', 'rental_active', 'rework', 're_intransit', 'returned', 're_delivered', 'completed'], 'label' => 'Booking placed', 'min' => 'new'],
            ['keys' => ['accepted', 'in_progress', 'delivered', 'rental_active', 'rework', 're_intransit', 'returned', 're_delivered', 'completed'], 'label' => 'Accepted', 'min' => 'accepted'],
            ['keys' => ['in_progress', 'delivered', 'rental_active', 'rework', 're_intransit', 'returned', 're_delivered', 'completed'], 'label' => 'In Transit', 'min' => 'in_progress'],
            ['keys' => ['delivered', 'rental_active', 'rework', 're_intransit', 'returned', 're_delivered', 'completed'], 'label' => 'Delivered', 'min' => 'delivered'],
            ['keys' => ['rental_active', 'rework', 're_intransit', 'returned', 're_delivered', 'completed'], 'label' => 'Rental Active', 'min' => 'rental_active'],
            ['keys' => ['re_intransit', 'returned', 're_delivered', 'completed'], 'label' => 'Return In Transit', 'min' => 're_intransit'],
            ['keys' => ['returned', 're_delivered', 'completed'], 'label' => 'Returned to Vendor', 'min' => 'returned'],
            ['keys' => ['completed'], 'label' => 'Completed', 'min' => 'completed'],
        ];

        if (in_array($status, ['cancelled', 'refunded'], true)) {
            return array_map(fn (array $step): array => [
                'label' => $step['label'],
                'time' => null,
                'state' => 'cancelled',
            ], $steps);
        }

        if ($isRental) {
            // Rental: Delivered → Rental Active → Return In Transit → Returned → Completed
            // No Rework / Re-delivered on this track.
            $steps = array_values(array_filter(
                $steps,
                fn (array $step) => ! in_array($step['min'], ['rework', 're_delivered'], true)
            ));
        } else {
            // Fashion designer: Delivered → Completed, with Rework / Re-delivered branch.
            // No Rental Active / Return In Transit / Returned.
            $steps = array_values(array_filter(
                $steps,
                fn (array $step) => ! in_array($step['min'], ['rental_active', 're_intransit', 'returned'], true)
            ));
            $steps[] = ['keys' => ['rework', 're_intransit', 're_delivered', 'completed'], 'label' => 'Rework', 'min' => 'rework'];
            $steps[] = ['keys' => ['re_delivered', 'completed'], 'label' => 'Re-delivered', 'min' => 're_delivered'];
            $steps[] = ['keys' => ['completed'], 'label' => 'Completed', 'min' => 'completed'];
            // Dedupe completed
            $seen = [];
            $steps = array_values(array_filter($steps, function (array $step) use (&$seen) {
                if (isset($seen[$step['label']])) {
                    return false;
                }
                $seen[$step['label']] = true;

                return true;
            }));
        }

        $rank = array_flip(self::STATUSES);
        // Treat pending acceptance as still on the first step so the timeline has a clear current marker.
        $current = $rank[$status === 'pending_acceptance' ? 'new' : $status] ?? 0;

        // Designer "delivered" is current until completed; rental "delivered" is current until rental_active.
        return array_map(function (array $step) use ($rank, $current): array {
            $minRank = $rank[$step['min']] ?? 0;

            if ($current > $minRank) {
                $state = 'done';
            } elseif ($current === $minRank) {
                $state = 'current';
            } else {
                $state = 'upcoming';
            }

            $time = null;
            if ($step['min'] === 'new' && $this->created_at) {
                $time = $this->created_at->format('M d, Y · H:i');
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
                ['label' => 'Cancel booking', 'url' => $route('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this booking?'],
            ],
            'pending_acceptance' => [
                ['label' => 'Cancel booking', 'url' => $route('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this booking?'],
            ],
            'accepted' => [
                ['label' => 'Mark In Transit', 'url' => $route('in_progress'), 'status' => 'in_progress', 'variant' => 'primary'],
                ['label' => 'Cancel booking', 'url' => $route('cancelled'), 'status' => 'cancelled', 'variant' => 'danger', 'confirm' => 'Cancel this booking?'],
            ],
            'in_progress' => [
                ['label' => 'Mark Delivered', 'url' => $route('delivered'), 'status' => 'delivered', 'variant' => 'success'],
            ],
            'delivered' => $this->isRental()
                ? [
                    ['label' => 'Mark Rental Active', 'url' => $route('rental_active'), 'status' => 'rental_active', 'variant' => 'primary'],
                    ['label' => 'Start Return Pickup', 'url' => $route('re_intransit'), 'status' => 're_intransit', 'variant' => 'outline'],
                ]
                : [
                    ['label' => 'Mark Completed', 'url' => $route('completed'), 'status' => 'completed', 'variant' => 'success'],
                    ['label' => 'Send for Rework', 'url' => $route('rework'), 'status' => 'rework', 'variant' => 'primary'],
                ],
            'rental_active' => [
                ['label' => 'Start Return Pickup', 'url' => $route('re_intransit'), 'status' => 're_intransit', 'variant' => 'primary'],
                ['label' => 'Send for Rework', 'url' => $route('rework'), 'status' => 'rework', 'variant' => 'outline'],
            ],
            're_intransit' => $this->isRental()
                ? [
                    ['label' => 'Mark Returned', 'url' => $route('returned'), 'status' => 'returned', 'variant' => 'success'],
                ]
                : [
                    ['label' => 'Mark Re-delivered', 'url' => $route('re_delivered'), 'status' => 're_delivered', 'variant' => 'success'],
                ],
            'rework' => [
                ['label' => 'Dispatch Rework (Return In Transit)', 'url' => $route('re_intransit'), 'status' => 're_intransit', 'variant' => 'primary'],
            ],
            'returned', 're_delivered' => [
                ['label' => 'Mark Completed', 'url' => $route('completed'), 'status' => 'completed', 'variant' => 'success'],
            ],
            default => [],
        };
    }
}

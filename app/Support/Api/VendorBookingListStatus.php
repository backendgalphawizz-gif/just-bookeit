<?php

namespace App\Support\Api;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;

/**
 * Vendor list/detail display status (app + panel).
 * Item / lifecycle statuses stay unchanged — this is presentation + list filters only.
 *
 * - all active items pending acceptance → new
 * - all active items cancelled → cancelled
 * - all active items completed → complete
 * - otherwise (incl. returned / in transit / mixed) → pending
 */
class VendorBookingListStatus
{
    public const STATUS_NEW = 'new';

    public const STATUS_PENDING = 'pending';

    /** @deprecated Use STATUS_PENDING */
    public const STATUS_PROCESSING = 'pending';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @return array{status: string, status_label: string}
     */
    public static function resolve(Order $order): array
    {
        $order->loadMissing('orderItems');
        $items = $order->orderItems;

        if ($items->isEmpty()) {
            return self::resolveFromBookingStatus($order->status);
        }

        $active = $items->where('status', '!=', OrderItem::STATUS_CANCELLED)->values();

        if ($active->isEmpty()) {
            return [
                'status' => self::STATUS_CANCELLED,
                'status_label' => 'Cancelled',
            ];
        }

        // All still awaiting accept → new.
        if ($active->every(fn (OrderItem $item) => $item->status === OrderItem::STATUS_PENDING)) {
            return [
                'status' => self::STATUS_NEW,
                'status_label' => 'New',
            ];
        }

        // Complete only when every active item is fully completed.
        if ($active->every(fn (OrderItem $item) => $item->status === 'completed')) {
            return [
                'status' => self::STATUS_COMPLETE,
                'status_label' => 'Complete',
            ];
        }

        // Returned / rental active / in transit / mixed → still pending.
        return [
            'status' => self::STATUS_PENDING,
            'status_label' => 'Pending',
        ];
    }

    public static function labelFor(string $listStatus): string
    {
        return match ($listStatus) {
            self::STATUS_NEW => 'New',
            self::STATUS_PENDING, 'processing' => 'Pending',
            self::STATUS_COMPLETE => 'Complete',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($listStatus),
        };
    }

    /**
     * Apply vendor list tab/status filter (item-based buckets).
     */
    public static function applyTabFilter(Builder $query, string $tab): bool
    {
        $key = strtolower(trim(str_replace('_', '-', $tab)));

        return match ($key) {
            'new' => tap(true, fn () => self::scopeNew($query)),
            'pending',
            'processing',
            'accepted',
            'in-progress',
            'in-transit',
            'delivered',
            'rental-active',
            'rework',
            're-in-transit',
            'return-in-transit',
            'returned',
            're-delivered' => tap(true, fn () => self::scopePending($query)),
            'complete',
            'completed',
            'booking-completed',
            'fully-completed' => tap(true, fn () => self::scopeComplete($query)),
            'cancelled',
            'rejected' => tap(true, fn () => self::scopeCancelled($query)),
            default => false,
        };
    }

    /** @return array{status: string, status_label: string} */
    protected static function resolveFromBookingStatus(string $status): array
    {
        if (in_array($status, ['new', 'pending_acceptance'], true)) {
            return [
                'status' => self::STATUS_NEW,
                'status_label' => 'New',
            ];
        }

        if ($status === 'completed') {
            return [
                'status' => self::STATUS_COMPLETE,
                'status_label' => 'Complete',
            ];
        }

        if (in_array($status, ['cancelled', 'refunded'], true)) {
            return [
                'status' => self::STATUS_CANCELLED,
                'status_label' => 'Cancelled',
            ];
        }

        return [
            'status' => self::STATUS_PENDING,
            'status_label' => 'Pending',
        ];
    }

    protected static function scopeNew(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->where(function (Builder $withItems) {
                $withItems->whereHas('orderItems')
                    ->whereDoesntHave('orderItems', function (Builder $items) {
                        $items->where('status', '!=', OrderItem::STATUS_CANCELLED)
                            ->where('status', '!=', OrderItem::STATUS_PENDING);
                    })
                    ->whereHas('orderItems', fn (Builder $items) => $items->where('status', OrderItem::STATUS_PENDING));
            })->orWhere(function (Builder $legacy) {
                $legacy->whereDoesntHave('orderItems')
                    ->whereIn('status', ['new', 'pending_acceptance']);
            });
        });
    }

    protected static function scopePending(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->where(function (Builder $withItems) {
                $withItems->whereHas('orderItems')
                    // Has at least one active (non-cancelled) item.
                    ->whereHas('orderItems', fn (Builder $items) => $items->where('status', '!=', OrderItem::STATUS_CANCELLED))
                    // Not entirely still pending-acceptance.
                    ->whereHas('orderItems', function (Builder $items) {
                        $items->whereNotIn('status', [
                            OrderItem::STATUS_PENDING,
                            OrderItem::STATUS_CANCELLED,
                        ]);
                    })
                    // Not entirely completed yet.
                    ->whereHas('orderItems', function (Builder $items) {
                        $items->where('status', '!=', OrderItem::STATUS_CANCELLED)
                            ->where('status', '!=', 'completed');
                    });
            })->orWhere(function (Builder $legacy) {
                $legacy->whereDoesntHave('orderItems')
                    ->whereNotIn('status', [
                        'new',
                        'pending_acceptance',
                        'completed',
                        'cancelled',
                        'refunded',
                    ]);
            });
        });
    }

    /** @deprecated Use scopePending */
    protected static function scopeProcessing(Builder $query): void
    {
        self::scopePending($query);
    }

    protected static function scopeComplete(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->where(function (Builder $withItems) {
                $withItems->whereHas('orderItems')
                    ->whereDoesntHave('orderItems', function (Builder $items) {
                        $items->where('status', '!=', OrderItem::STATUS_CANCELLED)
                            ->where('status', '!=', 'completed');
                    })
                    ->whereHas('orderItems', fn (Builder $items) => $items->where('status', 'completed'));
            })->orWhere(function (Builder $legacy) {
                $legacy->whereDoesntHave('orderItems')
                    ->where('status', 'completed');
            });
        });
    }

    protected static function scopeCancelled(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->whereIn('status', ['cancelled', 'refunded'])
                ->orWhere(function (Builder $allCancelled) {
                    $allCancelled->whereHas('orderItems')
                        ->whereDoesntHave('orderItems', function (Builder $items) {
                            $items->where('status', '!=', OrderItem::STATUS_CANCELLED);
                        });
                });
        });
    }
}

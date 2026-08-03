<?php

namespace App\Support\Api;

use App\Models\Driver;
use App\Models\DriverDeliverySkip;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class DriverDeliveryTab
{
    public const TAB_NEW = 'new';

    public const TAB_IN_PROGRESS = 'in_progress';

    public const TAB_ACCEPTED = 'accepted';

    public const TAB_PICKUP = 'pickup';

    public const TAB_DISPATCHED = 'dispatched';

    public const TAB_DELIVERED = 'delivered';

    public const TAB_CANCELLED = 'cancelled';

    public const TAB_RESCHEDULED = 'rescheduled';

    /** @return list<string> */
    public static function tabs(): array
    {
        return [
            self::TAB_NEW,
            self::TAB_IN_PROGRESS,
            self::TAB_ACCEPTED,
            self::TAB_PICKUP,
            self::TAB_DISPATCHED,
            self::TAB_DELIVERED,
            self::TAB_CANCELLED,
            self::TAB_RESCHEDULED,
        ];
    }

    public static function validationRule(): array
    {
        return ['nullable', 'string', Rule::in(self::tabs())];
    }

    /** @return list<string> */
    public static function activeDeliveryStatuses(): array
    {
        return ['in_progress', 're_intransit'];
    }

    /**
     * Booking is dispatch-ready when the order itself is In Transit / Return In Transit,
     * or when any line item is (item-wise flow while booking rollup may still be pending).
     */
    public static function whereDispatchReady(Builder $query): Builder
    {
        $statuses = self::activeDeliveryStatuses();

        return $query->where(function (Builder $builder) use ($statuses) {
            $builder->whereIn('status', $statuses)
                ->orWhereHas('orderItems', fn (Builder $items) => $items->whereIn('status', $statuses));
        });
    }

    /** Assigned at booking level or on any in-transit line item. */
    public static function whereAssignedToDriver(Builder $query, Driver $driver): Builder
    {
        return $query->where(function (Builder $builder) use ($driver) {
            $builder->where('driver_id', $driver->id)
                ->orWhereHas('orderItems', fn (Builder $items) => $items->where('driver_id', $driver->id));
        });
    }

    public static function applyToQuery(Builder $query, Driver $driver, ?string $tab): Builder
    {
        $dispatchStatuses = self::activeDeliveryStatuses();
        $normalizedTab = self::normalizeTab($tab);

        return match ($normalizedTab) {
            self::TAB_NEW => self::whereDispatchReady($query)
                ->where(function (Builder $builder) use ($driver) {
                    $skipped = DriverDeliverySkip::query()
                        ->where('driver_id', $driver->id)
                        ->select('order_id');

                    $builder
                        ->where(function (Builder $pool) use ($skipped) {
                            $pool->whereNull('driver_id')
                                ->whereNotIn('id', $skipped)
                                ->whereDoesntHave('orderItems', fn (Builder $items) => $items
                                    ->whereIn('status', self::activeDeliveryStatuses())
                                    ->whereNotNull('driver_id'));
                        })
                        ->orWhere(function (Builder $assigned) use ($driver) {
                            $assigned->whereNull('driver_delivery_status')
                                ->where(function (Builder $owned) use ($driver) {
                                    $owned->where('driver_id', $driver->id)
                                        ->orWhereHas('orderItems', fn (Builder $items) => $items
                                            ->where('driver_id', $driver->id)
                                            ->whereIn('status', self::activeDeliveryStatuses()));
                                });
                        });
                }),
            self::TAB_IN_PROGRESS => self::whereAssignedToDriver($query, $driver)
                ->where(function (Builder $builder) {
                    $builder->where('status', 'in_progress')
                        ->orWhereHas('orderItems', fn (Builder $items) => $items->where('status', 'in_progress'));
                })
                ->whereNull('driver_delivery_status'),
            self::TAB_ACCEPTED => self::whereAssignedToDriver(self::whereDispatchReady($query), $driver)
                ->where('driver_delivery_status', Order::DRIVER_STATUS_ACCEPTED),
            self::TAB_PICKUP => self::whereAssignedToDriver(self::whereDispatchReady($query), $driver)
                ->where('driver_delivery_status', Order::DRIVER_STATUS_PICKED_UP),
            self::TAB_DISPATCHED => self::whereAssignedToDriver(self::whereDispatchReady($query), $driver)
                ->where('driver_delivery_status', Order::DRIVER_STATUS_OUT_FOR_DELIVERY),
            self::TAB_DELIVERED => self::whereAssignedToDriver($query, $driver)
                ->where(function (Builder $builder) use ($driver) {
                    $lifecycleDone = ['delivered', 'returned', 're_delivered', 'completed', 'rental_active'];

                    // Driver-only completion: prefer explicit driver signals so Vendor/User/Admin
                    // lifecycle status can stay In Transit while this tab still populates.
                    $builder->where(function (Builder $byDriverSignal) use ($driver) {
                        $byDriverSignal
                            ->where('driver_delivery_status', Order::DRIVER_STATUS_DELIVERED)
                            ->orWhere(function (Builder $completed) use ($driver) {
                                $completed->whereNotNull('driver_delivered_at')
                                    ->where('driver_id', $driver->id);
                            })
                            ->orWhereHas('orderItems', fn (Builder $items) => $items
                                ->where('driver_id', $driver->id)
                                ->where('driver_delivery_status', Order::DRIVER_STATUS_DELIVERED));
                    })->orWhere(function (Builder $legacy) use ($driver, $lifecycleDone) {
                        // Pre-change rows where lifecycle already advanced on deliver().
                        $legacy->where(function (Builder $bookingDone) use ($lifecycleDone) {
                            $bookingDone->whereIn('status', $lifecycleDone)
                                ->whereDoesntHave('orderItems', fn (Builder $items) => $items->whereNotNull('driver_id'));
                        })->orWhere(function (Builder $itemWise) use ($driver, $lifecycleDone) {
                            $itemWise->whereHas('orderItems', fn (Builder $items) => $items
                                ->where('driver_id', $driver->id)
                                ->whereIn('status', $lifecycleDone))
                                ->whereDoesntHave('orderItems', fn (Builder $items) => $items
                                    ->where('driver_id', $driver->id)
                                    ->whereNotIn('status', [...$lifecycleDone, 'cancelled'])
                                    ->where(function (Builder $open) {
                                        $open->whereNull('driver_delivery_status')
                                            ->orWhereNotIn('driver_delivery_status', [
                                                Order::DRIVER_STATUS_DELIVERED,
                                            ]);
                                    }));
                        });
                    })->whereDoesntHave('orderItems', fn (Builder $items) => $items
                        ->where('driver_id', $driver->id)
                        ->whereIn('driver_delivery_status', Order::driverActiveDeliveryStatuses()));
                }),
            self::TAB_CANCELLED => self::whereAssignedToDriver($query, $driver)
                ->where(function (Builder $builder) {
                    $builder->where('status', 'cancelled')
                        ->orWhereHas('orderItems', fn (Builder $items) => $items->where('status', 'cancelled'));
                }),
            self::TAB_RESCHEDULED => self::whereAssignedToDriver(self::whereDispatchReady($query), $driver)
                ->where('driver_delivery_status', Order::DRIVER_STATUS_RESCHEDULED),
            default => $query->where(function (Builder $builder) use ($driver, $dispatchStatuses) {
                $skipped = DriverDeliverySkip::query()
                    ->where('driver_id', $driver->id)
                    ->select('order_id');

                $builder->where(function (Builder $available) use ($driver, $dispatchStatuses, $skipped) {
                    $available->where(function (Builder $ready) use ($dispatchStatuses) {
                        $ready->whereIn('status', $dispatchStatuses)
                            ->orWhereHas('orderItems', fn (Builder $items) => $items->whereIn('status', $dispatchStatuses));
                    })->where(function (Builder $open) use ($driver, $skipped) {
                        $open->whereNull('driver_id')
                            ->whereNotIn('id', $skipped)
                            ->orWhere(function (Builder $assigned) use ($driver) {
                                $assigned->where('driver_id', $driver->id)
                                    ->whereNull('driver_delivery_status');
                            })
                            ->orWhereHas('orderItems', fn (Builder $items) => $items
                                ->where('driver_id', $driver->id)
                                ->whereIn('status', self::activeDeliveryStatuses()));
                    });
                })->orWhere(function (Builder $owned) use ($driver, $dispatchStatuses) {
                    $owned->where(function (Builder $assigned) use ($driver) {
                        $assigned->where('driver_id', $driver->id)
                            ->orWhereHas('orderItems', fn (Builder $items) => $items->where('driver_id', $driver->id));
                    })->where(function (Builder $ready) use ($dispatchStatuses) {
                        $ready->whereIn('status', $dispatchStatuses)
                            ->orWhereHas('orderItems', fn (Builder $items) => $items->whereIn('status', $dispatchStatuses));
                    })->whereNotNull('driver_delivery_status');
                })->orWhere(function (Builder $completed) use ($driver) {
                    $completed->where(function (Builder $assigned) use ($driver) {
                        $assigned->where('driver_id', $driver->id)
                            ->orWhereHas('orderItems', fn (Builder $items) => $items->where('driver_id', $driver->id));
                    })->where(function (Builder $done) {
                        $done->whereIn('status', ['delivered', 're_delivered', 'cancelled'])
                            ->orWhereHas('orderItems', fn (Builder $items) => $items
                                ->whereIn('status', ['delivered', 're_delivered', 'cancelled']));
                    });
                });
            }),
        };
    }

    protected static function normalizeTab(?string $tab): ?string
    {
        if ($tab === null || trim($tab) === '') {
            return null;
        }

        $key = strtolower(str_replace('_', '-', trim($tab)));

        return match ($key) {
            'new' => self::TAB_NEW,
            'in-progress', 'in_progress' => self::TAB_IN_PROGRESS,
            'accepted' => self::TAB_ACCEPTED,
            'pickup' => self::TAB_PICKUP,
            'dispatched', 'out-for-delivery', 'out_for_delivery' => self::TAB_DISPATCHED,
            'delivered', 'completed' => self::TAB_DELIVERED,
            'cancelled', 'canceled' => self::TAB_CANCELLED,
            'rescheduled' => self::TAB_RESCHEDULED,
            default => $key,
        };
    }
}

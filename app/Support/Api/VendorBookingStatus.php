<?php

namespace App\Support\Api;

use App\Models\Order;

class VendorBookingStatus
{
    /** @return list<string> */
    public static function acceptedInputStatuses(): array
    {
        return array_values(array_unique([
            ...Order::STATUSES,
            'completed',
            're-intransit',
            're-delivered',
            'in_transit',
        ]));
    }

    public static function normalizeInput(string $status): string
    {
        $key = strtolower(trim(str_replace('_', '-', $status)));

        return match ($key) {
            'completed' => 'delivered',
            're-intransit' => 're_intransit',
            're-delivered' => 're_delivered',
            'in-transit' => 'in_progress',
            default => str_replace('-', '_', $key),
        };
    }

    public static function toApi(string $status): string
    {
        return match ($status) {
            'delivered' => 'completed',
            're_intransit' => 're-intransit',
            're_delivered' => 're-delivered',
            default => $status,
        };
    }

    /** @return list<string>|null */
    public static function statusesForTab(string $tab): ?array
    {
        $key = strtolower(trim(str_replace('_', '-', $tab)));

        return match ($key) {
            'accepted' => ['accepted'],
            'in-progress', 'in_progress' => ['in_progress'],
            'completed' => ['delivered'],
            'cancelled' => ['cancelled'],
            'returned' => ['returned'],
            'rework' => ['rework'],
            're-in-transit', 're_intransit' => ['re_intransit'],
            're-delivered', 're_delivered' => ['re_delivered'],
            'new' => ['new', 'pending_acceptance'],
            default => null,
        };
    }
}

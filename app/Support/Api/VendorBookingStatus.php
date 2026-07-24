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
            'rejected',
            'in-transit',
            'in_transit',
            're-intransit',
            're-delivered',
            'rental-active',
            'return-in-transit',
            'return_in_transit',
        ]));
    }

    public static function normalizeInput(string $status): string
    {
        $key = strtolower(trim(str_replace('_', '-', $status)));

        return match ($key) {
            'rejected' => 'cancelled',
            're-intransit', 'return-in-transit' => 're_intransit',
            're-delivered' => 're_delivered',
            'in-transit' => 'in_progress',
            'rental-active' => 'rental_active',
            default => str_replace('-', '_', $key),
        };
    }

    public static function toApi(string $status): string
    {
        return match ($status) {
            'cancelled' => 'rejected',
            'in_progress' => 'in_transit',
            're_intransit' => 'return_in_transit',
            default => $status,
        };
    }

    /** @return list<string>|null */
    public static function statusesForTab(string $tab): ?array
    {
        $key = strtolower(trim(str_replace('_', '-', $tab)));

        // List tabs (new / pending / complete) are handled by VendorBookingListStatus::applyTabFilter.
        // Legacy status keys kept for older clients that still filter by lifecycle status.
        return match ($key) {
            'accepted' => ['accepted'],
            'in-progress', 'in-transit' => ['in_progress'],
            'delivered' => ['delivered'],
            'completed' => ['completed'],
            'rejected', 'cancelled' => ['cancelled'],
            'returned' => ['returned'],
            'rework' => ['rework'],
            'rental-active' => ['rental_active'],
            're-in-transit', 'return-in-transit' => ['re_intransit'],
            're-delivered' => ['re_delivered'],
            'new' => ['new', 'pending_acceptance'],
            'pending' => null, // use VendorBookingListStatus
            'complete' => null, // use VendorBookingListStatus
            'booking-completed', 'fully-completed' => ['completed'],
            default => null,
        };
    }
}

<?php

namespace App\Support;

class AdminAccountStatus
{
    /** @var array<string, string> */
    protected const LABELS = [
        'active' => 'Active',
        'inactive' => 'Blocked',
        'pending' => 'Pending',
        'rejected' => 'Rejected',
    ];

    public static function labelFor(?string $status): string
    {
        if (! $status) {
            return '—';
        }

        return self::LABELS[$status] ?? str_replace('_', ' ', ucfirst($status));
    }

    /** @return list<string> */
    public static function filterOptionsForCustomer(): array
    {
        return ['active', 'inactive'];
    }

    /** @return list<string> */
    public static function filterOptionsForVendorOrDriver(): array
    {
        return ['pending', 'active', 'inactive', 'rejected'];
    }
}

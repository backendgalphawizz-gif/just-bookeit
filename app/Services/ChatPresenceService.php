<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ChatPresenceService
{
    /** Seconds without a heartbeat before the user is considered offline. */
    public const TTL_SECONDS = 60;

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_VENDOR = 'vendor';

    public function touch(string $role, int $id): void
    {
        if ($id <= 0 || ! $this->isValidRole($role)) {
            return;
        }

        Cache::put($this->key($role, $id), now()->getTimestamp(), self::TTL_SECONDS);
    }

    public function leave(string $role, int $id): void
    {
        if ($id <= 0 || ! $this->isValidRole($role)) {
            return;
        }

        Cache::forget($this->key($role, $id));
    }

    public function isOnline(string $role, int $id): bool
    {
        if ($id <= 0 || ! $this->isValidRole($role)) {
            return false;
        }

        return Cache::has($this->key($role, $id));
    }

    public function status(string $role, int $id): string
    {
        return $this->isOnline($role, $id) ? 'online' : 'offline';
    }

    public function customerOnline(int $customerId): bool
    {
        return $this->isOnline(self::ROLE_CUSTOMER, $customerId);
    }

    public function vendorOnline(int $vendorId): bool
    {
        return $this->isOnline(self::ROLE_VENDOR, $vendorId);
    }

    protected function key(string $role, int $id): string
    {
        return "chat.presence.{$role}.{$id}";
    }

    protected function isValidRole(string $role): bool
    {
        return in_array($role, [self::ROLE_CUSTOMER, self::ROLE_VENDOR], true);
    }
}

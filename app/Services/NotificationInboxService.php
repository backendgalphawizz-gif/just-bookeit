<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\NotificationRead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class NotificationInboxService
{
    public const TYPE_CUSTOMER = NotificationRead::TYPE_CUSTOMER;

    public const TYPE_VENDOR = NotificationRead::TYPE_VENDOR;

    public const TYPE_DRIVER = NotificationRead::TYPE_DRIVER;

    /** @return list<string> */
    public function audiencesFor(string $recipientType): array
    {
        return match ($recipientType) {
            self::TYPE_CUSTOMER => ['all_customers', 'customers'],
            self::TYPE_VENDOR => ['all_vendors', 'vendors'],
            self::TYPE_DRIVER => ['all_drivers', 'drivers'],
            default => [],
        };
    }

    public function baseQuery(string $recipientType, ?int $recipientId = null): Builder
    {
        $query = NotificationLog::query()
            ->where('status', 'sent')
            ->whereIn('audience', $this->audiencesFor($recipientType))
            ->orderByDesc('sent_at')
            ->orderByDesc('id');

        // Targeted vendor alerts (e.g. product rejection) must only appear for that vendor.
        // Broadcast rows keep target_vendor_id null and remain visible to everyone.
        if ($recipientType === self::TYPE_VENDOR && $recipientId !== null) {
            $query->where(function (Builder $scoped) use ($recipientId): void {
                $scoped
                    ->whereNull('target_vendor_id')
                    ->orWhere('target_vendor_id', $recipientId);
            });
        } elseif ($recipientType !== self::TYPE_VENDOR) {
            $query->whereNull('target_vendor_id');
        }

        return $query;
    }

    public function paginate(string $recipientType, int $recipientId, int $perPage = 15, ?string $filter = null): LengthAwarePaginator
    {
        $query = $this->baseQuery($recipientType, $recipientId)
            ->with(['reads' => fn ($readQuery) => $readQuery
                ->where('recipient_type', $recipientType)
                ->where('recipient_id', $recipientId)]);

        if ($filter === 'read') {
            $query->whereHas('reads', fn (Builder $readQuery) => $readQuery
                ->where('recipient_type', $recipientType)
                ->where('recipient_id', $recipientId)
                ->whereNotNull('read_at'));
        } elseif ($filter === 'unread') {
            $query->where(function (Builder $builder) use ($recipientType, $recipientId) {
                $builder->whereDoesntHave('reads', fn (Builder $readQuery) => $readQuery
                    ->where('recipient_type', $recipientType)
                    ->where('recipient_id', $recipientId))
                    ->orWhereHas('reads', fn (Builder $readQuery) => $readQuery
                        ->where('recipient_type', $recipientType)
                        ->where('recipient_id', $recipientId)
                        ->whereNull('read_at'));
            });
        }

        return $query->paginate($perPage);
    }

    public function totalCount(string $recipientType, ?int $recipientId = null): int
    {
        return $this->baseQuery($recipientType, $recipientId)->count();
    }

    public function unreadCount(string $recipientType, int $recipientId): int
    {
        return $this->baseQuery($recipientType, $recipientId)
            ->where(function (Builder $query) use ($recipientType, $recipientId) {
                $query->whereDoesntHave('reads', fn (Builder $readQuery) => $readQuery
                    ->where('recipient_type', $recipientType)
                    ->where('recipient_id', $recipientId))
                    ->orWhereHas('reads', fn (Builder $readQuery) => $readQuery
                        ->where('recipient_type', $recipientType)
                        ->where('recipient_id', $recipientId)
                        ->whereNull('read_at'));
            })
            ->count();
    }

    public function readCount(string $recipientType, int $recipientId): int
    {
        return $this->baseQuery($recipientType, $recipientId)
            ->whereHas('reads', fn (Builder $readQuery) => $readQuery
                ->where('recipient_type', $recipientType)
                ->where('recipient_id', $recipientId)
                ->whereNotNull('read_at'))
            ->count();
    }

    public function markRead(NotificationLog $notification, string $recipientType, int $recipientId): NotificationRead
    {
        $this->assertAudience($notification, $recipientType, $recipientId);

        return NotificationRead::query()->updateOrCreate(
            [
                'notification_log_id' => $notification->id,
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
            ],
            ['read_at' => now()]
        );
    }

    public function markUnread(NotificationLog $notification, string $recipientType, int $recipientId): NotificationRead
    {
        $this->assertAudience($notification, $recipientType, $recipientId);

        return NotificationRead::query()->updateOrCreate(
            [
                'notification_log_id' => $notification->id,
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
            ],
            ['read_at' => null]
        );
    }

    public function markAllRead(string $recipientType, int $recipientId): int
    {
        $marked = 0;

        $this->baseQuery($recipientType, $recipientId)
            ->pluck('id')
            ->each(function (int $notificationId) use ($recipientType, $recipientId, &$marked) {
                NotificationRead::query()->updateOrCreate(
                    [
                        'notification_log_id' => $notificationId,
                        'recipient_type' => $recipientType,
                        'recipient_id' => $recipientId,
                    ],
                    ['read_at' => now()]
                );
                $marked++;
            });

        return $marked;
    }

    public function readStateFor(NotificationLog $notification, string $recipientType, int $recipientId): array
    {
        $read = $notification->relationLoaded('reads')
            ? $notification->reads->first()
            : $notification->reads()
                ->where('recipient_type', $recipientType)
                ->where('recipient_id', $recipientId)
                ->first();

        return [
            'is_read' => $read !== null && $read->read_at !== null,
            'read_at' => $read?->read_at?->format('M d, Y, g:i A'),
            'read_at_iso' => $read?->read_at?->toIso8601String(),
        ];
    }

    protected function assertAudience(NotificationLog $notification, string $recipientType, ?int $recipientId = null): void
    {
        abort_unless(
            in_array($notification->audience, $this->audiencesFor($recipientType), true),
            404
        );

        if (
            $recipientType === self::TYPE_VENDOR
            && $notification->target_vendor_id
            && $recipientId !== null
            && (int) $notification->target_vendor_id !== (int) $recipientId
        ) {
            abort(404);
        }
    }
}

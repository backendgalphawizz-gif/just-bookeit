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

    /** @return list<string> */
    public function audiencesFor(string $recipientType): array
    {
        return match ($recipientType) {
            self::TYPE_CUSTOMER => ['all_customers', 'customers'],
            self::TYPE_VENDOR => ['all_vendors', 'vendors'],
            default => [],
        };
    }

    public function baseQuery(string $recipientType): Builder
    {
        return NotificationLog::query()
            ->where('status', 'sent')
            ->whereIn('audience', $this->audiencesFor($recipientType))
            ->orderByDesc('sent_at')
            ->orderByDesc('id');
    }

    public function paginate(string $recipientType, int $recipientId, int $perPage = 15, ?string $filter = null): LengthAwarePaginator
    {
        $query = $this->baseQuery($recipientType)
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

    public function totalCount(string $recipientType): int
    {
        return $this->baseQuery($recipientType)->count();
    }

    public function unreadCount(string $recipientType, int $recipientId): int
    {
        return $this->baseQuery($recipientType)
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
        return $this->baseQuery($recipientType)
            ->whereHas('reads', fn (Builder $readQuery) => $readQuery
                ->where('recipient_type', $recipientType)
                ->where('recipient_id', $recipientId)
                ->whereNotNull('read_at'))
            ->count();
    }

    public function markRead(NotificationLog $notification, string $recipientType, int $recipientId): NotificationRead
    {
        $this->assertAudience($notification, $recipientType);

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
        $this->assertAudience($notification, $recipientType);

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

        $this->baseQuery($recipientType)
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

    protected function assertAudience(NotificationLog $notification, string $recipientType): void
    {
        abort_unless(
            in_array($notification->audience, $this->audiencesFor($recipientType), true),
            404
        );
    }
}

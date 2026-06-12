<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\NotificationLog;
use App\Services\NotificationInboxService;
use App\Support\Api\CustomerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait HandlesNotificationInbox
{
    abstract protected function notificationRecipientType(): string;

    abstract protected function notificationRecipientId(Request $request): int;

    protected function inbox(): NotificationInboxService
    {
        return app(NotificationInboxService::class);
    }

    public function notificationIndex(Request $request): JsonResponse
    {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'filter' => ['nullable', 'string', Rule::in(['all', 'read', 'unread'])],
        ]);

        $recipientType = $this->notificationRecipientType();
        $recipientId = $this->notificationRecipientId($request);
        $filter = $request->string('filter', 'all')->toString();
        $filter = $filter === 'all' ? null : $filter;

        $notifications = $this->inbox()->paginate(
            $recipientType,
            $recipientId,
            $request->integer('per_page', 15),
            $filter
        );

        return $this->success([
            ...CustomerApiPresenter::paginator(
                $notifications,
                fn (NotificationLog $log) => CustomerApiPresenter::notification(
                    $log,
                    $recipientType,
                    $recipientId
                )
            ),
            'summary' => [
                'total_count' => $this->inbox()->totalCount($recipientType),
                'unread_count' => $this->inbox()->unreadCount($recipientType, $recipientId),
                'read_count' => $this->inbox()->readCount($recipientType, $recipientId),
            ],
        ]);
    }

    public function notificationMarkRead(Request $request, NotificationLog $notification): JsonResponse
    {
        $recipientType = $this->notificationRecipientType();
        $recipientId = $this->notificationRecipientId($request);

        $this->inbox()->markRead($notification, $recipientType, $recipientId);

        return $this->success([
            'notification' => CustomerApiPresenter::notification($notification->fresh(['reads']), $recipientType, $recipientId),
            'summary' => [
                'unread_count' => $this->inbox()->unreadCount($recipientType, $recipientId),
            ],
        ], 'Notification marked as read.');
    }

    public function notificationMarkUnread(Request $request, NotificationLog $notification): JsonResponse
    {
        $recipientType = $this->notificationRecipientType();
        $recipientId = $this->notificationRecipientId($request);

        $this->inbox()->markUnread($notification, $recipientType, $recipientId);

        return $this->success([
            'notification' => CustomerApiPresenter::notification($notification->fresh(['reads']), $recipientType, $recipientId),
            'summary' => [
                'unread_count' => $this->inbox()->unreadCount($recipientType, $recipientId),
            ],
        ], 'Notification marked as unread.');
    }

    public function notificationMarkAllRead(Request $request): JsonResponse
    {
        $recipientType = $this->notificationRecipientType();
        $recipientId = $this->notificationRecipientId($request);

        $marked = $this->inbox()->markAllRead($recipientType, $recipientId);

        return $this->success([
            'marked_count' => $marked,
            'summary' => [
                'unread_count' => $this->inbox()->unreadCount($recipientType, $recipientId),
            ],
        ], 'All notifications marked as read.');
    }

    public function index(Request $request): JsonResponse
    {
        return $this->notificationIndex($request);
    }

    public function markRead(Request $request, NotificationLog $notification): JsonResponse
    {
        return $this->notificationMarkRead($request, $notification);
    }

    public function markUnread(Request $request, NotificationLog $notification): JsonResponse
    {
        return $this->notificationMarkUnread($request, $notification);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        return $this->notificationMarkAllRead($request);
    }
}

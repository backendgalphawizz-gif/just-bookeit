<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminInboxNotification;
use App\Services\Admin\AdminInboxNotificationService;
use App\Support\AdminDateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InboxNotificationController extends AdminController
{
    public function __construct(
        protected AdminInboxNotificationService $inbox
    ) {}

    public function index(): View
    {
        $notifications = $this->inbox
            ->scopedQuery(auth('admin')->user())
            ->paginate(20);

        return view('admin.inbox-notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $notifications->total(),
        ]);
    }

    public function feed(): JsonResponse
    {
        $admin = auth('admin')->user();
        $notifications = $this->inbox->recent($admin, 8);

        return response()->json([
            'unread_count' => $this->inbox->unreadCount($admin),
            'notifications' => $notifications->map(fn (AdminInboxNotification $notification): array => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => Str::limit($notification->message, 90),
                'time' => AdminDateTime::format($notification->created_at, 'M d · h:i A'),
                'open_url' => route('admin.inbox-notifications.open', $notification),
                'read_url' => route('admin.inbox-notifications.read', $notification),
            ])->values(),
        ]);
    }

    public function read(Request $request, AdminInboxNotification $inboxNotification): RedirectResponse|JsonResponse
    {
        abort_unless($this->inbox->adminCanSee($inboxNotification, auth('admin')->user()), 403);

        $this->inbox->markRead($inboxNotification, auth('admin')->user());

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'unread_count' => $this->inbox->unreadCount(auth('admin')->user()),
            ]);
        }

        return back()->with('success', 'Notification cleared.');
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $cleared = $this->inbox->markAllRead(auth('admin')->user());

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'cleared' => $cleared,
                'unread_count' => 0,
                'notifications' => [],
            ]);
        }

        return back()->with('success', $cleared > 0 ? 'All notifications cleared.' : 'No unread notifications.');
    }

    public function open(AdminInboxNotification $inboxNotification): RedirectResponse
    {
        abort_unless($this->inbox->adminCanSee($inboxNotification, auth('admin')->user()), 403);

        $url = $inboxNotification->action_url;

        $this->inbox->markRead($inboxNotification, auth('admin')->user());

        return redirect()->to($url);
    }
}

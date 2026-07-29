<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminInboxNotification;
use App\Services\Admin\AdminInboxNotificationService;
use Illuminate\Http\RedirectResponse;
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

    public function read(AdminInboxNotification $inboxNotification): RedirectResponse
    {
        abort_unless($this->inbox->adminCanSee($inboxNotification, auth('admin')->user()), 403);

        $this->inbox->markRead($inboxNotification, auth('admin')->user());

        return back()->with('success', 'Notification cleared.');
    }

    public function readAll(): RedirectResponse
    {
        $cleared = $this->inbox->markAllRead(auth('admin')->user());

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

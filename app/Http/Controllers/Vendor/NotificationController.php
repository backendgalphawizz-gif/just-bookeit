<?php

namespace App\Http\Controllers\Vendor;

use App\Models\NotificationLog;
use App\Models\Vendor;
use App\Services\NotificationInboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends VendorController
{
    public function __construct(
        protected NotificationInboxService $inbox
    ) {}

    public function index(): View
    {
        $vendor = $this->authenticatedVendor();

        $notifications = $this->inbox->paginate(
            NotificationInboxService::TYPE_VENDOR,
            $vendor->id,
            20
        );

        return view('vendor.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $this->inbox->unreadCount(NotificationInboxService::TYPE_VENDOR, $vendor->id),
        ]);
    }

    public function markRead(NotificationLog $notification): RedirectResponse
    {
        $vendor = $this->authenticatedVendor();

        $this->inbox->markRead(
            $notification,
            NotificationInboxService::TYPE_VENDOR,
            $vendor->id
        );

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(): RedirectResponse
    {
        $vendor = $this->authenticatedVendor();

        $this->inbox->markAllRead(
            NotificationInboxService::TYPE_VENDOR,
            $vendor->id
        );

        return back()->with('success', 'All notifications marked as read.');
    }

    protected function authenticatedVendor(): Vendor
    {
        $vendor = Auth::guard('vendor')->user();
        abort_unless($vendor instanceof Vendor, 403);

        return $vendor;
    }

    /** @return array{is_read: bool, read_at: ?string} */
    public static function readState(NotificationLog $notification, Vendor $vendor): array
    {
        return app(NotificationInboxService::class)->readStateFor(
            $notification,
            NotificationInboxService::TYPE_VENDOR,
            $vendor->id
        );
    }
}

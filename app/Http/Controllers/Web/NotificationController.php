<?php

namespace App\Http\Controllers\Web;

use App\Models\Customer;
use App\Models\NotificationLog;
use App\Services\NotificationInboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends WebController
{
    public function __construct(
        protected NotificationInboxService $inbox
    ) {}

    public function index(): View
    {
        $customer = $this->registeredCustomer();

        $notifications = $this->inbox->paginate(
            NotificationInboxService::TYPE_CUSTOMER,
            $customer->id,
            20
        );

        return view('web.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $this->inbox->unreadCount(NotificationInboxService::TYPE_CUSTOMER, $customer->id),
        ]);
    }

    public function markRead(NotificationLog $notification): RedirectResponse
    {
        $customer = $this->registeredCustomer();

        $this->inbox->markRead(
            $notification,
            NotificationInboxService::TYPE_CUSTOMER,
            $customer->id
        );

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(): RedirectResponse
    {
        $customer = $this->registeredCustomer();

        $this->inbox->markAllRead(
            NotificationInboxService::TYPE_CUSTOMER,
            $customer->id
        );

        return back()->with('success', 'All notifications marked as read.');
    }

    protected function registeredCustomer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer && ! $customer->is_guest, 403);

        return $customer;
    }

    /** @return array{is_read: bool, read_at: ?string} */
    public static function readState(NotificationLog $notification, Customer $customer): array
    {
        return app(NotificationInboxService::class)->readStateFor(
            $notification,
            NotificationInboxService::TYPE_CUSTOMER,
            $customer->id
        );
    }
}

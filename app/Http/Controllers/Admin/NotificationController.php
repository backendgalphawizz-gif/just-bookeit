<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\NotificationLog;
use App\Models\Vendor;
use App\Services\PushNotificationService;
use App\Http\Requests\Admin\NotificationStoreRequest;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'notifications';

    public function __construct(
        protected PushNotificationService $pushNotifications
    ) {}

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $logs = $this->applyDateRange(NotificationLog::query(), $request)
            ->with('admin')
            ->newestFirst()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_sent' => NotificationLog::query()->where('status', 'sent')->count(),
            'this_month' => NotificationLog::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            'customers' => Customer::query()->where('status', 'active')->count(),
            'vendors' => Vendor::query()->where('status', 'active')->count(),
        ];

        return view('admin.notifications.index', compact('logs', 'stats'));
    }

    public function create(): View
    {
        return view('admin.notifications.create', [
            'customerCount' => Customer::query()->where('status', 'active')->count(),
            'vendorCount' => Vendor::query()->where('status', 'active')->count(),
            'driverCount' => Driver::query()->where('status', 'active')->count(),
        ]);
    }

    public function store(NotificationStoreRequest $request): RedirectResponse
    {
        $this->authorizeAdmin('create');

        $data = $request->validated();

        $recipients = match ($data['audience']) {
            'all_customers' => Customer::query()->where('status', 'active')->count(),
            'all_vendors' => Vendor::query()->where('status', 'active')->count(),
            'all_drivers' => Driver::query()->where('status', 'active')->count(),
            'customers' => Customer::query()->where('status', 'active')->count(),
            'vendors' => Vendor::query()->where('status', 'active')->count(),
            'drivers' => Driver::query()->where('status', 'active')->count(),
        };

        $notification = NotificationLog::query()->create([
            ...$data,
            'admin_id' => Auth::guard('admin')->id(),
            'status' => 'sent',
            'recipients_count' => $recipients,
            'sent_at' => now(),
        ]);

        $successMessage = match ($data['channel']) {
            'push' => $this->pushSuccessMessage($this->pushNotifications->dispatchNotificationLog($notification)),
            default => "Notification logged for {$recipients} recipients ({$data['channel']} delivery not yet integrated).",
        };

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', $successMessage);
    }

    public function show(NotificationLog $notification): View
    {
        $notification->load('admin');

        return view('admin.notifications.show', compact('notification'));
    }

    /** @param  array{sent: int, failed: int, tokens: int}  $result */
    protected function pushSuccessMessage(array $result): string
    {
        if ($result['tokens'] === 0) {
            return 'Push notification logged. No registered FCM tokens found for the selected audience.';
        }

        return "Push notification sent to {$result['sent']} of {$result['tokens']} device(s)."
            .($result['failed'] > 0 ? " {$result['failed']} failed." : '');
    }
}

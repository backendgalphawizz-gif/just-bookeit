<?php

namespace App\Services\Admin;

use App\Models\Admin;
use App\Models\AdminInboxNotification;
use App\Models\ContactMessage;
use App\Models\Dispute;
use App\Models\Driver;
use App\Models\PortfolioItem;
use App\Models\Refund;
use App\Models\SupportTicket;
use App\Models\Vendor;
use App\Models\VendorWithdrawalRequest;
use App\Support\AdminCityScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminInboxNotificationService
{
    public function notifyProductPendingApproval(PortfolioItem $product, bool $resubmitted = false): void
    {
        $product->loadMissing('vendor');

        if (! $product->vendor_id || $product->status !== PortfolioItem::PENDING_STATUS) {
            return;
        }

        $this->dismissForProduct($product);

        $vendorName = $product->vendor?->brand_name ?? 'Vendor';

        $this->push(
            type: $resubmitted
                ? AdminInboxNotification::TYPE_PRODUCT_RESUBMITTED
                : AdminInboxNotification::TYPE_PRODUCT_SUBMITTED,
            title: $resubmitted ? 'Product updated for approval' : 'New product submitted',
            message: sprintf('%s submitted "%s" for admin approval.', $vendorName, $product->title),
            actionUrl: route('admin.portfolio.show', $product),
            vendorId: $product->vendor_id,
            portfolioItemId: $product->id,
        );
    }

    public function notifyContactMessage(ContactMessage $message): void
    {
        $this->push(
            type: AdminInboxNotification::TYPE_CONTACT_MESSAGE,
            title: 'New contact message',
            message: sprintf('%s — %s', $message->email, $message->subject),
            actionUrl: route('admin.contact-messages.show', $message),
        );
    }

    public function notifySupportTicket(SupportTicket $ticket): void
    {
        $ticket->loadMissing('customer');

        $this->push(
            type: AdminInboxNotification::TYPE_SUPPORT_TICKET,
            title: 'New support ticket',
            message: sprintf(
                '%s — %s',
                $ticket->customer?->name ?? $ticket->email,
                $ticket->subject
            ),
            actionUrl: route('admin.dashboard'),
            metaKey: 'support_ticket_id',
            metaId: $ticket->id,
        );
    }

    public function notifyVendorPendingApproval(Vendor $vendor): void
    {
        if ($vendor->status !== 'pending') {
            return;
        }

        $this->dismissByTypeAndVendor(AdminInboxNotification::TYPE_VENDOR_PENDING, $vendor->id);

        $this->push(
            type: AdminInboxNotification::TYPE_VENDOR_PENDING,
            title: 'Vendor awaiting approval',
            message: sprintf('%s (%s) registered and needs approval.', $vendor->brand_name, $vendor->vendor_code),
            actionUrl: route('admin.vendors.show', $vendor),
            vendorId: $vendor->id,
        );
    }

    public function notifyDriverPendingApproval(Driver $driver): void
    {
        if ($driver->status !== 'pending') {
            return;
        }

        $this->push(
            type: AdminInboxNotification::TYPE_DRIVER_PENDING,
            title: 'Driver awaiting approval',
            message: sprintf('%s (%s) registered and needs approval.', $driver->name, $driver->driver_code),
            actionUrl: route('admin.drivers.show', $driver),
            metaKey: 'driver_id',
            metaId: $driver->id,
        );
    }

    public function notifyRefundRequested(Refund $refund): void
    {
        $refund->loadMissing(['customer', 'order.vendor']);

        $this->push(
            type: AdminInboxNotification::TYPE_REFUND_REQUESTED,
            title: 'Refund requested',
            message: sprintf(
                '%s requested ₹%s for order %s.',
                $refund->customer?->name ?? 'Customer',
                number_format((float) $refund->amount, 2),
                $refund->order?->order_number ?? '#'.$refund->order_id
            ),
            actionUrl: route('admin.refunds.show', $refund),
            vendorId: $refund->order?->vendor_id,
            metaKey: 'refund_id',
            metaId: $refund->id,
        );
    }

    public function notifyWithdrawalRequested(VendorWithdrawalRequest $withdrawal): void
    {
        $withdrawal->loadMissing('vendor');

        $this->push(
            type: AdminInboxNotification::TYPE_WITHDRAWAL_REQUESTED,
            title: 'Withdrawal requested',
            message: sprintf(
                '%s requested ₹%s (%s).',
                $withdrawal->vendor?->brand_name ?? 'Vendor',
                number_format((float) $withdrawal->amount, 2),
                $withdrawal->request_code
            ),
            actionUrl: route('admin.withdrawals.show', $withdrawal),
            vendorId: $withdrawal->vendor_id,
            metaKey: 'withdrawal_id',
            metaId: $withdrawal->id,
        );
    }

    public function notifyDisputeRaised(Dispute $dispute): void
    {
        $dispute->loadMissing(['order.vendor', 'order.customer']);

        $this->push(
            type: AdminInboxNotification::TYPE_DISPUTE_RAISED,
            title: 'New dispute raised',
            message: sprintf(
                '%s on order %s — %s',
                $dispute->order?->customer?->name ?? 'Customer',
                $dispute->order?->order_number ?? '#'.$dispute->order_id,
                $dispute->subject
            ),
            actionUrl: route('admin.disputes.show', $dispute),
            vendorId: $dispute->order?->vendor_id,
            metaKey: 'dispute_id',
            metaId: $dispute->id,
        );
    }

    public function dismissForProduct(PortfolioItem|int $product): void
    {
        $productId = $product instanceof PortfolioItem ? $product->id : $product;

        AdminInboxNotification::query()
            ->where('portfolio_item_id', $productId)
            ->delete();
    }

    public function dismissForVendor(Vendor|int $vendor): void
    {
        $vendorId = $vendor instanceof Vendor ? $vendor->id : $vendor;

        AdminInboxNotification::query()
            ->where('vendor_id', $vendorId)
            ->where('type', AdminInboxNotification::TYPE_VENDOR_PENDING)
            ->delete();
    }

    public function dismissForContactMessage(ContactMessage|int $message): void
    {
        $messageId = $message instanceof ContactMessage ? $message->id : $message;

        AdminInboxNotification::query()
            ->where('type', AdminInboxNotification::TYPE_CONTACT_MESSAGE)
            ->where('action_url', route('admin.contact-messages.show', $messageId))
            ->delete();
    }

    public function dismissForRefund(Refund|int $refund): void
    {
        $refundId = $refund instanceof Refund ? $refund->id : $refund;

        AdminInboxNotification::query()
            ->where('type', AdminInboxNotification::TYPE_REFUND_REQUESTED)
            ->where('action_url', route('admin.refunds.show', $refundId))
            ->delete();
    }

    public function dismissForWithdrawal(VendorWithdrawalRequest|int $withdrawal): void
    {
        $withdrawalId = $withdrawal instanceof VendorWithdrawalRequest ? $withdrawal->id : $withdrawal;

        AdminInboxNotification::query()
            ->where('type', AdminInboxNotification::TYPE_WITHDRAWAL_REQUESTED)
            ->where('action_url', route('admin.withdrawals.show', $withdrawalId))
            ->delete();
    }

    public function dismissForDispute(Dispute|int $dispute): void
    {
        $disputeId = $dispute instanceof Dispute ? $dispute->id : $dispute;

        AdminInboxNotification::query()
            ->where('type', AdminInboxNotification::TYPE_DISPUTE_RAISED)
            ->where('action_url', route('admin.disputes.show', $disputeId))
            ->delete();
    }

    public function dismissForDriver(Driver|int $driver): void
    {
        $driverId = $driver instanceof Driver ? $driver->id : $driver;

        AdminInboxNotification::query()
            ->where('type', AdminInboxNotification::TYPE_DRIVER_PENDING)
            ->where('action_url', route('admin.drivers.show', $driverId))
            ->delete();
    }

    /** @return Builder<AdminInboxNotification> */
    public function scopedQuery(?Admin $admin = null): Builder
    {
        $query = AdminInboxNotification::query()
            ->with(['vendor', 'portfolioItem'])
            ->unread()
            ->orderByDesc('created_at');

        if ($admin && ! AdminCityScope::isUnrestricted($admin)) {
            $query->where(function (Builder $scoped) use ($admin): void {
                $scoped
                    ->whereNull('vendor_id')
                    ->orWhereHas('vendor', fn (Builder $vendorQuery) => AdminCityScope::scopeVendors($vendorQuery, $admin));
            });
        }

        return $query;
    }

    public function unreadCount(?Admin $admin = null): int
    {
        return $this->scopedQuery($admin)->count();
    }

    /** @return Collection<int, AdminInboxNotification> */
    public function recent(?Admin $admin = null, int $limit = 8): Collection
    {
        return $this->scopedQuery($admin)->limit($limit)->get();
    }

    public function markRead(AdminInboxNotification $notification, ?Admin $admin = null): void
    {
        if (! $this->adminCanSee($notification, $admin)) {
            return;
        }

        $notification->delete();
    }

    public function markAllRead(?Admin $admin = null): int
    {
        $ids = $this->scopedQuery($admin)->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return AdminInboxNotification::query()->whereIn('id', $ids)->delete();
    }

    public function adminCanSee(AdminInboxNotification $notification, ?Admin $admin = null): bool
    {
        $admin ??= auth('admin')->user();

        if (! $admin || AdminCityScope::isUnrestricted($admin)) {
            return true;
        }

        if (! $notification->vendor_id) {
            return true;
        }

        $notification->loadMissing('vendor');

        return AdminCityScope::adminCanAccessCity($notification->vendor?->city, $admin);
    }

    protected function push(
        string $type,
        string $title,
        string $message,
        string $actionUrl,
        ?int $vendorId = null,
        ?int $portfolioItemId = null,
        ?string $metaKey = null,
        ?int $metaId = null,
    ): void {
        // Keep unique pending alerts for the same entity when possible.
        if ($portfolioItemId) {
            AdminInboxNotification::query()
                ->where('portfolio_item_id', $portfolioItemId)
                ->where('type', $type)
                ->delete();
        } elseif ($metaKey && $metaId) {
            AdminInboxNotification::query()
                ->where('type', $type)
                ->where('action_url', $actionUrl)
                ->delete();
        }

        AdminInboxNotification::query()->create([
            'type' => $type,
            'portfolio_item_id' => $portfolioItemId,
            'vendor_id' => $vendorId,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
        ]);
    }

    protected function dismissByTypeAndVendor(string $type, int $vendorId): void
    {
        AdminInboxNotification::query()
            ->where('type', $type)
            ->where('vendor_id', $vendorId)
            ->delete();
    }
}

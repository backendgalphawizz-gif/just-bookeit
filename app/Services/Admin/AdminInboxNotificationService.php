<?php

namespace App\Services\Admin;

use App\Models\Admin;
use App\Models\AdminInboxNotification;
use App\Models\PortfolioItem;
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
        $type = $resubmitted
            ? AdminInboxNotification::TYPE_PRODUCT_RESUBMITTED
            : AdminInboxNotification::TYPE_PRODUCT_SUBMITTED;

        AdminInboxNotification::query()->create([
            'type' => $type,
            'portfolio_item_id' => $product->id,
            'vendor_id' => $product->vendor_id,
            'title' => $resubmitted ? 'Product updated for approval' : 'New product submitted',
            'message' => sprintf(
                '%s submitted "%s" for admin approval.',
                $vendorName,
                $product->title
            ),
            'action_url' => route('admin.portfolio.show', $product),
        ]);
    }

    public function dismissForProduct(PortfolioItem|int $product): void
    {
        $productId = $product instanceof PortfolioItem ? $product->id : $product;

        AdminInboxNotification::query()
            ->where('portfolio_item_id', $productId)
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
            $query->whereHas('vendor', fn (Builder $vendorQuery) => AdminCityScope::scopeVendors($vendorQuery, $admin));
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

        $notification->loadMissing('vendor');

        return AdminCityScope::adminCanAccessCity($notification->vendor?->city, $admin);
    }
}

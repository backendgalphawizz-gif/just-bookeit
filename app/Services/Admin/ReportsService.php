<?php

namespace App\Services\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Vendor;
use App\Support\AdminCityScope;
use App\Support\AppliesListDateFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportsService
{
    use AppliesListDateFilter;

    protected const PER_PAGE = 15;

    public function __construct(
        protected DashboardService $dashboard
    ) {}

    public function summary(): array
    {
        return $this->dashboard->getStats();
    }

    public function ordersReport(Request $request): LengthAwarePaginator
    {
        return $this->ordersReportQuery($request)
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    public function ordersReportExport(Request $request): Collection
    {
        return $this->ordersReportQuery($request)->get();
    }

    protected function ordersReportQuery(Request $request): Builder
    {
        return AdminCityScope::scopeOrders(
            $this->applyDateRange(Order::query(), $request)
        )
            ->with(['customer', 'vendor', 'category'])
            ->when($request->filled('search'), fn (Builder $q) => $q->where('order_number', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
            ->when($request->filled('payment_status'), fn (Builder $q) => $q->where('payment_status', $request->string('payment_status')))
            ->when($request->filled('vendor_id'), fn (Builder $q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->orderByDesc('created_at');
    }

    public function vendorReport(Request $request): LengthAwarePaginator
    {
        return $this->vendorReportQuery($request)
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    public function vendorReportExport(Request $request): Collection
    {
        return $this->vendorReportQuery($request)->get();
    }

    protected function vendorReportQuery(Request $request): Builder
    {
        return AdminCityScope::scopeVendors(
            $this->applyDateRange(Vendor::query(), $request)
        )
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function (Builder $q) use ($term) {
                    $q->where('brand_name', 'like', $term)
                        ->orWhere('owner_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('vendor_code', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
            ->when($request->filled('city'), fn (Builder $q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
            ->orderByDesc('earnings');
    }

    public function refundReport(Request $request): LengthAwarePaginator
    {
        return $this->refundReportQuery($request)
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    public function refundReportExport(Request $request): Collection
    {
        return $this->refundReportQuery($request)->get();
    }

    protected function refundReportQuery(Request $request): Builder
    {
        $query = Refund::query();

        $admin = auth('admin')->user();
        if ($admin && ! AdminCityScope::isUnrestricted($admin)) {
            $query->whereHas('order', fn (Builder $orderQuery) => AdminCityScope::scopeOrders($orderQuery, $admin));
        }

        return $this->applyDateRange($query, $request)
            ->with(['customer', 'order.vendor'])
            ->when(
                $request->get('status') === '_open_' || $request->boolean('open_only'),
                fn (Builder $q) => $q->whereIn('status', Refund::OPEN_STATUSES)
            )
            ->when(
                $request->filled('status') && $request->get('status') !== '_open_',
                fn (Builder $q) => $q->where('status', $request->string('status'))
            )
            ->when($request->filled('vendor_id'), fn (Builder $q) => $q->whereHas(
                'order',
                fn (Builder $order) => $order->where('vendor_id', $request->integer('vendor_id'))
            ))
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->whereHas('customer', fn (Builder $c) => $c->where('name', 'like', $term));
            })
            ->orderByDesc('created_at');
    }

    public function customerGrowth(): int
    {
        return Customer::query()->where('created_at', '>=', now()->subDays(30))->count();
    }
}

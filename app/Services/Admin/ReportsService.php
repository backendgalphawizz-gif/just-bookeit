<?php

namespace App\Services\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportsService
{
    public function __construct(
        protected DashboardService $dashboard
    ) {}

    public function summary(): array
    {
        return $this->dashboard->getStats();
    }

    public function ordersReport(Request $request): Collection
    {
        return Order::query()
            ->with(['customer', 'vendor', 'category'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    public function vendorReport(): Collection
    {
        return Vendor::query()
            ->orderByDesc('earnings')
            ->limit(50)
            ->get();
    }

    public function refundReport(Request $request): Collection
    {
        return Refund::query()
            ->with(['customer', 'order'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    public function customerGrowth(): int
    {
        return Customer::query()->where('created_at', '>=', now()->subDays(30))->count();
    }
}

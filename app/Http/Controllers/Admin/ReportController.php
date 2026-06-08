<?php

namespace App\Http\Controllers\Admin;

use App\Models\Vendor;
use App\Services\Admin\DashboardService;
use App\Services\Admin\ReportsService;
use App\Support\AdminCityScope;
use App\Support\AppliesListDateFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends AdminController
{
    use AppliesListDateFilter;

    protected string $permissionModule = 'reports';

    public function __construct(
        protected ReportsService $reports,
        protected DashboardService $dashboard
    ) {}

    public function index(Request $request): View
    {
        $report = $request->string('report', 'overview')->toString();

        if (in_array($report, ['orders', 'refunds', 'vendors'], true)) {
            $this->validateListDateRange($request);
        }

        $filterVendors = AdminCityScope::scopeVendors(Vendor::query())
            ->active()
            ->orderBy('brand_name')
            ->get();

        return view('admin.reports.index', [
            'summary' => $this->reports->summary(),
            'charts' => $this->dashboard->getCharts(),
            'report' => $report,
            'filterVendors' => $filterVendors,
            'orders' => $report === 'orders' ? $this->reports->ordersReport($request) : null,
            'vendors' => $report === 'vendors' ? $this->reports->vendorReport($request) : null,
            'refunds' => $report === 'refunds' ? $this->reports->refundReport($request) : null,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeAdmin('export');

        $type = $request->string('type', 'orders')->toString();

        if (in_array($type, ['orders', 'refunds', 'vendors'], true)) {
            $this->validateListDateRange($request);
        }

        return response()->streamDownload(function () use ($type, $request) {
            $handle = fopen('php://output', 'w');

            if ($type === 'orders') {
                fputcsv($handle, ['Order', 'Customer', 'Vendor', 'Amount', 'Status', 'Payment', 'Date']);
                foreach ($this->reports->ordersReportExport($request) as $order) {
                    fputcsv($handle, [
                        $order->order_number,
                        $order->customer->name,
                        $order->vendor?->brand_name,
                        $order->amount,
                        $order->status,
                        $order->payment_status,
                        $order->created_at->format('Y-m-d'),
                    ]);
                }
            }

            if ($type === 'vendors') {
                fputcsv($handle, ['Code', 'Brand', 'City', 'Orders', 'Earnings', 'Status']);
                foreach ($this->reports->vendorReportExport($request) as $vendor) {
                    fputcsv($handle, [
                        $vendor->vendor_code,
                        $vendor->brand_name,
                        $vendor->city,
                        $vendor->orders_completed,
                        $vendor->earnings,
                        $vendor->status,
                    ]);
                }
            }

            if ($type === 'refunds') {
                fputcsv($handle, ['Customer', 'Vendor', 'Order', 'Amount', 'Status', 'Date']);
                foreach ($this->reports->refundReportExport($request) as $refund) {
                    fputcsv($handle, [
                        $refund->customer->name,
                        $refund->order?->vendor?->brand_name,
                        $refund->order?->order_number,
                        $refund->amount,
                        $refund->status,
                        $refund->created_at->format('Y-m-d'),
                    ]);
                }
            }

            fclose($handle);
        }, "justbookit-{$type}-report.csv", ['Content-Type' => 'text/csv']);
    }
}

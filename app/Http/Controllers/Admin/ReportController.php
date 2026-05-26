<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\DashboardService;
use App\Services\Admin\ReportsService;
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
        if (in_array($request->get('report'), ['orders', 'refunds'], true)) {
            $this->validateListDateRange($request);
        }

        return view('admin.reports.index', [
            'summary' => $this->reports->summary(),
            'charts' => $this->dashboard->getCharts(),
            'report' => $request->string('report', 'overview')->toString(),
            'orders' => $request->get('report') === 'orders' ? $this->reports->ordersReport($request) : collect(),
            'vendors' => $request->get('report') === 'vendors' ? $this->reports->vendorReport() : collect(),
            'refunds' => $request->get('report') === 'refunds' ? $this->reports->refundReport($request) : collect(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeAdmin('export');

        if (in_array($request->get('type'), ['orders', 'refunds'], true)) {
            $this->validateListDateRange($request);
        }

        $type = $request->string('type', 'orders')->toString();

        return response()->streamDownload(function () use ($type, $request) {
            $handle = fopen('php://output', 'w');

            if ($type === 'orders') {
                fputcsv($handle, ['Order', 'Customer', 'Vendor', 'Amount', 'Status', 'Payment', 'Date']);
                foreach ($this->reports->ordersReport($request) as $order) {
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
                foreach ($this->reports->vendorReport() as $vendor) {
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

            fclose($handle);
        }, "justbookit-{$type}-report.csv", ['Content-Type' => 'text/csv']);
    }
}

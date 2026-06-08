<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\DashboardService;
use App\Support\AppliesListDateFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use AppliesListDateFilter;

    public function __construct(
        protected DashboardService $dashboard
    ) {}

    public function index(Request $request): View
    {
        $this->validateListDateRange($request);

        $admin = Auth::guard('admin')->user();
        $admin?->loadMissing(['role.permissions', 'assignedCities']);

        $from = $request->filled('from') ? Carbon::parse($request->date('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->date('to'))->endOfDay() : null;

        $data = $this->dashboard->getData($admin, $from, $to);
        $data['page_subtitle'] = $this->pageSubtitle($admin);

        return view('admin.dashboard.index', $data);
    }

    protected function pageSubtitle(?Admin $admin): string
    {
        $parts = ['Platform overview · Updated '.now()->format('M d, Y h:i A')];

        if ($admin?->role) {
            $parts[] = 'Role: '.$admin->role->name;
        }

        if ($admin && ! $admin->isSuperAdmin()) {
            $city = $admin->assignedCity();
            $parts[] = $city ? 'City: '.$city : 'No city assigned';
        }

        return implode(' · ', $parts);
    }
}

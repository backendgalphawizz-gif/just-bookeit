<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboard
    ) {}

    public function index(): View
    {
        $admin = Auth::guard('admin')->user();

        return view('admin.dashboard.index', $this->dashboard->getData($admin));
    }
}

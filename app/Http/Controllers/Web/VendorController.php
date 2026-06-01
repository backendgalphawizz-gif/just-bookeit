<?php

namespace App\Http\Controllers\Web;

use App\Models\Order;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VendorController extends WebController
{
    public function show(Vendor $vendor): View
    {
        abort_unless($vendor->status === 'active', 404);

        $portfolio = $vendor->portfolioItems()
            ->whereIn('status', ['approved', 'pending'])
            ->latest('id')
            ->limit(12)
            ->get();

        return view('web.vendors.show', compact('vendor', 'portfolio'));
    }
}

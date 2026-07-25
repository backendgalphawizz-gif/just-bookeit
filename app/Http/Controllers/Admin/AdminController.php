<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesAdminModule;
use App\Http\Controllers\Controller;
use App\Support\AdminListOrder;

abstract class AdminController extends Controller
{
    use AuthorizesAdminModule;

    protected function newestFirst(\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $query, string $column = 'created_at'): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation
    {
        return AdminListOrder::newestFirst($query, $column);
    }

    public function authorizeAdminAccess(): void
    {
        $this->authorizeAdmin();
    }

    protected function authorizeCityAccess(?string $city): void
    {
        if (! \App\Support\AdminCityScope::adminCanAccessCity($city)) {
            abort(403, 'You do not have access to records outside your assigned city.');
        }
    }

    protected function authorizeOrderCity(\App\Models\Order $order): void
    {
        if (! \App\Support\AdminCityScope::adminCanAccessOrder($order)) {
            abort(403, 'You do not have access to records outside your assigned city.');
        }
    }

    protected function authorizeVendorCity(\App\Models\Vendor $vendor): void
    {
        if (! \App\Support\AdminCityScope::adminCanAccessVendor($vendor)) {
            abort(403, 'You do not have access to records outside your assigned city.');
        }
    }

    protected function authorizeDriverCity(\App\Models\Driver $driver): void
    {
        if (! \App\Support\AdminCityScope::adminCanAccessDriver($driver)) {
            abort(403, 'You do not have access to records outside your assigned city.');
        }
    }

    protected function authorizeCustomerCity(\App\Models\Customer $customer): void
    {
        if (! \App\Support\AdminCityScope::adminCanAccessCustomer($customer)) {
            abort(403, 'You do not have access to records outside your assigned city.');
        }
    }

    protected function authorizeCheckoutCity(\App\Models\CheckoutOrder $checkout): void
    {
        if (! \App\Support\AdminCityScope::adminCanAccessCheckout($checkout)) {
            abort(403, 'You do not have access to records outside your assigned city.');
        }
    }
}

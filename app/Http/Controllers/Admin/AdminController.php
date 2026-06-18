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
}

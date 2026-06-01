<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesAdminModule;
use App\Http\Controllers\Controller;

abstract class AdminController extends Controller
{
    use AuthorizesAdminModule;

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

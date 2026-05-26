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
}

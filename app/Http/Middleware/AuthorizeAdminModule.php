<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Admin\AdminController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAdminModule
{
    public function handle(Request $request, Closure $next): Response
    {
        $controller = $request->route()?->getController();

        if ($controller instanceof AdminController) {
            $controller->authorizeAdminAccess();
        }

        return $next($request);
    }
}

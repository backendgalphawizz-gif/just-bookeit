<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin || ! $admin->isActive()) {
            Auth::guard('admin')->logout();

            return redirect()->route('admin.login')
                ->with('error', 'Please sign in to continue.');
        }

        return $next($request);
    }
}

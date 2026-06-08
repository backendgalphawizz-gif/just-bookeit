<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $vendor = Auth::guard('vendor')->user();

        if (! $vendor || in_array($vendor->status, ['rejected', 'suspended', 'blocked'], true)) {
            Auth::guard('vendor')->logout();

            return redirect()->route('vendor.login')
                ->with('error', 'Please sign in to continue.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerIsRegistered
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if ($customer instanceof Customer && $customer->is_guest) {
            if (! $request->expectsJson()) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()
                ->route('web.register')
                ->with('info', 'Create a free account to access this feature.');
        }

        return $next($request);
    }
}

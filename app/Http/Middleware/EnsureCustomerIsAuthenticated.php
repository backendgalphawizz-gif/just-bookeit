<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof Customer) {
            if (! $request->expectsJson()) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('web.login')
                ->with('error', 'Please sign in to continue.');
        }

        if (! in_array($customer->status, ['active'], true)) {
            Auth::guard('customer')->logout();

            return redirect()->route('web.login')
                ->with('error', $this->statusMessage($customer));
        }

        return $next($request);
    }

    protected function statusMessage(Customer $customer): string
    {
        if ($customer->status === 'inactive') {
            return filled($customer->rejection_reason)
                ? 'Your account has been deactivated: '.$customer->rejection_reason
                : 'Your account has been deactivated.';
        }

        return 'Please sign in to continue.';
    }
}

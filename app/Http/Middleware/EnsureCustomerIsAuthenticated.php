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
            return redirect()->route('web.login')
                ->with('error', 'Please sign in to continue.');
        }

        if ($customer->status !== 'active') {
            Auth::guard('customer')->logout();

            return redirect()->route('web.login')
                ->with('error', $this->statusMessage($customer));
        }

        return $next($request);
    }

    protected function statusMessage(Customer $customer): string
    {
        if ($customer->status === 'blocked') {
            return filled($customer->rejection_reason)
                ? 'Your account was blocked: '.$customer->rejection_reason
                : 'Your account was blocked.';
        }

        if ($customer->status === 'suspended') {
            return filled($customer->rejection_reason)
                ? 'Your account is suspended: '.$customer->rejection_reason
                : 'Your account is suspended.';
        }

        return 'Please sign in to continue.';
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $vendor = Auth::guard('vendor')->user();

        if (! $vendor instanceof Vendor) {
            return redirect()->route('vendor.login')
                ->with('error', 'Please sign in to continue.');
        }

        if ($vendor->status === 'rejected') {
            Auth::guard('vendor')->logout();

            return redirect()->route('vendor.login')
                ->with('error', $this->statusMessage('vendor application', $vendor->rejection_reason, 'rejected'));
        }

        if ($vendor->status === 'suspended') {
            Auth::guard('vendor')->logout();

            $message = filled($vendor->suspension_reason)
                ? 'Your vendor account is suspended: '.$vendor->suspension_reason
                : 'Your vendor account is suspended.';

            return redirect()->route('vendor.login')->with('error', $message);
        }

        if ($vendor->status === 'blocked') {
            Auth::guard('vendor')->logout();

            return redirect()->route('vendor.login')
                ->with('error', $this->statusMessage('vendor account', $vendor->rejection_reason, 'blocked'));
        }

        return $next($request);
    }

    protected function statusMessage(string $subject, ?string $reason, string $action): string
    {
        if (filled($reason)) {
            return 'Your '.$subject.' was '.$action.': '.$reason;
        }

        return 'Your '.$subject.' was '.$action.'.';
    }
}

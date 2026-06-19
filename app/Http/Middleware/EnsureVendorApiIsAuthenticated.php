<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorApiIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor authentication required.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => match ($user->status) {
                    'pending' => 'Your vendor account is pending approval.',
                    'rejected' => 'Your vendor account was rejected.',
                    'inactive' => 'Your vendor account has been deactivated.',
                    default => 'Your vendor account cannot access the app right now.',
                },
            ], 403);
        }

        return $next($request);
    }
}

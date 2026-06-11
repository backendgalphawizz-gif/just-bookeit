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

        if (in_array($user->status, ['rejected', 'suspended', 'blocked'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Your vendor account cannot access the app right now.',
            ], 403);
        }

        return $next($request);
    }
}

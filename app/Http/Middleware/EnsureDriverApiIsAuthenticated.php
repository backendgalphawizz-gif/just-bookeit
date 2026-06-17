<?php

namespace App\Http\Middleware;

use App\Models\Driver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDriverApiIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver authentication required.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => match ($user->status) {
                    'pending' => 'Your driver account is pending approval.',
                    'rejected' => 'Your driver account was rejected.',
                    'inactive' => 'Your driver account has been deactivated.',
                    default => 'Your driver account cannot access the app right now.',
                },
            ], 403);
        }

        return $next($request);
    }
}

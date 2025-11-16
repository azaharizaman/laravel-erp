<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vendor Portal Authentication Middleware
 *
 * Ensures the authenticated user is a vendor user with portal access.
 */
class VendorPortalAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if user is a vendor user
        if (!$user instanceof \Nexus\Procurement\Models\VendorUser) {
            return response()->json(['error' => 'Access denied. Vendor portal access required.'], 403);
        }

        // Check if vendor user can access portal
        if (!$user->canAccessPortal()) {
            return response()->json(['error' => 'Account is inactive or vendor is suspended'], 403);
        }

        return $next($request);
    }
}
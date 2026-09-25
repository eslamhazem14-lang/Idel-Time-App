<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-controlled maintenance mode. Admins, the login page and public
 * marketing pages keep working; everything else returns 503.
 */
class PlatformMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! settings('maintenance_mode') || $request->user()?->isAdmin()) {
            return $next($request);
        }

        $message = settings('maintenance_message');
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'code' => 'maintenance'], 503);
        }

        return response()->view('errors.503', ['message' => $message], 503);
    }
}

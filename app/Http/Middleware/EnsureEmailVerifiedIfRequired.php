<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Email verification is required only when the admin setting is enabled. */
class EnsureEmailVerifiedIfRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->isAdmin() && settings('require_email_verification') && ! $user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please verify your email address.', 'code' => 'unverified'], 403);
            }

            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}

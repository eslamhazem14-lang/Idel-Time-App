<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureEmailVerifiedIfRequired;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\PlatformMaintenance;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'active' => EnsureAccountActive::class,
            'verified.required' => EnsureEmailVerifiedIfRequired::class,
            'maintenance' => PlatformMaintenance::class,
        ]);
        $middleware->append(SecurityHeaders::class);
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '127.0.0.1'));
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => route($request->user()->homeRoute()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // Business rule violations carry user-safe messages.
        $exceptions->render(function (BusinessRuleException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'code' => $e->errorCode], $e->status);
            }

            return back()->withInput()->with('error', $e->getMessage());
        });

        $exceptions->dontReport([BusinessRuleException::class]);
    })->create();

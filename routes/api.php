<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API (Sanctum bearer tokens)
|--------------------------------------------------------------------------
| Designed for future desktop apps, IDE extensions and agent integrations.
| See docs/API.md.
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [Api\AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/register', [Api\AuthController::class, 'register'])->middleware('throttle:register');
});

Route::get('/categories', [Api\MetaController::class, 'categories']);
Route::get('/config', [Api\MetaController::class, 'config']);

Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
    Route::get('/auth/me', [Api\AuthController::class, 'me']);
    Route::post('/auth/logout', [Api\AuthController::class, 'logout']);

    Route::get('/notifications', [Api\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [Api\NotificationController::class, 'read']);

    Route::middleware(['role:developer', 'maintenance'])->group(function () {
        Route::get('/developer/dashboard', [Api\DeveloperController::class, 'dashboard']);

        Route::get('/tasks', [Api\TaskController::class, 'index']);
        Route::get('/tasks/recommended', [Api\TaskController::class, 'recommended']);
        Route::get('/tasks/{task}', [Api\TaskController::class, 'show']);

        Route::get('/claims', [Api\ClaimController::class, 'index']);
        Route::get('/claims/{claim}', [Api\ClaimController::class, 'show']);
        Route::get('/submissions', [Api\SubmissionController::class, 'index']);
        Route::get('/submissions/{submission}', [Api\SubmissionController::class, 'show']);

        Route::get('/wallet', [Api\WalletController::class, 'show']);
        Route::get('/wallet/transactions', [Api\WalletController::class, 'transactions']);
        Route::get('/withdrawals', [Api\WithdrawalController::class, 'index']);

        // Idle sessions: a desktop / IDE client reports "the AI agent is busy for N minutes".
        Route::post('/idle-sessions', [Api\IdleSessionController::class, 'store']);
        Route::get('/idle-sessions/current', [Api\IdleSessionController::class, 'current']);
        Route::post('/idle-sessions/{idleSession}/end', [Api\IdleSessionController::class, 'end']);

        Route::middleware('verified.required')->group(function () {
            Route::post('/tasks/{task}/claim', [Api\TaskController::class, 'claim'])->middleware('throttle:claims');
            Route::post('/tasks/{task}/submit', [Api\TaskController::class, 'submit'])->middleware('throttle:submissions');
            Route::post('/claims/{claim}/release', [Api\ClaimController::class, 'release']);
            Route::post('/claims/{claim}/draft', [Api\ClaimController::class, 'draft'])->middleware('throttle:drafts');
            Route::post('/withdrawals', [Api\WithdrawalController::class, 'store'])->middleware('throttle:withdrawals');
            Route::post('/submissions/{submission}/appeal', [Api\SubmissionController::class, 'appeal'])->middleware('throttle:reports');
        });
    });

    Route::middleware(['role:requester', 'maintenance'])->prefix('requester')->group(function () {
        Route::get('/dashboard', [Api\RequesterController::class, 'dashboard']);
        Route::get('/pricing/quote', [Api\RequesterController::class, 'quote']);
        Route::get('/tasks', [Api\RequesterController::class, 'tasks']);
        Route::get('/tasks/{task}/submissions', [Api\RequesterController::class, 'submissions']);
        Route::middleware('verified.required')->group(function () {
            Route::post('/tasks', [Api\RequesterController::class, 'storeTask']);
            Route::post('/submissions/{submission}/review', [Api\RequesterController::class, 'review']);
        });
    });
});

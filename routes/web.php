<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Developer;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Requester;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/how-it-works', [PageController::class, 'howItWorks'])->name('how-it-works');
Route::get('/developers', [PageController::class, 'developers'])->name('developers');
Route::get('/requesters', [PageController::class, 'requesters'])->name('requesters');
Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [Auth\LoginController::class, 'show'])->name('login');
    Route::post('/login', [Auth\LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/register', [Auth\RegisterController::class, 'show'])->name('register');
    Route::post('/register', [Auth\RegisterController::class, 'store'])->middleware('throttle:register');
    Route::get('/forgot-password', [Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [Auth\PasswordResetController::class, 'email'])->middleware('throttle:login')->name('password.email');
    Route::get('/reset-password/{token}', [Auth\PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [Auth\PasswordResetController::class, 'update'])->middleware('throttle:login')->name('password.update');
});

// Signed attachment links (used by API clients); the signature itself is the authorization.
Route::get('/files/{attachment}', [AttachmentController::class, 'signed'])->middleware('signed')->name('attachments.signed');

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [Auth\LoginController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', [Auth\EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [Auth\EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [Auth\EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');

    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');
    Route::get('/account', [ProfileController::class, 'edit'])->name('account.edit');
    Route::put('/account', [ProfileController::class, 'update'])->name('account.update');
    Route::put('/account/password', [ProfileController::class, 'password'])->name('account.password');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');

    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
});

/*
|--------------------------------------------------------------------------
| Developer area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'role:developer', 'maintenance'])->name('developer.')->group(function () {
    Route::get('/developer', [Developer\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/tasks', [Developer\TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{task}', [Developer\TaskController::class, 'show'])->name('tasks.show');
    Route::post('/tasks/{task}/report', [Developer\TaskController::class, 'report'])->middleware('throttle:reports')->name('tasks.report');
    Route::post('/tasks/{task}/review', [Developer\TaskController::class, 'review'])->name('tasks.review');

    Route::get('/submissions', [Developer\SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/{submission}', [Developer\SubmissionController::class, 'show'])->name('submissions.show');
    Route::post('/submissions/{submission}/appeal', [Developer\SubmissionController::class, 'appeal'])->middleware('throttle:reports')->name('submissions.appeal');

    Route::get('/wallet', [Developer\WalletController::class, 'index'])->name('wallet');

    // Watch & earn (opened by the Claude Code hook while the agent works)
    Route::get('/watch', [Developer\WatchController::class, 'index'])->name('watch');
    Route::get('/watch/status', [Developer\WatchController::class, 'status'])->name('watch.status');
    Route::get('/connect', [Developer\ConnectController::class, 'index'])->name('connect');
    Route::post('/connect/token', [Developer\ConnectController::class, 'token'])->name('connect.token');
    Route::delete('/connect/token', [Developer\ConnectController::class, 'revoke'])->name('connect.revoke');

    Route::middleware('verified.required')->group(function () {
        Route::post('/tasks/{task}/claim', [Developer\WorkController::class, 'claim'])->middleware('throttle:claims')->name('tasks.claim');
        Route::get('/work/{claim}', [Developer\WorkController::class, 'show'])->name('work.show');
        Route::post('/work/{claim}/submit', [Developer\WorkController::class, 'submit'])->middleware('throttle:submissions')->name('work.submit');
        Route::post('/work/{claim}/draft', [Developer\WorkController::class, 'draft'])->middleware('throttle:drafts')->name('work.draft');
        Route::post('/work/{claim}/release', [Developer\WorkController::class, 'release'])->name('work.release');
        Route::post('/watch/views', [Developer\WatchController::class, 'start'])->middleware('throttle:ads')->name('watch.start');
        Route::post('/watch/views/{adView}/complete', [Developer\WatchController::class, 'complete'])->middleware('throttle:ads')->name('watch.complete');
        Route::post('/wallet/withdrawals', [Developer\WalletController::class, 'withdraw'])->middleware('throttle:withdrawals')->name('withdrawals.store');
    });
});

/*
|--------------------------------------------------------------------------
| Requester area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'role:requester', 'maintenance'])->prefix('requester')->name('requester.')->group(function () {
    Route::get('/', [Requester\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/tasks', [Requester\TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{task}', [Requester\TaskController::class, 'show'])->whereNumber('task')->name('tasks.show');

    Route::get('/submissions', [Requester\SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/{submission}', [Requester\SubmissionController::class, 'show'])->name('submissions.show');
    Route::get('/billing', [Requester\BillingController::class, 'index'])->name('billing');
    Route::get('/batches/{batch}', [Requester\BatchController::class, 'show'])->whereNumber('batch')->name('batches.show');

    Route::middleware('verified.required')->group(function () {
        Route::get('/tasks/create', [Requester\TaskController::class, 'create'])->name('tasks.create');
        Route::post('/tasks', [Requester\TaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{task}/edit', [Requester\TaskController::class, 'edit'])->name('tasks.edit');
        Route::put('/tasks/{task}', [Requester\TaskController::class, 'update'])->name('tasks.update');
        Route::post('/tasks/{task}/pause', [Requester\TaskController::class, 'pause'])->name('tasks.pause');
        Route::post('/tasks/{task}/resume', [Requester\TaskController::class, 'resume'])->name('tasks.resume');
        Route::post('/tasks/{task}/cancel', [Requester\TaskController::class, 'cancel'])->name('tasks.cancel');
        Route::post('/tasks/{task}/slots', [Requester\TaskController::class, 'addSlots'])->name('tasks.slots');

        Route::get('/batches/create', [Requester\BatchController::class, 'create'])->name('batches.create');
        Route::post('/batches', [Requester\BatchController::class, 'store'])->name('batches.store');

        Route::post('/submissions/{submission}/approve', [Requester\SubmissionController::class, 'approve'])->name('submissions.approve');
        Route::post('/submissions/{submission}/reject', [Requester\SubmissionController::class, 'reject'])->name('submissions.reject');
        Route::post('/submissions/{submission}/revision', [Requester\SubmissionController::class, 'revision'])->name('submissions.revision');

        Route::post('/billing/deposits', [Requester\BillingController::class, 'deposit'])->middleware('throttle:withdrawals')->name('deposits.store');
    });
});

/*
|--------------------------------------------------------------------------
| Admin area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/suspend', [Admin\UserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/reactivate', [Admin\UserController::class, 'reactivate'])->name('users.reactivate');
    Route::post('/users/{user}/adjust-wallet', [Admin\UserController::class, 'adjustWallet'])->name('users.adjust-wallet');

    Route::get('/tasks', [Admin\TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{task}', [Admin\TaskController::class, 'show'])->name('tasks.show');
    Route::post('/tasks/{task}/approve', [Admin\TaskController::class, 'approve'])->name('tasks.approve');
    Route::post('/tasks/{task}/reject', [Admin\TaskController::class, 'reject'])->name('tasks.reject');
    Route::post('/tasks/{task}/suspend', [Admin\TaskController::class, 'suspend'])->name('tasks.suspend');
    Route::post('/tasks/{task}/reinstate', [Admin\TaskController::class, 'reinstate'])->name('tasks.reinstate');
    Route::post('/tasks/{task}/cancel', [Admin\TaskController::class, 'cancel'])->name('tasks.cancel');
    Route::post('/batches/{batch}/approve', [Admin\TaskController::class, 'approveBatch'])->name('batches.approve');

    Route::get('/submissions', [Admin\SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/{submission}', [Admin\SubmissionController::class, 'show'])->name('submissions.show');
    Route::post('/submissions/{submission}/approve', [Admin\SubmissionController::class, 'approve'])->name('submissions.approve');
    Route::post('/submissions/{submission}/reject', [Admin\SubmissionController::class, 'reject'])->name('submissions.reject');
    Route::post('/submissions/{submission}/revision', [Admin\SubmissionController::class, 'revision'])->name('submissions.revision');
    Route::post('/submissions/{submission}/override', [Admin\SubmissionController::class, 'override'])->name('submissions.override');

    Route::get('/withdrawals', [Admin\WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::get('/withdrawals/{withdrawal}', [Admin\WithdrawalController::class, 'show'])->name('withdrawals.show');
    Route::post('/withdrawals/{withdrawal}/status', [Admin\WithdrawalController::class, 'update'])->name('withdrawals.update');

    Route::get('/deposits', [Admin\DepositController::class, 'index'])->name('deposits.index');
    Route::post('/deposits/{deposit}/status', [Admin\DepositController::class, 'update'])->name('deposits.update');

    Route::get('/disputes', [Admin\DisputeController::class, 'index'])->name('disputes.index');
    Route::get('/disputes/{dispute}', [Admin\DisputeController::class, 'show'])->name('disputes.show');
    Route::post('/disputes/{dispute}/resolve', [Admin\DisputeController::class, 'resolve'])->name('disputes.resolve');

    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/{report}', [Admin\ReportController::class, 'update'])->name('reports.update');

    Route::get('/fraud', [Admin\FraudController::class, 'index'])->name('fraud.index');
    Route::post('/fraud/{flag}', [Admin\FraudController::class, 'update'])->name('fraud.update');

    Route::get('/categories', [Admin\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [Admin\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [Admin\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/templates', [Admin\TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [Admin\TemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [Admin\TemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}/edit', [Admin\TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [Admin\TemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [Admin\TemplateController::class, 'destroy'])->name('templates.destroy');

    Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');

    Route::get('/activity', [Admin\ActivityController::class, 'index'])->name('activity.index');
    Route::get('/ads', [Admin\AdController::class, 'index'])->name('ads.index');
    Route::post('/ads/payouts', [Admin\AdController::class, 'distribute'])->name('ads.distribute');
});

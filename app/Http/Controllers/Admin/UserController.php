<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Analytics\DeveloperStatsService;
use App\Services\Analytics\RequesterAnalyticsService;
use App\Services\UserService;
use App\Services\WalletService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
            'flagged' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $users = User::query()->with('wallet')
            ->when($filters['role'] ?? null, fn ($q, $r) => $q->where('role', $r))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['flagged'] ?? null, fn ($q) => $q->where('fraud_score', '>', 0)->orderByDesc('fraud_score'))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.users.index', compact('users', 'filters'));
    }

    public function show(User $user, WalletService $wallets, DeveloperStatsService $devStats, RequesterAnalyticsService $reqStats): View
    {
        $user->load('developerProfile', 'fraudFlags.reviewer');
        $wallet = $wallets->walletFor($user);

        return view('admin.users.show', [
            'user' => $user,
            'wallet' => $wallet,
            'stats' => $user->isDeveloper() ? $devStats->summary($user) : ($user->isRequester() ? $reqStats->summary($user) : []),
            'transactions' => $wallet->transactions()->latest('id')->limit(15)->get(),
            'submissions' => $user->isDeveloper() ? $user->submissions()->with('task')->latest('submitted_at')->limit(10)->get() : collect(),
            'tasks' => $user->isRequester() ? $user->requestedTasks()->latest()->limit(10)->get() : collect(),
            'sameIp' => $user->registration_ip ? User::query()->whereKeyNot($user->id)
                ->where(fn ($q) => $q->where('registration_ip', $user->registration_ip)->orWhere('last_login_ip', $user->last_login_ip ?? $user->registration_ip))
                ->limit(10)->get() : collect(),
            'activity' => $user->activityLogs()->latest('id')->limit(15)->get(),
        ]);
    }

    public function suspend(Request $request, User $user, UserService $users): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']])['reason'];
        $users->suspend($user, $request->user(), $reason);

        return back()->with('success', "{$user->name} has been suspended.");
    }

    public function reactivate(Request $request, User $user, UserService $users): RedirectResponse
    {
        $users->reactivate($user, $request->user());

        return back()->with('success', "{$user->name} has been reactivated.");
    }

    public function adjustWallet(Request $request, User $user, WalletService $wallets): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'regex:/^-?\d{1,7}(\.\d{1,2})?$/', 'not_in:0,0.0,0.00,-0,-0.00'],
            'note' => ['required', 'string', 'min:5', 'max:200'],
        ]);
        $wallets->adjust($user, Money::of($data['amount']), $data['note'], $request->user());

        return back()->with('success', 'Wallet adjusted.');
    }
}

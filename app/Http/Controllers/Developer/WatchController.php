<?php

namespace App\Http\Controllers\Developer;

use App\Enums\AdViewStatus;
use App\Http\Controllers\Controller;
use App\Models\AdView;
use App\Models\IdleSession;
use App\Models\User;
use App\Services\AdRewardService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Watch & earn": the page the Claude Code hook opens while the AI agent is
 * working. Shows the agent's status and serves rewarded video ads.
 */
class WatchController extends Controller
{
    public function index(Request $request, AdRewardService $ads): View
    {
        $user = $request->user();

        return view('developer.watch', [
            'state' => $this->state($user, $ads),
            'provider' => $ads->provider(),
            'countsViews' => $ads->countsViews(),
            'adUnitPath' => config('platform.ads.google.ad_unit_path'),
            'sharePercent' => (string) settings('ad_revenue_share_percent'),
            'minSeconds' => (int) settings('ad_min_watch_seconds'),
            'payouts' => $user->adViews()->where('status', AdViewStatus::Paid)->with('payout')
                ->selectRaw('ad_payout_id, COUNT(*) as views')->groupBy('ad_payout_id')->latest('ad_payout_id')->limit(5)->get(),
        ]);
    }

    public function status(Request $request, AdRewardService $ads): JsonResponse
    {
        return response()->json($this->state($request->user(), $ads));
    }

    public function start(Request $request, AdRewardService $ads): JsonResponse
    {
        $user = $request->user();
        $view = $ads->start($user, $this->openIdleSession($user), $request->ip(), $request->userAgent());

        return response()->json([
            'view' => $view->uuid,
            'provider' => $view->provider,
            'min_seconds' => (int) settings('ad_min_watch_seconds'),
        ], 201);
    }

    public function complete(Request $request, AdView $adView, AdRewardService $ads): JsonResponse
    {
        $view = $ads->complete($request->user(), $adView);

        return response()->json([
            'status' => $view->status->value,
            'message' => $view->status === AdViewStatus::Counted
                ? 'Counted! Your share is paid into your wallet when the ad network pays us.'
                : 'Thanks for watching! Demo ads are not counted on this server.',
            'state' => $this->state($request->user(), $ads),
        ]);
    }

    private function openIdleSession(User $user): ?IdleSession
    {
        return $user->idleSessions()->whereNull('ended_at')->latest('started_at')->first();
    }

    private function state(User $user, AdRewardService $ads): array
    {
        $idle = $this->openIdleSession($user);
        $wallet = app(WalletService::class)->walletFor($user);

        return [
            'agent' => $idle ? [
                'working' => true,
                'name' => $idle->agent ?: $idle->client,
                'started_at' => $idle->started_at->toIso8601String(),
                'minutes_left' => $idle->minutesLeft(),
            ] : ['working' => false],
            'last_finished_at' => $user->idleSessions()->whereNotNull('ended_at')->latest('ended_at')->value('ended_at')?->toIso8601String(),
            'today' => $ads->countedToday($user),
            'cap' => (int) settings('ad_daily_cap'),
            'awaiting' => $awaiting = $ads->awaitingPayout($user),
            'estimate' => $ads->estimate($awaiting)->format(),
            'paid_out' => $ads->paidOut($user)->format(),
            'balance' => $wallet->balance->format(),
            'enabled' => (bool) settings('ads_enabled'),
        ];
    }
}

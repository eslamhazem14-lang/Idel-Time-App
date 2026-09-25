<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdViewStatus;
use App\Http\Controllers\Controller;
use App\Models\AdPayout;
use App\Models\AdView;
use App\Models\User;
use App\Services\AdRewardService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Ad revenue sharing: record what the ad network paid and split the
 * developers' share by the ads each of them watched in that period.
 */
class AdController extends Controller
{
    public function index(Request $request, AdRewardService $ads): View
    {
        $month = DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', completed_at)" : "DATE_FORMAT(completed_at, '%Y-%m')";
        $awaiting = AdView::query()->where('status', AdViewStatus::Counted)
            ->selectRaw("{$month} as month, COUNT(*) as views, COUNT(DISTINCT user_id) as developers, MIN(completed_at) as first_at, MAX(completed_at) as last_at")
            ->groupBy('month')->orderBy('month')->get();

        $preview = null;
        if ($request->filled(['period_start', 'period_end', 'gross_amount'])) {
            $data = $this->validated($request);
            $preview = $ads->preview(Carbon::parse($data['period_start']), Carbon::parse($data['period_end']), Money::of((string) $data['gross_amount']));
            $preview['users'] = User::query()->whereIn('id', array_keys($preview['shares']))->pluck('name', 'id');
        }

        return view('admin.ads.index', [
            'awaiting' => $awaiting,
            'payouts' => AdPayout::query()->with('creator')->latest('id')->paginate(10),
            'preview' => $preview,
            'provider' => $ads->provider(),
            'countsViews' => $ads->countsViews(),
            'sharePercent' => (string) settings('ad_revenue_share_percent'),
            'today' => AdView::query()->whereIn('status', [AdViewStatus::Counted, AdViewStatus::Paid])->where('completed_at', '>=', now()->startOfDay())->count(),
        ]);
    }

    public function distribute(Request $request, AdRewardService $ads): RedirectResponse
    {
        $data = $this->validated($request) + $request->validate([
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $payout = $ads->distribute(
            Carbon::parse($data['period_start']), Carbon::parse($data['period_end']), Money::of((string) $data['gross_amount']),
            $request->user(), $data['reference'] ?? null, $data['note'] ?? null,
        );

        return redirect()->route('admin.ads.index')->with('success',
            "Paid {$payout->distributed_amount->format()} to {$payout->developer_count} developers for {$payout->view_count} watched ads.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start', 'before_or_equal:today'],
            'gross_amount' => ['required', 'decimal:0,2', 'min:0.01', 'max:10000000'],
        ]);
    }
}

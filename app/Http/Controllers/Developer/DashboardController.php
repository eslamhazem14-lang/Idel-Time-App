<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Services\Analytics\DeveloperStatsService;
use App\Services\TaskBrowser;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, DeveloperStatsService $stats, TaskBrowser $browser): View
    {
        $user = $request->user();

        return view('developer.dashboard', [
            'stats' => $stats->summary($user),
            'activeClaim' => $stats->activeClaim($user),
            'tasks' => $browser->query($user, ['sort' => 'best_rate'])->limit(6)->get(),
            'activity' => $stats->recentActivity($user),
            'series' => $stats->earningsSeries($user),
        ]);
    }
}

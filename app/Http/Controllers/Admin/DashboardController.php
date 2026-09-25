<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Analytics\AdminAnalyticsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(AdminAnalyticsService $analytics): View
    {
        return view('admin.dashboard', [
            'cards' => $analytics->cards(),
            'charts' => $analytics->charts(),
            'activity' => ActivityLog::query()->with('user')->latest('id')->limit(10)->get(),
        ]);
    }
}

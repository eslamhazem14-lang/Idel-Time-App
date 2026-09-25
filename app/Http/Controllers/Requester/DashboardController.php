<?php

namespace App\Http\Controllers\Requester;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\TaskSubmission;
use App\Services\Analytics\RequesterAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, RequesterAnalyticsService $analytics): View
    {
        $user = $request->user();

        return view('requester.dashboard', [
            'stats' => $analytics->summary($user),
            'tasks' => $user->requestedTasks()->with('category')->latest()->limit(6)->get(),
            'pending' => TaskSubmission::query()->with('task', 'developer')
                ->whereHas('task', fn ($q) => $q->where('requester_id', $user->id))
                ->where('status', SubmissionStatus::Pending->value)
                ->oldest('submitted_at')->limit(5)->get(),
        ]);
    }
}

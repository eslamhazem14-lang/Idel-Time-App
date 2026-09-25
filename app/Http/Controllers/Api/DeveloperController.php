<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClaimResource;
use App\Http\Resources\SubmissionResource;
use App\Http\Resources\TaskResource;
use App\Services\Analytics\DeveloperStatsService;
use App\Services\TaskBrowser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeveloperController extends Controller
{
    public function dashboard(Request $request, DeveloperStatsService $stats, TaskBrowser $browser): JsonResponse
    {
        $user = $request->user();
        $summary = $stats->summary($user);
        $active = $stats->activeClaim($user);

        return response()->json([
            'stats' => [
                'available_balance' => $summary['available']->toDecimal(),
                'pending_balance' => $summary['pending']->toDecimal(),
                'today_earnings' => $summary['today']->toDecimal(),
                'lifetime_earnings' => $summary['lifetime']->toDecimal(),
                'completed_tasks' => $summary['completed'],
                'pending_reviews' => $summary['pending_reviews'],
                'approval_rate' => $summary['approval_rate'],
                'level' => $summary['level'],
            ],
            'active_claim' => $active ? new ClaimResource($active) : null,
            'tasks_available_now' => TaskResource::collection($browser->query($user, ['sort' => 'best_rate'])->limit(6)->get()),
            'recent_activity' => SubmissionResource::collection($stats->recentActivity($user)),
        ]);
    }
}

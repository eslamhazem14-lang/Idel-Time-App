<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IdleSessionResource;
use App\Http\Resources\TaskResource;
use App\Models\IdleSession;
use App\Services\TaskBrowser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Integration point for desktop apps / IDE extensions: the client reports
 * that the developer's AI agent started a job expected to take N minutes,
 * and gets back tasks that fit that window.
 */
class IdleSessionController extends Controller
{
    public function store(Request $request, TaskBrowser $browser): JsonResponse
    {
        $data = $request->validate([
            'client' => ['required', 'string', 'max:40', 'alpha_dash'],       // e.g. vscode, macos, cli
            'agent' => ['nullable', 'string', 'max:40', 'alpha_dash'],        // e.g. claude-code, cursor, codex
            'expected_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'meta' => ['nullable', 'array', 'max:10'],
        ]);

        // Only one open idle session per user.
        $request->user()->hasMany(IdleSession::class)->whereNull('ended_at')->update(['ended_at' => now()]);
        $session = $request->user()->hasMany(IdleSession::class)->create($data + ['started_at' => now()]);

        return response()->json([
            'session' => new IdleSessionResource($session),
            // Page the client should open while the agent works (Watch & earn + matching tasks)
            'watch_url' => route('developer.watch', ['idle' => $session->id]),
            'recommended_tasks' => TaskResource::collection($browser->recommended($request->user(), $session->expected_minutes)),
        ], 201);
    }

    public function current(Request $request, TaskBrowser $browser): JsonResponse
    {
        $session = IdleSession::query()->where('user_id', $request->user()->id)->whereNull('ended_at')->latest('started_at')->first();

        return response()->json([
            'session' => $session ? new IdleSessionResource($session) : null,
            'recommended_tasks' => $session && $session->minutesLeft() > 0
                ? TaskResource::collection($browser->recommended($request->user(), $session->minutesLeft()))
                : [],
        ]);
    }

    public function end(Request $request, IdleSession $idleSession): IdleSessionResource
    {
        abort_unless($idleSession->user_id === $request->user()->id, 403);
        $idleSession->update(['ended_at' => $idleSession->ended_at ?? now()]);

        return new IdleSessionResource($idleSession);
    }
}

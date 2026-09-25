<?php

namespace App\Http\Controllers\Api;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClaimResource;
use App\Models\TaskClaim;
use App\Services\TaskClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClaimController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status', ClaimStatus::Active->value);

        return ClaimResource::collection(
            TaskClaim::query()->with('task.category')->where('developer_id', $request->user()->id)
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest('claimed_at')->paginate(20)
        );
    }

    public function show(TaskClaim $claim, TaskClaimService $claims): ClaimResource
    {
        $this->authorize('work', $claim);
        if ($claim->isActive() && $claim->hasTimedOut()) {
            $claims->expire($claim);
            $claim->refresh();
        }

        return new ClaimResource($claim->load('task.category', 'task.attachments'));
    }

    public function release(TaskClaim $claim, TaskClaimService $claims): JsonResponse
    {
        $this->authorize('work', $claim);
        $claims->release($claim);

        return response()->json(['message' => 'Task released.']);
    }

    public function draft(Request $request, TaskClaim $claim, TaskClaimService $claims): JsonResponse
    {
        $this->authorize('work', $claim);
        $claims->saveDraft($claim, $request->validate(['answer' => ['nullable', 'array']])['answer'] ?? []);

        return response()->json(['saved_at' => now()->toIso8601String()]);
    }
}

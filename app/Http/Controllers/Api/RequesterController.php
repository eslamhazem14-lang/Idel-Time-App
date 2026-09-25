<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\SubmissionResource;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Services\Analytics\RequesterAnalyticsService;
use App\Services\SubmissionReviewService;
use App\Services\TaskPricingService;
use App\Services\TaskService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class RequesterController extends Controller
{
    public function dashboard(Request $request, RequesterAnalyticsService $analytics): JsonResponse
    {
        $stats = collect($analytics->summary($request->user()))
            ->map(fn ($v) => $v instanceof Money ? $v->toDecimal() : $v);

        return response()->json(['stats' => $stats]);
    }

    /** Server-side price calculation: GET /api/requester/pricing/quote?reward=0.50&slots=10 */
    public function quote(Request $request, TaskPricingService $pricing): JsonResponse
    {
        $data = $request->validate(['reward' => ['required', 'decimal:0,2', 'min:0.01'], 'slots' => ['nullable', 'integer', 'min:1', 'max:10000']]);

        return response()->json($pricing->quote((string) $data['reward'], (int) ($data['slots'] ?? 1))->toArray());
    }

    public function tasks(Request $request): AnonymousResourceCollection
    {
        return TaskResource::collection($request->user()->requestedTasks()->with('category')->latest()->paginate(20));
    }

    public function storeTask(TaskRequest $request, TaskService $tasks): JsonResponse
    {
        $task = $tasks->create($request->user(), $request->validated(), $request->file('attachments') ?? []);

        return (new TaskResource($task->load('category', 'attachments')))->detailed()->response()->setStatusCode(201);
    }

    public function submissions(Request $request, Task $task): AnonymousResourceCollection
    {
        $this->authorize('manage', $task);

        return SubmissionResource::collection($task->submissions()->with('developer', 'attachments')->latest('submitted_at')->paginate(25));
    }

    public function review(Request $request, TaskSubmission $submission, SubmissionReviewService $reviews): SubmissionResource
    {
        $this->authorize('review', $submission);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject', 'revision'])],
            'reason' => ['required_unless:decision,approve', 'nullable', 'string', 'min:10', 'max:1000'],
        ]);

        $result = match ($data['decision']) {
            'approve' => $reviews->approve($submission, $request->user()),
            'reject' => $reviews->reject($submission, $request->user(), $data['reason']),
            'revision' => $reviews->requestRevision($submission, $request->user(), $data['reason']),
        };

        return new SubmissionResource($result->load('task'));
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\ClaimStatus;
use App\Enums\Difficulty;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAnswerRequest;
use App\Http\Resources\ClaimResource;
use App\Http\Resources\SubmissionResource;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskClaim;
use App\Services\TaskBrowser;
use App\Services\TaskClaimService;
use App\Services\TaskSubmissionService;
use App\TaskTypes\TaskTypeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request, TaskBrowser $browser, TaskTypeRegistry $types): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:60'],
            'max_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'min_reward' => ['nullable', 'numeric', 'min:0'],
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'type' => ['nullable', Rule::in($types->keys())],
            'skill' => ['nullable', 'string', 'max:30'],
            'sort' => ['nullable', Rule::in(array_keys(TaskBrowser::SORTS))],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return TaskResource::collection($browser->paginate($request->user(), $filters, (int) ($filters['per_page'] ?? 15)));
    }

    /** Best tasks for an idle window: GET /api/tasks/recommended?minutes=8 */
    public function recommended(Request $request, TaskBrowser $browser): AnonymousResourceCollection
    {
        $data = $request->validate(['minutes' => ['required', 'integer', 'min:1', 'max:120'], 'limit' => ['nullable', 'integer', 'min:1', 'max:20']]);

        return TaskResource::collection($browser->recommended($request->user(), (int) $data['minutes'], (int) ($data['limit'] ?? 5)));
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return (new TaskResource($task->load('category', 'attachments')))->detailed();
    }

    public function claim(Request $request, Task $task, TaskClaimService $claims): JsonResponse
    {
        $this->authorize('claim', $task);
        $claim = $claims->claim($task, $request->user(), $request->ip());

        return (new ClaimResource($claim->load('task.category', 'task.attachments')))->response()->setStatusCode(201);
    }

    public function submit(SubmitAnswerRequest $request, Task $task, TaskSubmissionService $submissions): JsonResponse
    {
        $claim = TaskClaim::query()->where('task_id', $task->id)->where('developer_id', $request->user()->id)->first();
        if (! $claim) {
            throw BusinessRuleException::make('You have not claimed this task.', 'not_claimed', 404);
        }
        if ($claim->status !== ClaimStatus::Active && $claim->status !== ClaimStatus::Expired) {
            throw BusinessRuleException::make('This task has already been submitted.', 'already_submitted', 409);
        }

        $submission = $submissions->submit($claim, $request->answer(), $request->uploadedAttachments(), $request->ip());

        return (new SubmissionResource($submission->load('task', 'attachments')))->response()->setStatusCode(201);
    }
}

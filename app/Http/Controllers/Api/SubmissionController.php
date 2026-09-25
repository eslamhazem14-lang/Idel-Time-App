<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubmissionResource;
use App\Models\TaskSubmission;
use App\Services\DisputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class SubmissionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(SubmissionStatus::class)]])['status'] ?? null;

        return SubmissionResource::collection(
            TaskSubmission::query()->with('task.category', 'dispute')->where('developer_id', $request->user()->id)
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest('submitted_at')->paginate(20)
        );
    }

    public function show(TaskSubmission $submission): SubmissionResource
    {
        $this->authorize('view', $submission);

        return new SubmissionResource($submission->load('task.category', 'attachments', 'dispute'));
    }

    public function appeal(Request $request, TaskSubmission $submission, DisputeService $disputes): JsonResponse
    {
        $this->authorize('appeal', $submission);
        $data = $request->validate(['reason' => ['required', 'string', 'min:20', 'max:3000']]);
        $dispute = $disputes->open($submission, $request->user(), $data['reason']);

        return response()->json(['id' => $dispute->id, 'status' => $dispute->status->value], 201);
    }
}

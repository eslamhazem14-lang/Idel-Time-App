<?php

namespace App\Http\Controllers\Developer;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\TaskSubmission;
use App\Services\DisputeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(SubmissionStatus::class)]])['status'] ?? null;

        return view('developer.submissions.index', [
            'submissions' => TaskSubmission::query()->with('task.category', 'dispute')
                ->where('developer_id', $request->user()->id)
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest('submitted_at')->paginate(15)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(TaskSubmission $submission): View
    {
        $this->authorize('view', $submission);
        $submission->load('task.category', 'attachments', 'dispute', 'claim');

        return view('developer.submissions.show', [
            'submission' => $submission,
            'task' => $submission->task,
            'canAppeal' => $submission->status === SubmissionStatus::Rejected && ! $submission->dispute
                && $submission->reviewed_at?->gt(now()->subDays(DisputeService::APPEAL_WINDOW_DAYS)),
            'myReview' => $submission->task->reviews()->where('developer_id', $submission->developer_id)->first(),
        ]);
    }

    public function appeal(Request $request, TaskSubmission $submission, DisputeService $disputes): RedirectResponse
    {
        $this->authorize('appeal', $submission);
        $data = $request->validate(['reason' => ['required', 'string', 'min:20', 'max:3000']]);
        $disputes->open($submission, $request->user(), $data['reason']);

        return back()->with('success', 'Appeal submitted. An administrator will review it.');
    }
}

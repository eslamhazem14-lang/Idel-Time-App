<?php

namespace App\Http\Controllers\Requester;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\TaskSubmission;
use App\Services\SubmissionReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function __construct(private readonly SubmissionReviewService $reviews) {}

    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(SubmissionStatus::class)]])['status'] ?? SubmissionStatus::Pending->value;

        return view('requester.submissions.index', [
            'submissions' => TaskSubmission::query()->with('task', 'developer.developerProfile')
                ->whereHas('task', fn ($q) => $q->where('requester_id', $request->user()->id))
                ->where('status', $status)
                ->oldest('submitted_at')->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(TaskSubmission $submission): View
    {
        $this->authorize('review', $submission);
        $submission->load('task.category', 'developer.developerProfile', 'attachments', 'claim');

        return view('requester.submissions.show', [
            'submission' => $submission,
            'task' => $submission->task,
            'history' => $submission->claim->submissions()->where('id', '!=', $submission->id)->latest()->get(),
            'next' => TaskSubmission::query()->where('task_id', $submission->task_id)
                ->where('status', SubmissionStatus::Pending->value)->where('id', '!=', $submission->id)->oldest('submitted_at')->first(),
        ]);
    }

    public function approve(Request $request, TaskSubmission $submission): RedirectResponse
    {
        $this->authorize('review', $submission);
        $this->reviews->approve($submission, $request->user());

        return $this->afterReview($submission, 'Approved — the developer has been paid.');
    }

    public function reject(Request $request, TaskSubmission $submission): RedirectResponse
    {
        $this->authorize('review', $submission);
        $reason = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']])['reason'];
        $this->reviews->reject($submission, $request->user(), $reason);

        return $this->afterReview($submission, 'Submission rejected. The slot was re-opened.');
    }

    public function revision(Request $request, TaskSubmission $submission): RedirectResponse
    {
        $this->authorize('review', $submission);
        $note = $request->validate(['note' => ['required', 'string', 'min:10', 'max:1000']])['note'];
        $this->reviews->requestRevision($submission, $request->user(), $note);

        return $this->afterReview($submission, 'Revision requested. The developer has a fresh timer to update their work.');
    }

    private function afterReview(TaskSubmission $submission, string $message): RedirectResponse
    {
        $next = TaskSubmission::query()->where('task_id', $submission->task_id)
            ->where('status', SubmissionStatus::Pending->value)->oldest('submitted_at')->first();

        return ($next ? redirect()->route('requester.submissions.show', $next) : redirect()->route('requester.tasks.show', $submission->task_id))
            ->with('success', $message);
    }
}

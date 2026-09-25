<?php

namespace App\Http\Controllers\Admin;

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
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(SubmissionStatus::class)],
            'flagged' => ['nullable', 'boolean'],
        ]);

        return view('admin.submissions.index', [
            'submissions' => TaskSubmission::query()->with('task.requester', 'developer')
                ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
                ->when($filters['flagged'] ?? null, fn ($q) => $q->where('is_flagged', true))
                ->latest('submitted_at')->paginate(25)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(TaskSubmission $submission): View
    {
        $submission->load('task.category', 'task.requester', 'developer.developerProfile', 'attachments', 'reviewer', 'dispute', 'claim');

        return view('admin.submissions.show', [
            'submission' => $submission,
            'task' => $submission->task,
            'history' => $submission->claim->submissions()->where('id', '!=', $submission->id)->latest()->get(),
            'duplicates' => TaskSubmission::query()->with('developer')->where('answer_hash', $submission->answer_hash)
                ->whereKeyNot($submission->id)->limit(10)->get(),
        ]);
    }

    public function approve(Request $request, TaskSubmission $submission): RedirectResponse
    {
        $this->reviews->approve($submission, $request->user());

        return back()->with('success', 'Submission approved and developer paid.');
    }

    public function reject(Request $request, TaskSubmission $submission): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']])['reason'];
        $this->reviews->reject($submission, $request->user(), $reason);

        return back()->with('success', 'Submission rejected.');
    }

    public function revision(Request $request, TaskSubmission $submission): RedirectResponse
    {
        $note = $request->validate(['note' => ['required', 'string', 'min:10', 'max:1000']])['note'];
        $this->reviews->requestRevision($submission, $request->user(), $note);

        return back()->with('success', 'Revision requested.');
    }

    /** Override a final decision (rejected → approved, approved → rejected with clawback). */
    public function override(Request $request, TaskSubmission $submission): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', Rule::in(['approved', 'rejected'])],
            'note' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $data['to'] === 'approved'
            ? $this->reviews->overrideApprove($submission, $request->user(), $data['note'])
            : $this->reviews->overrideReject($submission, $request->user(), $data['note']);

        return back()->with('success', 'Submission status overridden.');
    }
}

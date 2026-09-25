<?php

namespace App\Http\Controllers\Developer;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAnswerRequest;
use App\Models\Task;
use App\Models\TaskClaim;
use App\Services\TaskClaimService;
use App\Services\TaskSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkController extends Controller
{
    public function claim(Request $request, Task $task, TaskClaimService $claims): RedirectResponse
    {
        $this->authorize('claim', $task);
        $claim = $claims->claim($task, $request->user(), $request->ip());

        return redirect()->route('developer.work.show', $claim)->with('success', 'Task started — the timer is running.');
    }

    public function show(Request $request, TaskClaim $claim, TaskClaimService $claims): View|RedirectResponse
    {
        $this->authorize('work', $claim);

        // Lazily enforce the server-side deadline.
        if ($claim->isActive() && $claim->hasTimedOut()) {
            $claims->expire($claim);
            $claim->refresh();
        }
        if ($claim->status !== ClaimStatus::Active) {
            $submission = $claim->latestSubmission;
            $message = $claim->status === ClaimStatus::Expired ? 'Your task expired.' : 'This task is no longer in progress.';

            return $submission
                ? redirect()->route('developer.submissions.show', $submission)->with('info', $message)
                : redirect()->route('developer.tasks.index')->with('error', $message);
        }

        $claim->load('task.category', 'task.attachments', 'submissions');

        return view('developer.work', [
            'claim' => $claim,
            'task' => $claim->task,
            'revision' => $claim->submissions->sortByDesc('id')->first(),
            'graceSeconds' => (int) settings('claim_grace_seconds'),
        ]);
    }

    public function submit(SubmitAnswerRequest $request, TaskClaim $claim, TaskSubmissionService $submissions): RedirectResponse
    {
        $this->authorize('work', $claim);
        $submission = $submissions->submit($claim, $request->answer(), $request->uploadedAttachments(), $request->ip());

        return redirect()->route('developer.submissions.show', $submission)
            ->with('success', 'Submitted! '.$submission->reward->format().' is pending review.');
    }

    public function draft(Request $request, TaskClaim $claim, TaskClaimService $claims): JsonResponse
    {
        $this->authorize('work', $claim);
        $data = $request->validate(['answer' => ['nullable', 'array']]);
        $claims->saveDraft($claim, $data['answer'] ?? []);

        return response()->json(['saved_at' => now()->toIso8601String()]);
    }

    public function release(Request $request, TaskClaim $claim, TaskClaimService $claims): RedirectResponse
    {
        $this->authorize('work', $claim);
        $claims->release($claim);

        return redirect()->route('developer.tasks.index')->with('success', 'Task released. It is available to other developers again.');
    }
}

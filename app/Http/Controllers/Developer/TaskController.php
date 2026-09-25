<?php

namespace App\Http\Controllers\Developer;

use App\Enums\Difficulty;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskReview;
use App\Notifications\AdminAlertNotification;
use App\Services\ActivityLogger;
use App\Services\TaskBrowser;
use App\Services\TaskClaimService;
use App\Support\AdminNotifier;
use App\TaskTypes\TaskTypeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request, TaskBrowser $browser, TaskTypeRegistry $types): View
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:60'],
            'max_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'min_reward' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'type' => ['nullable', Rule::in($types->keys())],
            'skill' => ['nullable', 'string', 'max:30'],
            'sort' => ['nullable', Rule::in(array_keys(TaskBrowser::SORTS))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return view('developer.tasks.index', [
            'tasks' => $browser->paginate($request->user(), $filters),
            'filters' => $filters,
            'categories' => TaskCategory::query()->active()->get(),
            'skills' => $browser->skills(),
            'types' => $types->options(),
        ]);
    }

    public function show(Request $request, Task $task, TaskClaimService $claims): View
    {
        $this->authorize('view', $task);
        $task->load('category', 'attachments', 'requester');
        $myClaim = $task->claims()->where('developer_id', $request->user()->id)->with('latestSubmission')->first();

        return view('developer.tasks.show', [
            'task' => $task,
            'myClaim' => $myClaim,
            'claimMinutes' => $claims->claimDurationMinutes($task),
            'rating' => $task->reviews()->avg('rating'),
            'myReview' => $task->reviews()->where('developer_id', $request->user()->id)->first(),
        ]);
    }

    public function report(Request $request, Task $task, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('view', $task);
        $data = $request->validate([
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $report = Report::query()->create([
            'reporter_id' => $request->user()->id,
            'reported_user_id' => $task->requester_id,
            'task_id' => $task->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => ReportStatus::Open,
        ]);
        $logger->log('report.created', $report);
        AdminNotifier::send(new AdminAlertNotification('Task reported', "“{$task->title}”: ".$report->reason->label(), route('admin.reports.index'), 'flag'));

        return back()->with('success', 'Thanks — the report was sent to our moderation team.');
    }

    public function review(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('review', $task);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'clarity' => ['nullable', 'integer', 'between:1,5'],
            'time_accurate' => ['nullable', 'boolean'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = TaskReview::query()->where('task_id', $task->id)->where('developer_id', $request->user()->id)->first()
            ?? (new TaskReview)->forceFill(['task_id' => $task->id, 'developer_id' => $request->user()->id]);
        $review->fill($data)->save();

        return back()->with('success', 'Thanks for rating this task.');
    }
}

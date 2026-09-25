<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskBatch;
use App\Services\Analytics\RequesterAnalyticsService;
use App\Services\TaskService;
use App\Support\TaskReviewChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'batch' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? (isset($filters['batch']) || isset($filters['q']) ? null : TaskStatus::PendingApproval->value);

        return view('admin.tasks.index', [
            'tasks' => Task::query()->with('category', 'requester')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($filters['batch'] ?? null, fn ($q, $b) => $q->where('batch_id', $b))
                ->when($filters['q'] ?? null, fn ($q, $t) => $q->where('title', 'like', "%{$t}%"))
                ->latest()->paginate(25)->withQueryString(),
            'status' => $status,
            'filters' => $filters,
            'counts' => Task::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status'),
            'pendingBatches' => TaskBatch::query()->with('requester')->where('status', 'pending_approval')->latest()->get(),
        ]);
    }

    public function show(Task $task, RequesterAnalyticsService $analytics): View
    {
        $task->load('category', 'requester.wallet', 'attachments', 'batch', 'approver', 'reports.reporter');

        return view('admin.tasks.show', [
            'task' => $task,
            'issues' => TaskReviewChecklist::issues($task),
            'analytics' => $analytics->forTask($task),
            'submissions' => $task->submissions()->with('developer')->latest('submitted_at')->limit(20)->get(),
            'requesterStats' => [
                'tasks' => Task::query()->where('requester_id', $task->requester_id)->count(),
                'rejected' => Task::query()->where('requester_id', $task->requester_id)->where('status', TaskStatus::Rejected->value)->count(),
            ],
        ]);
    }

    public function approve(Request $request, Task $task): RedirectResponse
    {
        $this->tasks->approve($task, $request->user());
        $next = Task::query()->where('status', TaskStatus::PendingApproval->value)->oldest()->first();

        return ($next ? redirect()->route('admin.tasks.show', $next) : redirect()->route('admin.tasks.index'))
            ->with('success', "“{$task->title}” is live.");
    }

    public function reject(Request $request, Task $task): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']])['reason'];
        $this->tasks->reject($task, $request->user(), $reason);

        return redirect()->route('admin.tasks.index')->with('success', 'Task rejected and budget refunded to the requester.');
    }

    public function suspend(Request $request, Task $task): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']])['reason'];
        $this->tasks->suspend($task, $request->user(), $reason);

        return back()->with('success', 'Task suspended.');
    }

    public function reinstate(Request $request, Task $task): RedirectResponse
    {
        $this->tasks->reinstate($task, $request->user());

        return back()->with('success', 'Task reinstated.');
    }

    public function cancel(Request $request, Task $task): RedirectResponse
    {
        $this->tasks->cancel($task, $request->user());

        return back()->with('success', 'Task cancelled and unused budget refunded.');
    }

    public function approveBatch(Request $request, TaskBatch $batch): RedirectResponse
    {
        $count = 0;
        $batch->tasks()->where('status', TaskStatus::PendingApproval->value)->each(function (Task $task) use ($request, &$count) {
            $this->tasks->approve($task, $request->user());
            $count++;
        });

        return back()->with('success', "Approved {$count} tasks in batch “{$batch->title}”.");
    }
}

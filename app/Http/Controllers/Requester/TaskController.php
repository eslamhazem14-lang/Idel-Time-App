<?php

namespace App\Http\Controllers\Requester;

use App\Enums\Difficulty;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Models\TaskBatch;
use App\Models\TaskCategory;
use App\Models\TaskTemplate;
use App\Services\Analytics\RequesterAnalyticsService;
use App\Services\TaskService;
use App\Services\WalletService;
use App\TaskTypes\TaskTypeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(TaskStatus::class)]])['status'] ?? null;

        return view('requester.tasks.index', [
            'tasks' => $request->user()->requestedTasks()->with('category')
                ->withCount(['submissions as pending_count' => fn ($q) => $q->where('status', 'pending')])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()->paginate(15)->withQueryString(),
            'status' => $status,
            'batches' => TaskBatch::query()->where('requester_id', $request->user()->id)->latest()->limit(5)->get(),
        ]);
    }

    public function create(Request $request, TaskTypeRegistry $types, WalletService $wallets): View
    {
        $template = $request->filled('template') ? TaskTemplate::query()->where('is_active', true)->find($request->integer('template')) : null;

        return view('requester.tasks.create', $this->formData($types) + [
            'task' => null,
            'template' => $template,
            'templates' => TaskTemplate::query()->where('is_active', true)->with('category')->orderBy('name')->get(),
            'balance' => $wallets->walletFor($request->user())->balance,
        ]);
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $task = $this->tasks->create($request->user(), $request->validated(), $request->file('attachments') ?? []);

        return redirect()->route('requester.tasks.show', $task)
            ->with('success', 'Task submitted for approval. '.$task->total_budget->format().' has been reserved from your balance.');
    }

    public function show(Task $task, RequesterAnalyticsService $analytics): View
    {
        $this->authorize('manage', $task);
        $task->load('category', 'attachments', 'batch');

        return view('requester.tasks.show', [
            'task' => $task,
            'analytics' => $analytics->forTask($task),
            'submissions' => $task->submissions()->with('developer')->latest('submitted_at')->paginate(15),
            'activeClaims' => $task->activeClaims()->count(),
        ]);
    }

    public function edit(Request $request, Task $task, TaskTypeRegistry $types, WalletService $wallets): View
    {
        $this->authorize('update', $task);

        return view('requester.tasks.create', $this->formData($types) + [
            'task' => $task,
            'template' => null,
            'templates' => collect(),
            'balance' => $wallets->walletFor($request->user())->balance->add($task->escrow_balance),
        ]);
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        $this->tasks->update($task, $request->validated());

        return redirect()->route('requester.tasks.show', $task)->with('success', 'Task updated and re-submitted for approval.');
    }

    public function pause(Task $task): RedirectResponse
    {
        $this->authorize('manage', $task);
        $this->tasks->pause($task);

        return back()->with('success', 'Task paused. Developers already working on it can still submit.');
    }

    public function resume(Task $task): RedirectResponse
    {
        $this->authorize('manage', $task);
        $this->tasks->resume($task);

        return back()->with('success', 'Task is live again.');
    }

    public function cancel(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('manage', $task);
        $this->tasks->cancel($task, $request->user());

        return back()->with('success', 'Task cancelled. Unused budget was returned to your balance (pending submissions can still be reviewed).');
    }

    public function addSlots(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('manage', $task);
        $slots = (int) $request->validate(['slots' => ['required', 'integer', 'min:1', 'max:10000']])['slots'];
        $this->tasks->addSlots($task, $slots);

        return back()->with('success', "Added {$slots} slot(s).");
    }

    private function formData(TaskTypeRegistry $types): array
    {
        return [
            'categories' => TaskCategory::query()->active()->get(),
            'types' => $types->all(),
            'difficulties' => Difficulty::cases(),
            'commission' => settings()->commissionPercent(),
            'limits' => [
                'min_reward' => settings('min_reward'),
                'max_reward' => settings('max_reward'),
                'min_minutes' => settings('min_task_minutes'),
                'max_minutes' => settings('max_task_minutes'),
            ],
        ];
    }
}

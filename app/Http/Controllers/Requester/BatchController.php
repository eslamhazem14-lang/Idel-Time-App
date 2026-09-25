<?php

namespace App\Http\Controllers\Requester;

use App\Enums\Difficulty;
use App\Http\Controllers\Controller;
use App\Http\Requests\BatchRequest;
use App\Models\TaskBatch;
use App\Models\TaskCategory;
use App\Services\TaskService;
use App\Services\WalletService;
use App\TaskTypes\TaskTypeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function create(Request $request, TaskTypeRegistry $types, WalletService $wallets): View
    {
        return view('requester.batches.create', [
            'categories' => TaskCategory::query()->active()->get(),
            'types' => $types->all(),
            'difficulties' => Difficulty::cases(),
            'commission' => settings()->commissionPercent(),
            'balance' => $wallets->walletFor($request->user())->balance,
            'maxItems' => config('platform.batch.max_items'),
        ]);
    }

    public function store(BatchRequest $request, TaskService $tasks): RedirectResponse
    {
        $batch = $tasks->createBatch($request->user(), $request->validated(), $request->items());

        return redirect()->route('requester.batches.show', $batch)
            ->with('success', "Batch of {$batch->total_tasks} tasks submitted for approval. {$batch->total_budget->format()} reserved.");
    }

    public function show(Request $request, TaskBatch $batch): View
    {
        abort_unless($batch->requester_id === $request->user()->id, 403);

        return view('requester.batches.show', [
            'batch' => $batch->load('category'),
            'tasks' => $batch->tasks()->withCount(['submissions as pending_count' => fn ($q) => $q->where('status', 'pending')])->paginate(25),
        ]);
    }
}

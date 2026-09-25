<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DisputeStatus;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DisputeController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(DisputeStatus::class)]])['status'] ?? DisputeStatus::Open->value;

        return view('admin.disputes.index', [
            'disputes' => Dispute::query()->with('developer', 'submission.task')->where('status', $status)->latest()->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Dispute $dispute): View
    {
        return view('admin.disputes.show', ['dispute' => $dispute->load('developer.developerProfile', 'submission.task.requester', 'submission.reviewer', 'submission.attachments', 'resolver')]);
    }

    public function resolve(Request $request, Dispute $dispute, DisputeService $disputes): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['uphold', 'deny'])],
            'note' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $disputes->resolve($dispute, $request->user(), $data['decision'] === 'uphold', $data['note']);

        return redirect()->route('admin.disputes.index')->with('success', 'Dispute resolved.');
    }
}

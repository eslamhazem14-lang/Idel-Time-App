<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(ReportStatus::class)]])['status'] ?? ReportStatus::Open->value;

        return view('admin.reports.index', [
            'reports' => Report::query()->with('reporter', 'reportedUser', 'task')->where('status', $status)->latest()->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function update(Request $request, Report $report, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ReportStatus::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $report->forceFill([
            'status' => $data['status'],
            'resolution_note' => $data['note'] ?? $report->resolution_note,
            'resolved_by' => $request->user()->id,
            'resolved_at' => in_array($data['status'], ['resolved', 'dismissed'], true) ? now() : null,
        ])->save();
        $logger->log('report.'.$data['status'], $report);

        return back()->with('success', 'Report updated.');
    }
}

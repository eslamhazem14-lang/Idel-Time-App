<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\ActivityLogger;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(WithdrawalStatus::class)]])['status'] ?? null;

        return view('admin.withdrawals.index', [
            'withdrawals' => Withdrawal::query()->with('developer')
                ->when($status, fn ($q) => $q->where('status', $status), fn ($q) => $q->orderByRaw("CASE WHEN status IN ('pending','processing') THEN 0 ELSE 1 END"))
                ->latest()->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Withdrawal $withdrawal, ActivityLogger $logger): View
    {
        // Viewing decrypted payout details is audited.
        $logger->log('withdrawal.details_viewed', $withdrawal);

        return view('admin.withdrawals.show', [
            'withdrawal' => $withdrawal->load('developer.wallet', 'processor'),
            'history' => Withdrawal::query()->where('developer_id', $withdrawal->developer_id)->whereKeyNot($withdrawal->id)->latest()->limit(10)->get(),
        ]);
    }

    public function update(Request $request, Withdrawal $withdrawal, WithdrawalService $service): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['processing', 'paid', 'rejected'])],
            'note' => ['nullable', 'required_if:status,rejected', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        match ($data['status']) {
            'processing' => $service->markProcessing($withdrawal, $request->user(), $data['note'] ?? null),
            'paid' => $service->markPaid($withdrawal, $request->user(), $data['note'] ?? null, $data['reference'] ?? null),
            'rejected' => $service->reject($withdrawal, $request->user(), $data['note']),
        };

        return back()->with('success', "Withdrawal #{$withdrawal->id} marked {$data['status']}.");
    }
}

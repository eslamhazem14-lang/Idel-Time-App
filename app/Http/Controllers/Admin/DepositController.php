<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepositStatus;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\DepositService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(DepositStatus::class)]])['status'] ?? null;

        return view('admin.deposits.index', [
            'deposits' => Deposit::query()->with('requester')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function update(Request $request, Deposit $deposit, DepositService $service): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['completed', 'rejected'])],
            'note' => ['nullable', 'required_if:status,rejected', 'string', 'max:1000'],
        ]);

        $data['status'] === 'completed'
            ? $service->approve($deposit, $request->user(), $data['note'] ?? null)
            : $service->reject($deposit, $request->user(), $data['note']);

        return back()->with('success', 'Deposit updated.');
    }
}

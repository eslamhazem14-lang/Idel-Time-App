<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FraudFlagStatus;
use App\Http\Controllers\Controller;
use App\Models\FraudFlag;
use App\Services\FraudDetectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FraudController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(FraudFlagStatus::class)]])['status'] ?? FraudFlagStatus::Open->value;

        return view('admin.fraud.index', [
            'flags' => FraudFlag::query()->with('user', 'reviewer')->where('status', $status)->latest()->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function update(Request $request, FraudFlag $flag, FraudDetectionService $fraud): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', Rule::in(['confirmed', 'dismissed'])]])['status'];
        $fraud->review($flag, $request->user(), FraudFlagStatus::from($status));

        return back()->with('success', 'Flag '.$status.'.');
    }
}

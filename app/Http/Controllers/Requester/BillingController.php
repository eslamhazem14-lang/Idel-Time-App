<?php

namespace App\Http\Controllers\Requester;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Services\DepositService;
use App\Services\WalletService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request, WalletService $wallets): View
    {
        $wallet = $wallets->walletFor($request->user());

        return view('requester.billing', [
            'wallet' => $wallet,
            'transactions' => $wallet->transactions()->where('bucket', 'available')->latest('id')->paginate(15),
            'deposits' => $request->user()->deposits()->latest()->limit(10)->get(),
            'methods' => PaymentMethod::cases(),
            'minimum' => settings()->money('min_deposit'),
        ]);
    }

    public function deposit(Request $request, DepositService $deposits): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'decimal:0,2', 'min:1', 'max:100000'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        [, $result] = $deposits->request($request->user(), Money::of((string) $data['amount']), PaymentMethod::from($data['method']), $data['reference'] ?? null);

        return back()->with('success', $result->instructions ?? 'Deposit request received.');
    }
}

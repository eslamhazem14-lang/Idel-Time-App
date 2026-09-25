<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawalRequest;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request, WalletService $wallets, WithdrawalService $withdrawals): View
    {
        $user = $request->user();
        $wallet = $wallets->walletFor($user);
        $bucket = $request->query('ledger') === 'pending' ? 'pending' : 'available';

        return view('developer.wallet', [
            'wallet' => $wallet,
            'bucket' => $bucket,
            'transactions' => $wallet->transactions()->where('bucket', $bucket)->latest('id')->paginate(15, pageName: 'tx')->withQueryString(),
            'withdrawals' => $user->withdrawals()->latest()->limit(10)->get(),
            'methods' => $withdrawals->enabledMethods(),
            'minimum' => settings()->money('min_withdrawal'),
        ]);
    }

    public function withdraw(WithdrawalRequest $request, WithdrawalService $withdrawals): RedirectResponse
    {
        $withdrawal = $withdrawals->request($request->user(), $request->amount(), $request->paymentMethod(), $request->validated('account_details'));

        return back()->with('success', "Withdrawal of {$withdrawal->amount->format()} requested. We'll email you when it is processed.");
    }
}

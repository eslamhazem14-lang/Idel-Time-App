<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    public function show(Request $request, WalletService $wallets): WalletResource
    {
        return new WalletResource($wallets->walletFor($request->user()));
    }

    public function transactions(Request $request, WalletService $wallets): AnonymousResourceCollection
    {
        $bucket = $request->validate(['bucket' => ['nullable', Rule::in(['available', 'pending'])]])['bucket'] ?? null;

        return TransactionResource::collection(
            $wallets->walletFor($request->user())->transactions()
                ->when($bucket, fn ($q) => $q->where('bucket', $bucket))
                ->latest('id')->paginate(25)
        );
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawalRequest;
use App\Http\Resources\WithdrawalResource;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WithdrawalController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return WithdrawalResource::collection($request->user()->withdrawals()->latest()->paginate(20));
    }

    public function store(WithdrawalRequest $request, WithdrawalService $withdrawals): JsonResponse
    {
        $withdrawal = $withdrawals->request($request->user(), $request->amount(), $request->paymentMethod(), $request->validated('account_details'));

        return (new WithdrawalResource($withdrawal))->response()->setStatusCode(201);
    }
}

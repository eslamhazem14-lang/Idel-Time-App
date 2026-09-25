<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\TaskCategory;
use App\TaskTypes\TaskTypeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MetaController extends Controller
{
    public function categories(): AnonymousResourceCollection
    {
        return CategoryResource::collection(TaskCategory::query()->active()->get());
    }

    /** Public platform parameters useful to clients (no secrets). */
    public function config(TaskTypeRegistry $types): JsonResponse
    {
        return response()->json([
            'currency' => config('platform.currency'),
            'commission_percent' => settings()->commissionPercent(),
            'min_withdrawal' => settings()->money('min_withdrawal')->toDecimal(),
            'max_task_minutes' => (int) settings('max_task_minutes'),
            'claim_grace_seconds' => (int) settings('claim_grace_seconds'),
            'task_types' => collect($types->all())->map(fn ($t) => ['key' => $t->key(), 'label' => $t->label(), 'description' => $t->description()])->values(),
            'maintenance' => (bool) settings('maintenance_mode'),
        ]);
    }
}

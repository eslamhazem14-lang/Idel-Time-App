<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['action' => ['nullable', 'string', 'max:80'], 'user' => ['nullable', 'integer']]);

        return view('admin.activity', [
            'logs' => ActivityLog::query()->with('user')
                ->when($filters['action'] ?? null, fn ($q, $a) => $q->where('action', 'like', $a.'%'))
                ->when($filters['user'] ?? null, fn ($q, $u) => $q->where('user_id', $u))
                ->latest('id')->paginate(50)->withQueryString(),
            'filters' => $filters,
        ]);
    }
}

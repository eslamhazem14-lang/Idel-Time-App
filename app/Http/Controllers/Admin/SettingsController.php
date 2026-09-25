<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\SettingsService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(SettingsService $settings): View
    {
        return view('admin.settings', ['settings' => $settings->all(), 'methods' => PaymentMethod::cases()]);
    }

    public function update(Request $request, SettingsService $settings, ActivityLogger $logger): RedirectResponse
    {
        $money = ['required', 'decimal:0,2', 'min:0'];
        $data = $request->validate([
            'commission_percent' => ['required', 'decimal:0,2', 'min:0', 'max:100'],
            'min_reward' => [...$money, 'min:0.01'],
            'max_reward' => [...$money, 'gte:min_reward'],
            'min_task_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'max_task_minutes' => ['required', 'integer', 'gte:min_task_minutes', 'max:120'],
            'claim_duration_multiplier' => ['required', 'numeric', 'min:1', 'max:10'],
            'claim_min_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'claim_grace_seconds' => ['required', 'integer', 'min:0', 'max:3600'],
            'expired_claim_policy' => ['required', Rule::in(['expire', 'submit_draft'])],
            'max_active_claims' => ['required', 'integer', 'min:1', 'max:10'],
            'expiry_warning_minutes' => ['required', 'integer', 'min:1', 'max:30'],
            'auto_approve_days' => ['required', 'integer', 'min:0', 'max:60'],
            'min_withdrawal' => [...$money, 'min:1'],
            'min_deposit' => [...$money, 'min:1'],
            'withdrawal_methods' => ['required', 'array', 'min:1'],
            'withdrawal_methods.*' => [Rule::enum(PaymentMethod::class)],
            'maintenance_message' => ['required', 'string', 'max:500'],
        ]);

        foreach (['commission_percent', 'min_reward', 'max_reward', 'min_withdrawal', 'min_deposit'] as $key) {
            $data[$key] = Money::of((string) $data[$key])->toDecimal();
        }
        foreach (['min_task_minutes', 'max_task_minutes', 'claim_min_minutes', 'claim_grace_seconds', 'max_active_claims', 'expiry_warning_minutes', 'auto_approve_days'] as $key) {
            $data[$key] = (int) $data[$key];
        }
        $data['claim_duration_multiplier'] = (string) $data['claim_duration_multiplier'];
        $data['withdrawal_methods'] = array_values($data['withdrawal_methods']);
        $data['maintenance_mode'] = $request->boolean('maintenance_mode');
        $data['require_email_verification'] = $request->boolean('require_email_verification');

        $before = $settings->all();
        $settings->setMany($data);
        $logger->log('settings.updated', null, ['changed' => array_keys(array_diff_assoc(array_map('json_encode', $data), array_map('json_encode', array_intersect_key($before, $data))))]);

        return back()->with('success', 'Settings saved. New commission rates apply to tasks created from now on.');
    }
}

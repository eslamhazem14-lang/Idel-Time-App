<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.edit', ['user' => $request->user()->load('developerProfile')]);
    }

    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'timezone' => ['required', 'timezone:all'],
            'skills' => ['nullable', 'string', 'max:300'],
            'github_url' => ['nullable', 'url:https', 'max:255'],
            'portfolio_url' => ['nullable', 'url:http,https', 'max:255'],
            'linkedin_url' => ['nullable', 'url:https', 'max:255'],
            'languages' => ['nullable', 'string', 'max:200'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
        ]);

        $skills = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) ($data['skills'] ?? ''))))));
        $user->fill([
            'name' => $data['name'],
            'bio' => $data['bio'] ?? null,
            'country' => isset($data['country']) ? strtoupper($data['country']) : null,
            'timezone' => $data['timezone'],
            'skills' => array_slice($skills, 0, 20),
        ])->save();

        if ($user->isDeveloper()) {
            $user->developerProfile()->updateOrCreate([], [
                'github_url' => $data['github_url'] ?? null,
                'portfolio_url' => $data['portfolio_url'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'skills' => array_slice($skills, 0, 20),
                'languages' => array_values(array_filter(array_map('trim', explode(',', (string) ($data['languages'] ?? ''))))),
                'experience_years' => (int) ($data['experience_years'] ?? 0),
            ]);
        }
        $logger->log('user.profile_updated', $user);

        return back()->with('success', 'Profile saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $request->user()->forceFill(['password' => $request->input('password')])->save();
        $request->session()->regenerate();

        return back()->with('success', 'Password updated.');
    }
}

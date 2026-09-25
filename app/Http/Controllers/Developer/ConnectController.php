<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Setup page for the Claude Code hook (and other AI-agent integrations). */
class ConnectController extends Controller
{
    public const TOKEN_NAME = 'claude-code-hook';

    public function index(Request $request): View
    {
        return view('developer.connect', [
            'token' => $request->session()->get('hook_token'),
            'existing' => $request->user()->tokens()->where('name', self::TOKEN_NAME)->latest()->first(),
            'baseUrl' => rtrim(config('app.url'), '/'),
        ]);
    }

    public function token(Request $request): RedirectResponse
    {
        $user = $request->user();
        // One hook token per account; generating a new one revokes the old one.
        $user->tokens()->where('name', self::TOKEN_NAME)->delete();
        $token = $user->createToken(self::TOKEN_NAME, ['idle:write', 'tasks:read'], now()->addYear())->plainTextToken;

        return redirect()->route('developer.connect')
            ->with('hook_token', $token)
            ->with('success', 'New token created. Copy it now — it will not be shown again.');
    }

    public function revoke(Request $request): RedirectResponse
    {
        $request->user()->tokens()->where('name', self::TOKEN_NAME)->delete();

        return redirect()->route('developer.connect')->with('success', 'Token revoked. The hook will stop working until you create a new one.');
    }
}

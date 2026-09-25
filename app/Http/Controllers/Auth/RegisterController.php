<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.register', ['role' => in_array($request->query('as'), ['developer', 'requester'], true) ? $request->query('as') : 'developer']);
    }

    public function store(RegisterRequest $request, UserService $users): RedirectResponse
    {
        $user = $users->register($request->validated(), $request->ip());
        Auth::login($user);
        $request->session()->regenerate();

        return $user->hasVerifiedEmail()
            ? redirect()->route($user->homeRoute())->with('success', 'Welcome aboard!')
            : redirect()->route('verification.notice');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Token abilities granted to API clients per role. */
    private const ABILITIES = [
        'developer' => ['tasks:read', 'tasks:work', 'wallet:read', 'wallet:withdraw'],
        'requester' => ['tasks:read', 'tasks:manage', 'submissions:review'],
        'admin' => ['*'],
    ];

    public function login(Request $request, UserService $users): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', strtolower($data['email']))->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }
        if ($user->isSuspended()) {
            return response()->json(['message' => 'Your account is suspended.', 'code' => 'suspended'], 403);
        }

        $users->recordLogin($user, $request->ip());

        return response()->json([
            'token' => $user->createToken($data['device_name'], self::ABILITIES[$user->role->value], now()->addDays(90))->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    public function register(RegisterRequest $request, UserService $users): JsonResponse
    {
        $request->validate(['device_name' => ['required', 'string', 'max:100']]);
        $user = $users->register($request->validated(), $request->ip());

        return response()->json([
            'token' => $user->createToken($request->input('device_name'), self::ABILITIES[$user->role->value], now()->addDays(90))->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}

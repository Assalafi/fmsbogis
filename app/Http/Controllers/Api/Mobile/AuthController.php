<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'code' => '401',
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'code' => '403',
                'message' => 'Your account is disabled. Contact the administrator.',
            ], 403);
        }

        $remember = ! empty($data['remember']);
        $expiresAt = $remember ? now()->addYear() : now()->addDays(7);

        $token = Str::random(64);

        MobileApiToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'device_name' => Str::limit($request->userAgent() ?? 'unknown', 120),
            'expires_at' => $expiresAt,
            'last_used_at' => now(),
        ]);

        $user->update(['last_login_at' => now()]);

        return response()->json([
            'code' => '00',
            'message' => 'Login successful.',
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $apiToken = $request->input('_mobile_token');

        if ($apiToken instanceof MobileApiToken) {
            $apiToken->delete();
        }

        return response()->json([
            'code' => '00',
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'code' => '00',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}

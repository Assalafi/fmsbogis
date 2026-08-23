<?php

namespace App\Http\Middleware;

use App\Models\MobileApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MobileAuthMiddleware
{
    /**
     * Authenticate the BOGIS mobile verifier app via its API token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return response()->json([
                'code' => '401',
                'message' => 'Authentication required.',
            ], 401);
        }

        $tokenHash = hash('sha256', $token);

        $apiToken = MobileApiToken::where('token_hash', $tokenHash)->first();

        if (! $apiToken) {
            return response()->json([
                'code' => '401',
                'message' => 'Invalid or expired session. Please sign in again.',
            ], 401);
        }

        if ($apiToken->isExpired()) {
            $apiToken->delete();

            return response()->json([
                'code' => '401',
                'message' => 'Session expired. Please sign in again.',
            ], 401);
        }

        $user = $apiToken->user;

        if (! $user || $user->status !== 'active') {
            $apiToken->delete();

            return response()->json([
                'code' => '403',
                'message' => 'Your account is disabled. Contact the administrator.',
            ], 403);
        }

        $apiToken->update(['last_used_at' => now()]);

        Auth::login($user);

        $request->merge(['_mobile_token' => $apiToken]);

        return $next($request);
    }
}

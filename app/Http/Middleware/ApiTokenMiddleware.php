<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    /**
     * Validate the shared API token used between BOGIS systems.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $expected = (string) config('services.external_receipts.api_token');

        if ($expected === '' || $token === null || ! hash_equals($expected, $token)) {
            return response()->json([
                'code' => '401',
                'message' => 'Unauthorized token',
            ], 401);
        }

        return $next($request);
    }
}

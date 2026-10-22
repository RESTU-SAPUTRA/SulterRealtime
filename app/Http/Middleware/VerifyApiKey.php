<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{

    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');
        $expectedKey = config('services.iot.api_key', env('IOT_API_KEY', 'kampus-iot-secret-key-2026'));

        if (!$apiKey || $apiKey !== $expectedKey) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized device. Invalid or missing X-API-KEY.',
            ], 401);
        }

        return $next($request);
    }
}

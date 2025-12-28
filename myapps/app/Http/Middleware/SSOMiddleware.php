<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SSOMiddleware
{
    /**
     * Handle an incoming request for SSO verification
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Token SSO diperlukan.',
                'message_en' => 'Unauthorized. SSO token required.',
            ], 401);
        }

        // Check if staff is active
        $staf = $request->user();
        if (!$staf->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Akaun anda tidak aktif.',
                'message_en' => 'Your account is inactive.',
            ], 403);
        }

        // Add staff info to request
        $request->merge([
            'authenticated_staf' => $staf,
        ]);

        return $next($request);
    }
}


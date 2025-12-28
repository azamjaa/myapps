<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Load loginRecord relationship
        $user = auth()->user();
        if (!$user->relationLoaded('loginRecord')) {
            $user->load('loginRecord');
        }

        // Check if user has admin role
        if ($request->is('admin*')) {
            if (!$user->isAdmin()) {
                // User biasa cuba akses admin panel
                abort(403, 'Access denied. Admin privileges required.');
            }
        }

        return $next($request);
    }
}


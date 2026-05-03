<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        if (!in_array($user->role ?? null, $roles)) {

            // 📝 logging
            Log::warning('Unauthorized access attempt', [
                'user_id' => $user->id,
                'role' => $user->role,
                'required_roles' => $roles,
                'route' => $request->path()
            ]);

            return response()->json([
                'error' => 'Unauthorized - role not allowed'
            ], 403);
        }

        return $next($request);
    }
}
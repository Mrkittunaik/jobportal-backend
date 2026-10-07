<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /** Usage: ->middleware('role:company,admin') */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        if (! $user) return ApiResponse::error('Unauthenticated', 401);
        if (! in_array($user->role, $roles, true)) {
            return ApiResponse::error('You do not have permission for this action', 403);
        }
        return $next($request);
    }
}

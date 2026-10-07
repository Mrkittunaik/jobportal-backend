<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class EnsureUserActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && ! $user->isActive()) {
            $user->currentAccessToken()?->delete();
            return ApiResponse::error('Your account has been blocked. Contact support.', 403);
        }
        return $next($request);
    }
}

<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (! $request->auth) { return response()->json(['message' => 'Unauthorized: Authentication required.'], 401); }
        if (! $request->auth->hasRole($roles)) { return response()->json(['message' => 'Forbidden: You do not have the necessary role(s) to access this resource.'], 403); }
        return $next($request);
    }
}
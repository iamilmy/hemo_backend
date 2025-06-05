<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');
        if (!$token) { return response()->json(['message' => 'Token not provided.'], 401); }
        $token = str_replace('Bearer ', '', $token);
        try {
            $credentials = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            $user = User::withTrashed()->find($credentials->sub);
            if (!$user) { return response()->json(['message' => 'Unauthorized user.'], 401); }
            $request->auth = $user;
            $request->user = $user;
        } catch (\Firebase\JWT\ExpiredException $e) { return response()->json(['message' => 'Provided token is expired.'], 401); }
        catch (\Firebase\JWT\SignatureInvalidException $e) { return response()->json(['message' => 'Provided token is invalid.'], 401); }
        catch (\Exception $e) { return response()->json(['message' => 'An error occurred while decoding token: ' . $e->getMessage()], 401); }
        return $next($request);
    }
}
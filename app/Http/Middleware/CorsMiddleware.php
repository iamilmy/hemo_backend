<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        $allowOrigin = 'http://hemo.local'; // <<< Sesuaikan dengan frontend baru
        $allowMethods = 'POST, GET, OPTIONS, PUT, DELETE';
        $allowHeaders = 'Content-Type, X-Auth-Token, Origin, Authorization';

        if ($request->isMethod('OPTIONS')) {
            	return response('', 204)
		        ->header('Access-Control-Allow-Origin', $allowOrigin)
				->header('Access-Control-Allow-Methods', $allowMethods)
				->header('Access-Control-Allow-Headers', $allowHeaders)
				->header('Access-Control-Allow-Credentials', 'true')
				->header('Access-Control-Max-Age', '86400');
		}

            $response = $next($request);
            $response->header('Access-Control-Allow-Origin', $allowOrigin);
            $response->header('Access-Control-Allow-Methods', $allowMethods);
            $response->header('Access-Control-Allow-Headers', $allowHeaders);
            $response->header('Access-Control-Allow-Credentials', 'true');
            return $response;
        }
    }
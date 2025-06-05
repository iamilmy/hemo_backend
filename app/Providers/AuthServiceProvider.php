<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Auth\JwtUserProvider; // Pastikan ini diimpor
use App\Models\User; // Pastikan ini diimpor jika Anda menggunakan EloquentUserProvider

use Firebase\JWT\JWT;
use Firebase\JWT\Key; // Impor kelas JWT dan Key

class AuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Bind the AuthManager to resolve the 'jwt' driver
        Auth::extend('jwt', function ($app, $name, array $config) {
            // Get the user provider defined for this guard (e.g., 'users')
            $providerConfig = $app['config']['auth.providers.' . $config['provider']];

            if (!isset($providerConfig['model'])) {
                throw new \InvalidArgumentException("Model not specified for authentication provider [{$config['provider']}]. Check your config/auth.php.");
            }

            // Return an instance of the JwtGuard directly here
            // This is the key: we define the 'guard' itself, not just the provider
            // The JwtGuard will use the JwtUserProvider to retrieve the user
            return new \App\Auth\JwtGuard( // <<< INI YANG BERBEDA
                $app['hash'],
                new JwtUserProvider($app['hash'], $providerConfig['model']), // Pass your custom User Provider
                $app['request'], // Pass the request instance
                env('JWT_SECRET'), // Get JWT_SECRET from .env
                'HS256' // Algorithm
            );
        });

        // The viaRequest method is now mostly redundant if Auth::extend defines the full guard
        // but can still be kept for clarity or specific edge cases.
        // However, the error suggests AuthManager is trying to get user from the provider
        // before the guard is fully set up via viaRequest.
        // If the above Auth::extend fix works, this viaRequest might become optional
        // or need slight adjustment if you still want to explicitly use it.
        // For now, leave it as is, the Auth::extend fix is more critical.
        $this->app['auth']->viaRequest('api', function ($request) {
            // This part should technically be handled by your JwtGuard now
            // if it's set up correctly.
            // However, if $request->auth is still being set by AuthMiddleware,
            // this can act as a fallback.
            if ($request->auth) {
                return $request->auth;
            }
            return null;
        });
    }
}
<?php

require_once __DIR__.'/../vendor/autoload.php';

// --- TAMBAHKAN DEFINISI HELPER public_path() JIKA BELUM ADA ---
if (! function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * @param  string  $path
     * @return string
     */
    function public_path($path = '')
    {
        return app()->basePath('public').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}
// --- AKHIR DEFINISI HELPER ---


(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

// Pastikan env ter-load di semua context (middleware, dsb)
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();


$app->withFacades(); // <-- Harus aktif

$app->withEloquent(); // Harus aktif

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
*/

$app->configure('app');
$app->configure('auth');
$app->configure('logging'); // <-- PASTIKAN BARIS INI ADA
$app->configure('filesystems'); // <-- PASTIKAN BARIS INI ADA

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
*/

// Global Middleware
$app->middleware([
    App\Http\Middleware\CorsMiddleware::class,
]);

// Route Middleware
$app->routeMiddleware([
    'auth' => App\Http\Middleware\AuthMiddleware::class,
    'role' => App\Http\Middleware\RoleMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
*/

// $app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);


// --- TAMBAHKAN/PASTIKAN SERVICE PROVIDER FILESYSTEM DAN BINDINGS INI ADA ---
$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);

// Ini penting agar Lumen tahu bagaimana menginisialisasi driver filesystem
$app->bind('filesystem.disk', function ($app, $parameters) {
    return $app['filesystem']->disk($parameters[0]);
});

// Ini adalah binding yang hilang untuk Flysystem v3 VisibilityConverter
$app->singleton(\League\Flysystem\UnixVisibility\PortableVisibilityConverter::class, function () {
    return new \League\Flysystem\UnixVisibility\PortableVisibilityConverter();
});
// --- AKHIR TAMBAHAN PENTING ---


/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
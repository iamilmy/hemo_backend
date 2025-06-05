<?php
/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');

    $router->group(['middleware' => 'auth'], function () use ($router) {
        $router->get('profile', 'AuthController@profile');
        $router->get('sidebar-menu', 'MenuController@getSidebarMenu');

        $router->group(['middleware' => 'role:super_admin'], function () use ($router) {
            $router->get('users', 'UserController@index');
            $router->put('users/{id}/role', 'UserController@update'); // Update user juga bisa ganti role
            $router->delete('users/{id}', 'UserController@destroy');
            $router->post('users/{id}/restore', 'UserController@restore');
            $router->post('users', 'UserController@store'); // Super Admin bisa create user baru

            $router->get('roles', 'RoleController@index');
            $router->post('roles', 'RoleController@store');
            $router->get('roles/{id}', 'RoleController@show');
            $router->put('roles/{id}', 'RoleController@update');
            $router->delete('roles/{id}', 'RoleController@destroy');

            $router->get('menus', 'MenuController@index');
            $router->post('menus', 'MenuController@store');
            $router->get('menus/{id}', 'MenuController@show');
            $router->put('menus/{id}', 'MenuController@update');
            $router->delete('menus/{id}', 'MenuController@destroy');
        });

        // ... rute untuk role lain (admin, dokter, pasien)
        $router->put('change-password', 'UserController@changePassword'); // Perlu buat method ini di UserController
    });
});
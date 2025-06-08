<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');

    $router->group(['middleware' => 'auth'], function () use ($router) {
        $router->get('profile', 'AuthController@profile');
        $router->get('sidebar-menu', 'MenuController@getSidebarMenu');

        // --- Rute untuk Roles (SEKARANG DI LUAR GRUP SUPER_ADMIN) ---
        // Kita akan tambahkan middleware kustom jika diperlukan,
        // atau mengandalkan checks di controller.
        $router->get('roles', 'RoleController@index');
        $router->post('roles', 'RoleController@store');
        $router->get('roles/{id}', 'RoleController@show');
        $router->put('roles/{id}', 'RoleController@update');
        $router->delete('roles/{id}', 'RoleController@destroy');

        // --- Rute yang HANYA untuk Super Admin ---
        // (Pindahkan rute yang memang hanya untuk super_admin di sini)
        $router->group(['middleware' => 'role:super_admin'], function () use ($router) {
            $router->get('users', 'UserController@index');
            $router->put('users/{id}/role', 'UserController@update');
            $router->delete('users/{id}', 'UserController@destroy');
            $router->post('users/{id}/restore', 'UserController@restore');
            $router->post('users', 'UserController@store');

            // Rute lain yang memang eksklusif untuk super_admin
            $router->get('menus', 'MenuController@index');
            $router->post('menus', 'MenuController@store');
            $router->get('menus/{id}', 'MenuController@show');
            $router->put('menus/{id}', 'MenuController@update');
            $router->delete('menus/{id}', 'MenuController@destroy');

            $router->get('permissions/roles', 'PermissionController@getRolesForSelection');
            $router->get('permissions/menus/{roleId}', 'PermissionController@getMenuPermissionsByRole');
            $router->put('permissions/menus/{roleId}', 'PermissionController@updateMenuPermissions');
        });

        // ... rute untuk role lain (admin, dokter, pasien)
        $router->put('change-password', 'UserController@changePassword');
    });
});
<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');


    $router->get('/public/app-name', 'PublicController@appName');


 



    $router->group(['middleware' => 'auth'], function () use ($router) {
        $router->get('profile', 'AuthController@profile');
        $router->get('sidebar-menu', 'MenuController@getSidebarMenu');

        // --- Rute untuk Roles (Dilindungi per method di RoleController) ---
        $router->get('roles', 'RoleController@index');
        $router->post('roles', 'RoleController@store');
        $router->get('roles/{id}', 'RoleController@show');
        $router->put('roles/{id}', 'RoleController@update');
        $router->delete('roles/{id}', 'RoleController@destroy');

        // --- Rute untuk Users (Dilindungi per method di UserController) ---
        // INI ADALAH SATU-SATUNYA TEMPAT RUTE USERS HARUS ADA
        $router->get('users', 'UserController@index');
        $router->post('users', 'UserController@store');
        $router->get('users/{id}', 'UserController@show');
        $router->put('users/{id}', 'UserController@update');
        $router->delete('users/{id}', 'UserController@destroy'); // Soft delete
        $router->post('users/{id}/restore', 'UserController@restore'); // Restore user
        // $router->put('users/{id}/role', 'UserController@updateRole'); // Jika Anda mengaktifkan endpoint ini

        // Permissions Controller
        $router->get('permissions/roles', 'PermissionController@getRolesForSelection');

         // --- Rute untuk Pengaturan Aplikasi 
        $router->get('app-setting', 'AppSettingController@show');
        $router->post('app-setting', 'AppSettingController@update'); // Gunakan POST untuk form-data dengan file

        // --- Rute yang HANYA untuk Super Admin ---
        // Grup ini sekarang hanya berisi rute yang memang eksklusif untuk super_admin
        // dan tidak ingin dicek per method di controller atau tidak ada implementasi granularnya.
        $router->group(['middleware' => 'role:super_admin'], function () use ($router) {
            // Contoh: rute manajemen menus yang mungkin hanya untuk super_admin
            $router->get('menus', 'MenuController@index');
            $router->post('menus', 'MenuController@store');
            $router->get('menus/{id}', 'MenuController@show');
            $router->put('menus/{id}', 'MenuController@update');
            $router->delete('menus/{id}', 'MenuController@destroy');

           
            $router->get('permissions/menus/{roleId}', 'PermissionController@getMenuPermissionsByRole');
            $router->put('permissions/menus/{roleId}', 'PermissionController@updateMenuPermissions');
            // Jika ada rute user yang SANGAT SPESIFIK dan hanya bisa oleh super_admin,
            // barulah Anda bisa menambahkannya di sini. Tetapi untuk CRUD dasar users,
            // sebaiknya pakai cek granular di UserController.
        });

        // ... rute untuk role lain (admin, dokter, pasien)
        $router->put('change-password', 'UserController@changePassword');
    });
});
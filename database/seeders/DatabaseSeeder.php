<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UsersTableSeeder::class,    // Ini harus dijalankan duluan untuk membuat user
            RolesTableSeeder::class,    // Ini harus dijalankan setelah Users, untuk membuat role
            UserRoleTableSeeder::class, // Ini harus dijalankan setelah Users dan Roles, untuk mengaitkan
            MenusTableSeeder::class,    // Ini harus dijalankan setelah Roles, untuk membuat menu
            AppSettingsSeeder::class, // <-- TAMBAHKAN BARIS INI
            SuperAdminMenuPermissionsSeeder::class, // <-- TAMBAHKAN BARIS INI
        ]);
    }
}
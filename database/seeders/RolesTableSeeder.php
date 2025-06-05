<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('roles')->insert([
            ['name' => 'super_admin', 'display_name' => 'Super Administrator', 'description' => 'User with full administrative privileges', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'Manages general application settings and data', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'dokter', 'display_name' => 'Dokter', 'description' => 'Manages medical records and patient data', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'pasien', 'display_name' => 'Pasien', 'description' => 'Can view their own medical records', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
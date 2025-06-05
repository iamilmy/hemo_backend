<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon; // <<< PASTIKAN INI ADA

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            [
                'name' => 'Super Admin',
                'email' => 'super@admin.com',
                'password' => Hash::make('monakocakep'),
                //'role' => 'super_admin', // <<< PASTIKAN INI DIKOMENTARI ATAU DIHAPUS
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => Carbon::now(), // <<< PASTIKAN MENGGUNAKAN Carbon::now()
                'updated_at' => Carbon::now(), // <<< PASTIKAN MENGGUNAKAN Carbon::now()
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('monakocakep'),
                //'role' => 'admin',
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Dokter',
                'email' => 'dokter@dokter.com',
                'password' => Hash::make('monakocakep'),
                //'role' => 'dokter',
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Pasien',
                'email' => 'pasien@pasien.com',
                'password' => Hash::make('monakocakep'),
                //'role' => 'pasien',
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
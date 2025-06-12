<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Impor Facade DB
use Illuminate\Support\Carbon;

class AppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Pastikan tabel kosong sebelum menambahkan data
        // Ini mencegah duplikasi jika seeder dijalankan berkali-kali
        if (DB::table('app_settings')->count() === 0) {
            DB::table('app_settings')->insert([
                'application_name' => 'Hemodialysis SI', // Nama aplikasi default Anda
                'application_logo' => null, // Biarkan null atau path default jika ada
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } else {
            // Opsional: Jika tabel sudah ada isinya, Anda bisa update baris pertama
            // Ini relevan jika Anda hanya ingin memastikan application_name terisi
            DB::table('app_settings')->where('id', 1)->update([
                'application_name' => 'Hemodialysis SI',
                'updated_at' => Carbon::now(),
            ]);
        }

        // Anda bisa menghapus bagian insert dari migrasi sebelumnya
        // jika Anda memutuskan untuk hanya menggunakan seeder untuk mengisi data awal.
    }
}
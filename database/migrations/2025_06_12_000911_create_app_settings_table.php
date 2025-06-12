<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon; // <-- TAMBAHKAN IMPOR INI

class CreateAppSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id(); // Ini akan membuat kolom 'id' sebagai primary key auto-increment
            $table->string('application_name')->default('Your App Name'); // Nama aplikasi, dengan default value
            $table->string('application_logo')->nullable(); // Path ke logo aplikasi, bisa null
            $table->timestamps(); // Ini akan membuat kolom 'created_at' dan 'updated_at'
        });

        // Opsional: Isi satu baris data default setelah tabel dibuat
        // Karena Anda bilang isinya hanya 1 row, kita bisa langsung tambahkan di sini
        DB::table('app_settings')->insert([
            'application_name' => 'Hemodialysis SI', // Nama aplikasi default Anda
            'application_logo' => null, // Path logo default (misal: '/storage/logos/default_logo.png')
            'created_at' => Carbon::now(), // <-- PERBAIKAN DI SINI
            'updated_at' => Carbon::now(), // <-- PERBAIKAN DI SINI
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_settings');
    }
}
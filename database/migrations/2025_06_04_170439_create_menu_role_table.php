<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Jika Anda menyertakan DB::table()->insert() di sini
use Carbon\Carbon; // Jika Anda menyertakan Carbon::now() di sini

class CreateMenuRoleTable extends Migration
{
    public function up()
    {
        Schema::create('menu_role', function (Blueprint $table) {
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->primary(['menu_id', 'role_id']);
            $table->timestamps();
            // HAPUS BARIS-BARIS BERIKUT DARI SINI:
            // $table->boolean('can_view')->default(false)->comment('Can view this menu item or access its associated page');
            // $table->boolean('can_read')->default(false)->comment('Can read/view data within this menu context');
            // $table->boolean('can_create')->default(false)->comment('Can create/insert new data within this menu context');
            // $table->boolean('can_update')->default(false)->comment('Can update/edit data within this menu context');
            // $table->boolean('can_delete')->default(false)->comment('Can delete data within this menu context');
        });

        // Jika Anda menambahkan DB::table()->insert() di migrasi ini, pastikan juga tidak ada kolom izin di sana
        // (Disarankan untuk memindahkan ini ke seeder sepenuhnya)
        // Contohnya (jika dipertahankan di migrasi ini):
        // DB::table('menu_role')->insert([
        //     'menu_id' => 1, 'role_id' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        //     // Tidak ada can_view, can_read, dll. di sini
        // ]);
    }

    public function down()
    {
        Schema::dropIfExists('menu_role');
    }
}
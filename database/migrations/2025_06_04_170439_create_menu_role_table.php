<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_role', function (Blueprint $table) {
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->primary(['menu_id', 'role_id']);

            // Menambahkan kolom-kolom untuk hak akses spesifik
            $table->boolean('can_view')->default(false)->comment('Can view this menu item or access its associated page');
            $table->boolean('can_read')->default(false)->comment('Can read/view data within this menu context');
            $table->boolean('can_create')->default(false)->comment('Can create/insert new data within this menu context');
            $table->boolean('can_update')->default(false)->comment('Can update/edit data within this menu context');
            $table->boolean('can_delete')->default(false)->comment('Can delete data within this menu context');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_role');
    }
}
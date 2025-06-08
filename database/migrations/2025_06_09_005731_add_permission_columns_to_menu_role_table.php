<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPermissionColumnsToMenuRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('menu_role', function (Blueprint $table) {
            // Menambahkan kolom-kolom baru
            $table->boolean('can_view')->default(false)->after('role_id')->comment('Can view this menu item or access its associated page');
            $table->boolean('can_read')->default(false)->after('can_view')->comment('Can read/view data within this menu context');
            $table->boolean('can_create')->default(false)->after('can_read')->comment('Can create/insert new data within this menu context');
            $table->boolean('can_update')->default(false)->after('can_create')->comment('Can update/edit data within this menu context');
            $table->boolean('can_delete')->default(false)->after('can_update')->comment('Can delete data within this menu context');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('menu_role', function (Blueprint $table) {
            // Menghapus kolom jika migrasi di-rollback
            $table->dropColumn(['can_view', 'can_read', 'can_create', 'can_update', 'can_delete']);
        });
    }
}
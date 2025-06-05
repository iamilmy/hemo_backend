<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenusTable extends Migration
{
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('label'); // Teks yang ditampilkan di menu
            $table->string('icon')->nullable(); // Nama ikon (misal: mdiMonitor)
            $table->string('path'); // Path frontend (misal: /dashboard, /users-management)
            $table->integer('order')->default(0); // Urutan menu
            $table->boolean('is_main_menu')->default(true); // Untuk membedakan menu utama atau submenu
            $table->foreignId('parent_id')->nullable()->constrained('menus')->onDelete('cascade'); // Untuk submenu

            $table->boolean('is_logout')->default(false); // <<< TAMBAHKAN BARIS INI

            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('menus');
    }
}
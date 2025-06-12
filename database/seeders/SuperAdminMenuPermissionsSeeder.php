<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Role; // Impor model Role
use App\Models\Menu; // Impor model Menu
use Illuminate\Support\Carbon;

class SuperAdminMenuPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Temukan peran Super Admin
        $superAdminRole = Role::where('name', 'super_admin')->first();

        // Temukan semua menu
        $allMenus = Menu::all();

        // Pastikan Super Admin role dan ada menu
        if ($superAdminRole && $allMenus->isNotEmpty()) {
            $pivotData = [];
            foreach ($allMenus as $menu) {
                // Berikan semua hak akses (can_view, can_read, create, update, delete)
                // untuk peran super_admin pada setiap menu.
                $pivotData[$menu->id] = [
                    'can_view' => true,
                    'can_read' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                    'created_at' => Carbon::now(), // Gunakan now() atau Carbon::now()
                    'updated_at' => Carbon::now(), // Gunakan now() atau Carbon::now()
                ];
            }

            // Gunakan sync untuk memastikan hanya hak akses yang didefinisikan di sini
            // yang ada untuk role_id ini. Ini akan menghapus entri lama jika ada
            // atau menambahkan/memperbarui yang baru.
            $superAdminRole->menus()->sync($pivotData);

            $this->command->info('Super Admin role has been granted all permissions to all menus.');
        } else {
            $this->command->warn('Super Admin role not found or no menus found. Skipping SuperAdminMenuPermissionsSeeder.');
        }
    }
}
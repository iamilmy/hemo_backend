<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Role;
use App\Models\Menu;
// Tambahkan ikon baru yang Anda gunakan di seeder ini
// Misalnya mdiCog, mdiLock, mdiShieldAccount, mdiMenu, mdiKey, mdiSettings, mdiLogout
// Jika tidak diimpor, frontend tidak bisa merender ikonnya
// Pastikan Anda mengimpor ikon yang benar di frontend (src/menuAside.js) juga

class MenusTableSeeder extends Seeder
{
    public function run()
    {
    	 // Tambahkan ini di awal metode run()
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $superAdminRole = Role::where('name', 'super_admin')->first();
        // Ambil juga role lainnya jika Anda ingin mengaitkan menu lain di masa depan
        $adminRole = Role::where('name', 'admin')->first();
        $dokterRole = Role::where('name', 'dokter')->first();
        $pasienRole = Role::where('name', 'pasien')->first();

        // Clear existing menus for re-seeding
        DB::table('menus')->truncate();
        DB::table('menu_role')->truncate();

        // --- Main Menus ---
        $dashboardMenu = Menu::create([
            'label' => 'Dashboard',
            'icon' => 'mdiMonitor',
            'path' => '/',
            'order' => 10,
            'is_main_menu' => true,
            'parent_id' => null,
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now()
        ]);

        // Menu "Super Admin" (Parent Menu)
        $superAdminMenu = Menu::create([
            'label' => 'Super Admin',
            'icon' => 'mdiCog', // Contoh ikon untuk administrasi
            'path' => '#', // Path bisa '#' jika hanya sebagai parent/dropdown
            'order' => 20,
            'is_main_menu' => true,
            'parent_id' => null,
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now()
        ]);

        // Sub-menu di bawah "Super Admin"
        $roleManagementMenu = Menu::create([
            'label' => 'Role',
            'icon' => 'mdiLock', // Contoh ikon
            'path' => '/roles-management',
            'order' => 21,
            'is_main_menu' => false,
            'parent_id' => $superAdminMenu->id, // Parent adalah menu Super Admin
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now()
        ]);

        $userManagementMenu = Menu::create([
            'label' => 'User',
            'icon' => 'mdiShieldAccount',
            'path' => '/users-management',
            'order' => 22,
            'is_main_menu' => false,
            'parent_id' => $superAdminMenu->id,
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now()
        ]);

        $menuManagement = Menu::create([
            'label' => 'Menu',
            'icon' => 'mdiMenu', // Contoh ikon
            'path' => '/menus-management',
            'order' => 23,
            'is_main_menu' => false,
            'parent_id' => $superAdminMenu->id,
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now()
        ]);

        $hakAksesMenu = Menu::create([
            'label' => 'Hak Akses',
            'icon' => 'mdiKey',
            'path' => '/hak-akses',
            'order' => 24,
            'is_main_menu' => false,
            'parent_id' => $superAdminMenu->id,
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now()
        ]);

        $appSettingMenu = Menu::create([
            'label' => 'App Setting',
            'icon' => 'mdiSettings',
            'path' => '/app-setting',
            'order' => 25,
            'is_main_menu' => false,
            'parent_id' => $superAdminMenu->id,
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now()
        ]);

        // Tambahkan item Logout ini di sini
        $logoutMenu = Menu::create([
            'label' => 'Logout',
            'icon' => 'mdiLogout', // Ikon Logout
            'path' => '#', // Path bisa '#' untuk ditangani di frontend
            'order' => 100, // Biasanya di paling bawah
            'is_main_menu' => true,
            'parent_id' => null,
            'is_logout' => true, // Properti khusus untuk frontend mengenali ini sebagai tombol Logout
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now()
        ]);


        // --- Attach Roles to Menus ---
        // Dashboard: Semua bisa akses
        $dashboardMenu->roles()->attach([$superAdminRole->id, $adminRole->id, $dokterRole->id, $pasienRole->id]);

        // Super Admin dan submenunya : Hanya Super Admin
        $superAdminMenu->roles()->attach($superAdminRole->id);
        $roleManagementMenu->roles()->attach($superAdminRole->id);
        $userManagementMenu->roles()->attach($superAdminRole->id);
        $menuManagement->roles()->attach($superAdminRole->id);
        $hakAksesMenu->roles()->attach($superAdminRole->id);
        $appSettingMenu->roles()->attach($superAdminRole->id);
        // Logout: Semua yang login
        $logoutMenu->roles()->attach([$superAdminRole->id, $adminRole->id, $dokterRole->id, $pasienRole->id]);

         // Tambahkan ini di akhir metode run()
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
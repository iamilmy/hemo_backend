<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Role;

class UserRoleTableSeeder extends Seeder
{
    public function run()
    {
        $superAdminUser = User::where('email', 'super@admin.com')->first();
        $adminUser = User::where('email', 'admin@admin.com')->first();
        $dokterUser = User::where('email', 'dokter@dokter.com')->first();
        $pasienUser = User::where('email', 'pasien@pasien.com')->first();

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $dokterRole = Role::where('name', 'dokter')->first();
        $pasienRole = Role::where('name', 'pasien')->first();

        if ($superAdminUser && $superAdminRole) {
            $superAdminUser->roles()->attach($superAdminRole->id);
        }
        if ($adminUser && $adminRole) {
            $adminUser->roles()->attach($adminRole->id);
        }
        if ($dokterUser && $dokterRole) {
            $dokterUser->roles()->attach($dokterRole->id);
        }
        if ($pasienUser && $pasienRole) {
            $pasienUser->roles()->attach($pasienRole->id);
        }
    }
}
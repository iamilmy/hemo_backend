<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function __construct()
    {
        // Pastikan user terautentikasi dan memiliki peran 'super_admin' untuk mengakses fitur ini
       // $this->middleware('auth');
       // $this->middleware('role:super_admin'); // Middleware untuk membatasi akses hanya ke super_admin
    }

    /**
     * Get a list of all roles (for the dropdown).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRolesForSelection()
    {
        // Hanya ambil 'id', 'name', 'display_name' untuk dropdown
        $roles = Role::select('id', 'name', 'display_name')->orderBy('display_name')->get();
        return response()->json($roles);
    }

    /**
     * Get all menus with their associated permissions for a specific role.
     *
     * @param  int  $roleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMenuPermissionsByRole($roleId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json(['message' => 'Peran tidak ditemukan.'], 404);
        }

        // Ambil semua menu utama (is_main_menu = 1) dan anak-anaknya,
        // lalu eager load relasi 'roles' dengan pivot untuk peran yang sedang dipilih.
        // Ini akan mengembalikan semua menu dengan status hak aksesnya untuk peran tsb.
        $menus = Menu::whereNull('parent_id') // Filter hanya menu utama
                      ->with(['children' => function($query) use ($roleId) {
                          $query->with(['roles' => function($q) use ($roleId) {
                                  $q->where('role_id', $roleId)
                                    ->withPivot('can_view', 'can_read', 'can_create', 'can_update', 'can_delete');
                              }])
                              ->orderBy('order');
                      }])
                      ->with(['roles' => function($query) use ($roleId) {
                          $query->where('role_id', $roleId)
                                ->withPivot('can_view', 'can_read', 'can_create', 'can_update', 'can_delete');
                      }])
                      ->orderBy('order')
                      ->get();

        // Format ulang data agar lebih mudah digunakan di frontend
        $formattedMenus = $this->formatMenuPermissions($menus, $roleId);

        return response()->json([
            'role' => $role,
            'menus' => $formattedMenus
        ]);
    }

    /**
     * Helper function to recursively format menu permissions.
     */
    protected function formatMenuPermissions($menus, $roleId)
    {
        $result = [];
        foreach ($menus as $menu) {
            $permission = $menu->roles->first(function($role) use ($roleId) {
                return $role->id == $roleId;
            })->pivot ?? null; // Dapatkan pivot data, jika tidak ada, null

            $item = [
                'id' => $menu->id,
                'label' => $menu->label,
                'parent_id' => $menu->parent_id,
                'can_view' => $permission ? (bool)$permission->can_view : false,
                'can_read' => $permission ? (bool)$permission->can_read : false,
                'can_create' => $permission ? (bool)$permission->can_create : false,
                'can_update' => $permission ? (bool)$permission->can_update : false,
                'can_delete' => $permission ? (bool)$permission->can_delete : false,
                'children' => []
            ];

            if ($menu->children->isNotEmpty()) {
                $item['children'] = $this->formatMenuPermissions($menu->children, $roleId);
            }

            $result[] = $item;
        }
        return $result;
    }


    /**
     * Update permissions for a specific role and menus.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $roleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMenuPermissions(Request $request, $roleId)
    {
        try {
            $role = Role::find($roleId);

            if (!$role) {
                return response()->json(['message' => 'Peran tidak ditemukan.'], 404);
            }

            // Validasi input
            $this->validate($request, [
                'permissions' => 'required|array',
                'permissions.*.menu_id' => 'required|exists:menus,id',
                'permissions.*.can_view' => 'required|boolean',
                'permissions.*.can_read' => 'required|boolean',
                'permissions.*.can_create' => 'required|boolean',
                'permissions.*.can_update' => 'required|boolean',
                'permissions.*.can_delete' => 'required|boolean',
            ]);

            DB::beginTransaction();

            foreach ($request->input('permissions') as $permissionData) {
                $menuId = $permissionData['menu_id'];

                // Dapatkan data pivot atau buat baru jika belum ada
                $role->menus()->syncWithoutDetaching([
                    $menuId => [
                        'can_view' => $permissionData['can_view'],
                        'can_read' => $permissionData['can_read'],
                        'can_create' => $permissionData['can_create'],
                        'can_update' => $permissionData['can_update'],
                        'can_delete' => $permissionData['can_delete'],
                    ]
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Hak akses berhasil diperbarui!']);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validasi gagal!', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui hak akses!', 'error' => $e->getMessage()], 500);
        }
    }
}
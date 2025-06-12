<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\User;
use App\Models\Role;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MenuController extends Controller
{
    // Mendapatkan menu sidebar untuk frontend (hierarkis dan difilter berdasarkan peran)
    public function getSidebarMenu(Request $request)
    {
        /** @var User $user */
        $user = Auth::user()->load('roles');

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userRoleIds = $user->roles->pluck('id')->toArray();

        if (empty($userRoleIds)) {
            return response()->json([]);
        }

        $mainMenus = Menu::whereNull('parent_id')
                        // Eager load children dan pastikan mereka juga memenuhi kriteria peran dan can_view
                        ->with(['children' => function ($query) use ($userRoleIds) {
                            $query->whereHas('roles', function ($subQuery) use ($userRoleIds) {
                                $subQuery->whereIn('roles.id', $userRoleIds)
                                         // KONDISI UNTUK PIVOT PADA CHILDREN: Pastikan can_view TRUE
                                         ->where('menu_role.can_view', true); // <-- PERBAIKAN DI SINI (Gunakan nama tabel pivot.kolom)
                            })
                            ->with(['roles' => function($q) use ($userRoleIds) {
                                $q->whereIn('roles.id', $userRoleIds)
                                  ->withPivot('can_view', 'can_read', 'can_create', 'can_update', 'can_delete');
                            }])
                            ->orderBy('order');
                        }])
                        // Pastikan menu utama juga relevan dengan peran user dan memiliki can_view = true
                        ->whereHas('roles', function ($query) use ($userRoleIds) {
                            $query->whereIn('roles.id', $userRoleIds)
                                  // KONDISI UNTUK PIVOT PADA MENU UTAMA: Pastikan can_view TRUE
                                  ->where('menu_role.can_view', true); // <-- PERBAIKAN DI SINI (Gunakan nama tabel pivot.kolom)
                        })
                        // Eager load relasi roles untuk menu utama dengan pivot
                        ->with(['roles' => function($query) use ($userRoleIds) {
                            $query->whereIn('roles.id', $userRoleIds)
                                  ->withPivot('can_view', 'can_read', 'can_create', 'can_update', 'can_delete');
                        }])
                        ->orderBy('order')
                        ->get();

        $formattedMenus = $this->formatSidebarMenuRecursive($mainMenus, $userRoleIds);

        return response()->json($formattedMenus);
    }

    /**
     * Helper function to recursively format menus for the sidebar, applying can_view filter.
     *
     * @param \Illuminate\Database\Eloquent\Collection $menus
     * @param array $userRoleIds
     * @return array
     */
    protected function formatSidebarMenuRecursive($menus, $userRoleIds)
    {
        $result = [];
        foreach ($menus as $menu) {
            // Cek apakah menu ini memiliki can_view = true untuk setidaknya satu peran user yang login
            $hasViewPermission = $menu->roles->first(function($role) use ($userRoleIds) {
                // Pastikan peran user adalah salah satu peran yang dimuat, dan memiliki pivot can_view = true
                return in_array($role->id, $userRoleIds) && (bool)$role->pivot->can_view;
            });

            // Jika menu ini tidak memiliki izin 'can_view', lewati
            // (Logika ini menjadi redundan jika `whereHas` di atas sudah memfilter dengan benar,
            // tapi tetap aman untuk berjaga-jaga jika ada relasi tidak lengkap)
            if (!$hasViewPermission) {
                continue;
            }

            $item = [
                'label' => $menu->label,
                'icon' => $menu->icon,
                'to' => ($menu->path !== '#' && $menu->path !== null) ? $menu->path : null,
                'href' => ($menu->path === '#') ? '#' : null,
                'isLogout' => (bool)$menu->is_logout,
            ];

            // Jika ada submenu (children)
            if ($menu->children->isNotEmpty()) {
                $childrenFormatted = $this->formatSidebarMenuRecursive($menu->children, $userRoleIds);
                if (!empty($childrenFormatted)) {
                    $item['menu'] = $childrenFormatted;
                    $item['to'] = null;
                    $item['href'] = null;
                } else {
                    // Jika menu induk memiliki anak tapi tidak ada anak yang visible,
                    // dan menu induk itu sendiri tidak memiliki tujuan navigasi,
                    // maka jangan tampilkan menu induk ini.
                    if ($item['to'] === null && $item['href'] === null) {
                        continue;
                    }
                }
            }

            // Tambahkan permissions spesifik yang relevan untuk user (opsional, jika Anda ingin gunakan di frontend)
            foreach ($menu->roles as $role) {
                if (in_array($role->id, $userRoleIds)) {
                    $item['permissions'] = [
                        'can_read' => (bool)$role->pivot->can_read,
                        'can_create' => (bool)$role->pivot->can_create,
                        'can_update' => (bool)$role->pivot->can_update,
                        'can_delete' => (bool)$role->pivot->can_delete,
                    ];
                    break;
                }
            }
            // Filter menu yang tidak memiliki tujuan (to/href) dan juga tidak memiliki submenu yang visible
            if ($item['to'] === null && $item['href'] === null && !isset($item['menu'])) {
                 continue;
            }

            $result[] = $item;
        }
        return $result;
    }


    /**
     * Display a listing of the menus.
     * Untuk manajemen menu, dengan relasi parent dan roles
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 100);
        $search = $request->input('search');
        $parentOnly = $request->input('is_main_menu');

        $menus = Menu::with('parent', 'roles');

        if ($search) {
            $menus->where(function($query) use ($search) {
                $query->where('label', 'like', '%' . $search . '%')
                      ->orWhere('path', 'like', '%' . $search . '%');
            });
        }

        if ($parentOnly === 'true') {
            $menus->whereNull('parent_id');
        }

        $menus->orderBy('order')->orderBy('label');

        $menus = $menus->paginate($perPage);

        return response()->json($menus);
    }

    /**
     * Store a newly created menu in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'label' => 'required|string|max:255',
                'icon' => 'nullable|string|max:255',
                'path' => 'nullable|string|max:255|unique:menus,path,NULL,id,path,!#',
                'order' => 'required|integer',
                'parent_id' => 'nullable|exists:menus,id',
                'is_logout' => 'boolean',
                'roles' => 'sometimes|array',
                'roles.*' => 'exists:roles,id',
            ]);

            DB::beginTransaction();

            $menu = new Menu;
            $menu->label = $request->input('label');
            $menu->icon = $request->input('icon');
            $menu->path = $request->input('path');
            $menu->order = $request->input('order');
            $menu->parent_id = $request->input('parent_id');
            $menu->is_main_menu = $request->input('parent_id') === null;
            $menu->is_logout = (bool)$request->input('is_logout', false);
            $menu->save();

            if ($request->has('roles')) {
                $pivotData = [];
                foreach ($request->input('roles') as $roleId) {
                    $pivotData[$roleId] = [
                        'can_view' => true,
                        'can_read' => true,
                        'can_create' => true,
                        'can_update' => true,
                        'can_delete' => true,
                    ];
                }
                $menu->roles()->sync($pivotData);
            } else {
                $defaultRoles = Role::whereIn('name', ['super_admin', 'admin'])->pluck('id');
                $pivotData = [];
                foreach ($defaultRoles as $roleId) {
                    $pivotData[$roleId] = [
                        'can_view' => true,
                        'can_read' => true,
                        'can_create' => true,
                        'can_update' => true,
                        'can_delete' => true,
                    ];
                }
                $menu->roles()->sync($pivotData);
            }

            DB::commit();

            return response()->json(['message' => 'Menu created successfully', 'menu' => $menu->load('parent', 'roles')], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Menu creation failed!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified menu.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $menu = Menu::with('parent', 'roles')->find($id);

        if (!$menu) {
            return response()->json(['message' => 'Menu not found'], 404);
        }

        return response()->json($menu);
    }

    /**
     * Update the specified menu in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $menu = Menu::find($id);

            if (!$menu) {
                return response()->json(['message' => 'Menu not found'], 404);
            }

            $this->validate($request, [
                'label' => 'required|string|max:255',
                'icon' => 'nullable|string|max:255',
                'path' => 'nullable|string|max:255|unique:menus,path,' . $menu->id,
                'order' => 'required|integer',
                'parent_id' => 'nullable|exists:menus,id',
                'is_logout' => 'boolean',
                'roles' => 'sometimes|array',
                'roles.*' => 'exists:roles,id',
            ]);

            DB::beginTransaction();

            $menu->label = $request->input('label');
            $menu->icon = $request->input('icon');
            $menu->path = $request->input('path');
            $menu->order = $request->input('order');
            $menu->parent_id = $request->input('parent_id');
            $menu->is_main_menu = $request->input('parent_id') === null;
            $menu->is_logout = (bool)$request->input('is_logout', false);
            $menu->save();

            if ($request->has('roles')) {
                $pivotData = [];
                foreach ($request->input('roles') as $roleId) {
                    $existingPivot = DB::table('menu_role')
                                        ->where('menu_id', $menu->id)
                                        ->where('role_id', $roleId)
                                        ->first();

                    $pivotData[$roleId] = [
                        'can_view' => $existingPivot ? (bool)$existingPivot->can_view : true,
                        'can_read' => $existingPivot ? (bool)$existingPivot->can_read : true,
                        'can_create' => $existingPivot ? (bool)$existingPivot->can_create : true,
                        'can_update' => $existingPivot ? (bool)$existingPivot->can_update : true,
                        'can_delete' => $existingPivot ? (bool)$existingPivot->can_delete : true,
                    ];
                }
                $menu->roles()->sync($pivotData);

            } else {
            }

            DB::commit();

            return response()->json(['message' => 'Menu updated successfully', 'menu' => $menu->load('parent', 'roles')]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Menu update failed!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified menu from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $menu = Menu::find($id);

            if (!$menu) {
                return response()->json(['message' => 'Menu not found'], 404);
            }

            if ($menu->children->isNotEmpty()) {
                return response()->json(['message' => 'Tidak dapat menghapus menu utama karena memiliki submenu yang terkait. Harap hapus submenu terlebih dahulu.'], 409);
            }

            $menu->roles()->detach();

            $menu->delete();

            DB::commit();

            return response()->json(['message' => 'Menu deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Menu deletion failed!', 'error' => $e->getMessage()], 500);
        }
    }
}
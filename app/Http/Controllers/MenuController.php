<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\User;
use App\Models\Role;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    // Mendapatkan menu sidebar untuk frontend (hierarkis dan difilter berdasarkan peran)
    public function getSidebarMenu(Request $request)
    {
        /** @var User $user */
        $user = $request->user()->load('roles'); // Pastikan relasi roles dimuat

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userRoleIds = $user->roles->pluck('id')->toArray();

        // Ambil hanya menu utama (parent_id is NULL) yang relevan dengan peran user
        $mainMenus = Menu::with(['children' => function ($query) use ($userRoleIds) {
                            // Load children yang juga relevan dengan peran user
                            $query->whereHas('roles', function ($subQuery) use ($userRoleIds) {
                                $subQuery->whereIn('roles.id', $userRoleIds);
                            })->orderBy('order');
                        }])
                        ->whereNull('parent_id') // Hanya menu level 0
                        ->whereHas('roles', function ($query) use ($userRoleIds) {
                            $query->whereIn('roles.id', $userRoleIds);
                        })
                        ->orderBy('order')
                        ->get();

        // Format menu untuk frontend (agar sesuai dengan struktur menuAside.js sebelumnya)
        $formattedMenus = $mainMenus->map(function ($menu) {
            $formatted = [
                'label' => $menu->label,
                'icon' => $menu->icon,
                'path' => $menu->path, // Gunakan 'path' sebagai 'href' di frontend
                'isLogout' => (bool)$menu->is_logout, // Gunakan kolom is_logout jika ada
                'meta' => [ // Untuk kesesuaian dengan router frontend meta
                    'requiresAuth' => true, // Semua menu ini memerlukan auth
                    // Roles akan difilter di backend, jadi tidak perlu di sini
                ]
            ];

            // Jika ada submenu (children) yang dimuat
            if ($menu->children->isNotEmpty()) {
                $formatted['menu'] = $menu->children->map(function ($child) {
                    return [
                        'label' => $child->label,
                        'icon' => $child->icon,
                        'path' => $child->path,
                        'isLogout' => (bool)$child->is_logout,
                        'meta' => ['requiresAuth' => true]
                    ];
                })->toArray();
            }
            return $formatted;
        });

        return response()->json($formattedMenus);
    }

    // Mendapatkan semua menu untuk manajemen (bisa dengan atau tanpa hierarki)
    public function index(Request $request)
    {
        // Untuk manajemen, biasanya menampilkan semua menu dalam format flat atau pagination
        $menus = Menu::with('parent', 'roles')->orderBy('order')->get(); // Muat parent dan roles untuk tampilan manajemen
        return response()->json($menus);
    }

    // Mendapatkan detail satu menu
    public function show($id)
    {
        $menu = Menu::with('roles', 'parent')->find($id); // Muat relasi roles dan parent

        if (!$menu) {
            return response()->json(['message' => 'Menu not found'], 404);
        }

        return response()->json($menu);
    }

    // Menyimpan menu baru
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'label' => 'required|string|max:255',
                'icon' => 'nullable|string|max:255',
                'path' => 'required|string|max:255',
                'order' => 'nullable|integer',
                'is_main_menu' => 'boolean',
                'parent_id' => 'nullable|exists:menus,id',
                'is_logout' => 'boolean', // Tambahkan validasi untuk is_logout
                'role_ids' => 'required|array|min:1', // ID peran yang bisa mengakses menu ini
                'role_ids.*' => 'exists:roles,id',
            ]);

            DB::beginTransaction();
            $menu = new Menu($request->except('role_ids')); // Except role_ids agar tidak masuk ke fillable langsung
            $menu->save();

            $menu->roles()->attach($request->input('role_ids')); // Kaitkan peran
            DB::commit();

            return response()->json(['message' => 'Menu created successfully', 'menu' => $menu], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create menu', 'error' => $e->getMessage()], 500);
        }
    }

    // Memperbarui menu
    public function update(Request $request, $id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json(['message' => 'Menu not found'], 404);
        }

        try {
            $this->validate($request, [
                'label' => 'required|string|max:255',
                'icon' => 'nullable|string|max:255',
                'path' => 'required|string|max:255',
                'order' => 'nullable|integer',
                'is_main_menu' => 'boolean',
                'parent_id' => 'nullable|exists:menus,id',
                'is_logout' => 'boolean', // Tambahkan validasi untuk is_logout
                'role_ids' => 'required|array|min:1',
                'role_ids.*' => 'exists:roles,id',
            ]);

            DB::beginTransaction();
            $menu->fill($request->except('role_ids'));
            $menu->save();

            $menu->roles()->sync($request->input('role_ids')); // Sync peran
            DB::commit();

            return response()->json(['message' => 'Menu updated successfully', 'menu' => $menu], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update menu', 'error' => $e->getMessage()], 500);
        }
    }

    // Menghapus menu (dan sub-menu-nya secara cascade karena onDelete('cascade') di migrasi)
    public function destroy($id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json(['message' => 'Menu not found'], 404);
        }

        try {
            DB::beginTransaction();
            $menu->delete();
            DB::commit();

            return response()->json(['message' => 'Menu deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete menu', 'error' => $e->getMessage()], 500);
        }
    }
}
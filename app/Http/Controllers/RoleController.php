<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Menu; // Impor model Menu jika belum
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // <-- Pastikan ini diimpor

class RoleController extends Controller
{
    public function __construct()
    {
        // Semua endpoint di controller ini membutuhkan autentikasi
        $this->middleware('auth');

        // TIDAK ADA LAGI MIDDLEWARE ROLE DI SINI.
        // Otorisasi granular akan dilakukan secara manual di setiap method
        // menggunakan permissions dari user yang terautentikasi.
    }

    /**
     * Display a listing of the roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Dapatkan user yang terautentikasi dan permissions-nya
        $user = Auth::user();
        // Memanggil getAllMenuPermissions() pada user untuk mendapatkan izin
        $permissions = $user->getAllMenuPermissions();
        // Dapatkan izin khusus untuk path '/roles'
        $rolesPermissions = $permissions['/roles'] ?? null;

        // Cek izin can_read untuk path /roles
        // Jika tidak ada izin atau can_read adalah false, kembalikan 403 Forbidden
        if (!isset($rolesPermissions['can_read']) || !$rolesPermissions['can_read']) {
            return response()->json(['message' => 'Forbidden: You do not have permission to read roles.'], 403);
        }

        // --- Logika yang sudah ada untuk mengambil daftar roles ---
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $roles = Role::with('menus');

        if ($search) {
            $roles->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('display_name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $roles->orderBy('display_name')->orderBy('name');
        $roles = $roles->paginate($perPage);

        return response()->json($roles);
    }

    /**
     * Store a newly created role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $permissions = $user->getAllMenuPermissions();
        $rolesPermissions = $permissions['/roles'] ?? null;

        // Cek izin can_create untuk path /roles
        if (!isset($rolesPermissions['can_create']) || !$rolesPermissions['can_create']) {
            return response()->json(['message' => 'Forbidden: You do not have permission to create roles.'], 403);
        }

        // --- Logika yang sudah ada untuk menyimpan role baru ---
        try {
            $this->validate($request, [
                'name' => 'required|string|max:255|unique:roles',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $role = new Role;
            $role->name = $request->input('name');
            $role->display_name = $request->input('display_name');
            $role->description = $request->input('description');
            $role->save();

            DB::commit();

            return response()->json(['message' => 'Peran berhasil ditambahkan!', 'role' => $role->load('menus')], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validasi gagal!', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menambahkan peran!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified role.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user();
        $permissions = $user->getAllMenuPermissions();
        $rolesPermissions = $permissions['/roles'] ?? null;

        // Cek izin can_read untuk path /roles (untuk melihat detail)
        if (!isset($rolesPermissions['can_read']) || !$rolesPermissions['can_read']) {
            return response()->json(['message' => 'Forbidden: You do not have permission to view role details.'], 403);
        }

        // --- Logika yang sudah ada untuk menampilkan detail role ---
        $role = Role::with('menus')->find($id);

        if (!$role) {
            return response()->json(['message' => 'Peran tidak ditemukan'], 404);
        }

        return response()->json($role);
    }

    /**
     * Update the specified role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $permissions = $user->getAllMenuPermissions();
        $rolesPermissions = $permissions['/roles'] ?? null;

        // Cek izin can_update untuk path /roles
        if (!isset($rolesPermissions['can_update']) || !$rolesPermissions['can_update']) {
            return response()->json(['message' => 'Forbidden: You do not have permission to update roles.'], 403);
        }

        // --- Logika yang sudah ada untuk memperbarui role ---
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json(['message' => 'Peran tidak ditemukan'], 404);
            }

            $this->validate($request, [
                'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $role->name = $request->input('name');
            $role->display_name = $request->input('display_name');
            $role->description = $request->input('description');
            $role->save();

            DB::commit();

            return response()->json(['message' => 'Peran berhasil diperbarui!', 'role' => $role->load('menus')]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validasi gagal!', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui peran!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified role from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $permissions = $user->getAllMenuPermissions();
        $rolesPermissions = $permissions['/roles'] ?? null;

        // Cek izin can_delete untuk path /roles
        if (!isset($rolesPermissions['can_delete']) || !$rolesPermissions['can_delete']) {
            return response()->json(['message' => 'Forbidden: You do not have permission to delete roles.'], 403);
        }

        // --- Logika yang sudah ada untuk menghapus role ---
        DB::beginTransaction();
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json(['message' => 'Peran tidak ditemukan'], 404);
            }

            if ($role->users()->count() > 0) {
                return response()->json(['message' => 'Tidak dapat menghapus peran karena masih ada pengguna yang terkait dengan peran ini.'], 409);
            }

            $role->menus()->detach();

            $role->delete();

            DB::commit();

            return response()->json(['message' => 'Peran berhasil dihapus!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus peran!', 'error' => $e->getMessage()], 500);
        }
    }
}
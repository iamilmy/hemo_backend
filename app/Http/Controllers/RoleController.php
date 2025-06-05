<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Menu; // Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Constructor for RoleController.
     * Apply 'auth' middleware to all methods.
     * Apply 'role:super_admin' middleware to all methods for authorization.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:super_admin');
    }

    /**
     * Display a listing of the roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $roles = Role::with('menus'); // Eager load menus relationship

        if ($search) {
            $roles->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('display_name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Add sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $roles->orderBy($sortBy, $sortOrder);

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
        try {
            $this->validate($request, [
                'name' => 'required|string|max:255|unique:roles',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'menu_ids' => 'sometimes|array', // New: for assigning menus to roles
                'menu_ids.*' => 'exists:menus,id',
            ]);

            DB::beginTransaction();

            $role = new Role;
            $role->name = $request->input('name');
            $role->display_name = $request->input('display_name');
            $role->description = $request->input('description');
            $role->save();

            if ($request->has('menu_ids')) {
                $role->menus()->sync($request->input('menu_ids'));
            }

            DB::commit();

            return response()->json(['message' => 'Role created successfully', 'role' => $role->load('menus')], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Role creation failed!', 'error' => $e->getMessage()], 500);
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
        $role = Role::with('menus')->find($id); // Eager load menus relationship

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
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
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json(['message' => 'Role not found'], 404);
            }

            $this->validate($request, [
                'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $role->id,
                'display_name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'menu_ids' => 'sometimes|array', // New: for assigning menus to roles
                'menu_ids.*' => 'exists:menus,id',
            ]);

            DB::beginTransaction();

            $role->fill($request->only(['name', 'display_name', 'description']));
            $role->save();

            if ($request->has('menu_ids')) {
                $role->menus()->sync($request->input('menu_ids'));
            }

            DB::commit();

            return response()->json(['message' => 'Role updated successfully', 'role' => $role->load('menus')]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Role update failed!', 'error' => $e->getMessage()], 500);
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
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        DB::beginTransaction();
        try {
            // Detach all users and menus associated with this role before deleting
            $role->users()->detach();
            $role->menus()->detach();
            $role->delete();

            DB::commit();
            return response()->json(['message' => 'Role deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete role!', 'error' => $e->getMessage()], 500);
        }
    }
}
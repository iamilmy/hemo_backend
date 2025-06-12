<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
     public function index(Request $request)
    {
        $user = Auth::user();
        \Log::info('--- UserController@index Debug Start ---');
        \Log::info('Authenticated user ID: ' . ($user ? $user->id : 'N/A'));

        if (!$user) {
            \Log::warning('UserController@index: No authenticated user.');
            \Log::info('--- UserController@index Debug End ---');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $permissions = $user->getAllMenuPermissions();
        \Log::info('Permissions returned by getAllMenuPermissions() in UserController: ' . json_encode($permissions));

        $usersPermissions = $permissions['/users'] ?? null;
        \Log::info('Specific permissions for /users in UserController: ' . json_encode($usersPermissions));
        \Log::info('Can read for /users (computed in UserController): ' . (isset($usersPermissions['can_read']) ? ($usersPermissions['can_read'] ? 'TRUE' : 'FALSE') : 'NOT SET'));


        if (!isset($usersPermissions['can_read']) || !$usersPermissions['can_read']) {
            \Log::warning('Access denied in UserController@index for user ' . $user->id . '. can_read is false or missing for /users.');
            \Log::info('--- UserController@index Debug End ---');
            return response()->json(['message' => 'Forbidden: You do not have permission to read users.'], 403);
        }

        \Log::info('Access granted for user ' . $user->id . ' to /users.');
        \Log::info('--- UserController@index Debug End ---');
        // ... (lanjutan logika index yang sudah ada)
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $users = User::with('roles', 'creator', 'updater', 'deleter');

        if ($search) {
            $users->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $users->orderBy('name', 'asc');

        $users = $users->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $permissions = $user->getAllMenuPermissions();
        // PERBAIKAN DI SINI: Gunakan key '/users'
        $usersPermissions = $permissions['/users'] ?? null;

        if (!isset($usersPermissions['can_create']) || !$usersPermissions['can_create']) {
            return response()->json(['message' => 'Forbidden: You do not have permission to create users.'], 403);
        }

        try {
            $this->validate($request, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'role_ids' => 'required|array',
                'role_ids.*' => 'exists:roles,id',
            ]);

            DB::beginTransaction();

            $newUser = new User;
            $newUser->name = $request->input('name');
            $newUser->email = $request->input('email');
            $newUser->password = Hash::make($request->input('password'));
            $newUser->created_by = Auth::id();
            $newUser->updated_by = Auth::id();
            $newUser->save();

            $newUser->roles()->sync($request->input('role_ids'));

            DB::commit();

            return response()->json(['message' => 'User created successfully', 'user' => $newUser->load('roles')], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'User creation failed!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user();
        $permissions = $user->getAllMenuPermissions();
        // PERBAIKAN DI SINI: Gunakan key '/users'
        $usersPermissions = $permissions['/users'] ?? null;

        if (!isset($usersPermissions['can_read']) || !$usersPermissions['can_read']) {
            return response()->json(['message' => 'Forbidden: You do not have permission to view user details.'], 403);
        }

        $userToView = User::withTrashed()->with('roles', 'creator', 'updater', 'deleter')->find($id);

        if (!$userToView) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($userToView);
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $permissions = $user->getAllMenuPermissions();
        // PERBAIKAN DI SINI: Gunakan key '/users'
        $usersPermissions = $permissions['/users'] ?? null;

        if (!isset($usersPermissions['can_update']) || !$usersPermissions['can_update']) {
            return response()->json(['message' => 'Forbidden: You do not have permission to update users.'], 403);
        }

        try {
            $userToUpdate = User::withTrashed()->find($id);

            if (!$userToUpdate) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $this->validate($request, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $userToUpdate->id,
                'password' => 'nullable|string|min:6',
                'role_ids' => 'required|array',
                'role_ids.*' => 'exists:roles,id',
            ]);

            DB::beginTransaction();

            $userToUpdate->name = $request->input('name');
            $userToUpdate->email = $request->input('email');
            if ($request->has('password') && !empty($request->input('password'))) {
                $userToUpdate->password = Hash::make($request->input('password'));
            }
            $userToUpdate->updated_by = Auth::id();
            $userToUpdate->save();

            $userToUpdate->roles()->sync($request->input('role_ids'));

            DB::commit();

            return response()->json(['message' => 'User updated successfully', 'user' => $userToUpdate->load('roles')]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'User update failed!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Soft delete the specified user from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $permissions = $user->getAllMenuPermissions();
        // PERBAIKAN DI SINI: Gunakan key '/users'
        $usersPermissions = $permissions['/users'] ?? null;

        if (!isset($usersPermissions['can_delete']) || !$usersPermissions['can_delete']) {
            return response()->json(['message' => 'Forbidden: You do not have permission to delete users.'], 403);
        }

        DB::beginTransaction();
        try {
            $userToDelete = User::find($id);

            if (!$userToDelete) {
                return response()->json(['message' => 'User not found'], 404);
            }

            if ($userToDelete->id === Auth::id()) {
                return response()->json(['message' => 'Cannot delete yourself.'], 403);
            }

            $userToDelete->deleted_by = Auth::id();
            $userToDelete->save();
            $userToDelete->delete();

            DB::commit();

            return response()->json(['message' => 'User soft-deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'User soft-delete failed!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Restore a soft-deleted user.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        $user = Auth::user();
        $permissions = $user->getAllMenuPermissions();
        // PERBAIKAN DI SINI: Gunakan key '/users'
        $usersPermissions = $permissions['/users'] ?? null;

        if (!isset($usersPermissions['can_update']) || !$usersPermissions['can_update']) {
            return response()->json(['message' => 'Forbidden: You do not have permission to restore users.'], 403);
        }

        DB::beginTransaction();
        try {
            $userToRestore = User::onlyTrashed()->find($id);

            if (!$userToRestore) {
                return response()->json(['message' => 'Soft-deleted user not found'], 404);
            }

            $userToRestore->restore();
            $userToRestore->deleted_by = null;
            $userToRestore->save();

            DB::commit();

            return response()->json(['message' => 'User restored successfully', 'user' => $userToRestore->load('roles')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'User restore failed!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update user's role (alternative endpoint if needed).
     * This is already handled by the general update method.
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateRole(Request $request, $id) // Method ini bisa dihapus jika update umum sudah cukup
    {
        $user = Auth::user();
        $permissions = $user->getAllMenuPermissions();
        // PERBAIKAN DI SINI: Gunakan key '/users'
        $usersPermissions = $permissions['/users'] ?? null;

        if (!isset($usersPermissions['can_update']) || !$usersPermissions['can_update']) {
            return response()->json(['message' => 'Forbidden: You do not have permission to update user roles.'], 403);
        }

        try {
            $this->validate($request, [
                'role_ids' => 'required|array',
                'role_ids.*' => 'exists:roles,id',
            ]);

            $userToUpdate = User::find($id);

            if (!$userToUpdate) {
                return response()->json(['message' => 'User not found'], 404);
            }

            if ($userToUpdate->id === Auth::id() && !in_array(1, $request->input('role_ids'))) {
                return response()->json(['message' => 'You cannot remove your own super_admin role.'], 403);
            }

            $userToUpdate->roles()->sync($request->input('role_ids'));
            $userToUpdate->updated_by = Auth::id();
            $userToUpdate->save();

            return response()->json(['message' => 'User roles updated successfully', 'user' => $userToUpdate->load('roles')]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'User role update failed!', 'error' => $e->getMessage()], 500);
        }
    }
}
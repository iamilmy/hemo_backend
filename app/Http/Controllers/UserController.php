<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Constructor for UserController.
     * Apply 'auth' middleware to all methods.
     * Apply 'role:super_admin' middleware to specific methods for authorization.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:super_admin')->only(['index', 'store', 'update', 'destroy', 'restore', 'updateRole']);
    }

    /**
     * Display a listing of the users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Default 10 items per page
        $search = $request->input('search');
        $status = $request->input('status', 'active'); // 'active', 'inactive', 'all'

        $users = User::with('roles');

        if ($search) {
            $users->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($status === 'active') {
            $users->whereNull('deleted_at');
        } elseif ($status === 'inactive') {
            $users->onlyTrashed(); // Retrieve only soft-deleted users
        } elseif ($status === 'all') {
            $users->withTrashed(); // Retrieve all users, including soft-deleted
        }

        // Add sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $users->orderBy($sortBy, $sortOrder);

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
        try {
            $this->validate($request, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'role_ids' => 'sometimes|array',
                'role_ids.*' => 'exists:roles,id',
            ]);

            DB::beginTransaction();

            $user = new User;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));
            $user->created_by = $request->user()->id;
            $user->updated_by = $request->user()->id;
            $user->save();

            if ($request->has('role_ids')) {
                $user->roles()->sync($request->input('role_ids'));
            } else {
                // Attach a default role if no roles are provided, e.g., 'pasien'
                $defaultRole = Role::where('name', 'pasien')->first();
                if ($defaultRole) {
                    $user->roles()->attach($defaultRole->id);
                } else {
                    throw new \Exception("Default role 'pasien' not found. Please seed roles table first.");
                }
            }

            DB::commit();

            return response()->json(['message' => 'User created successfully', 'user' => $user->load('roles')], 201);
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
        $user = User::withTrashed()->with('roles')->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
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
        try {
            $user = User::withTrashed()->find($id);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $this->validate($request, [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'sometimes|string|min:6',
                'role_ids' => 'sometimes|array',
                'role_ids.*' => 'exists:roles,id',
            ]);

            DB::beginTransaction();

            $user->fill($request->only(['name', 'email']));
            if ($request->has('password')) {
                $user->password = Hash::make($request->input('password'));
            }
            $user->updated_by = $request->user()->id;
            $user->save();

            if ($request->has('role_ids')) {
                $user->roles()->sync($request->input('role_ids'));
            }

            DB::commit();

            return response()->json(['message' => 'User updated successfully', 'user' => $user->load('roles')]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'User update failed!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Change the password of the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        try {
            $this->validate($request, [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed', // 'confirmed' will check for new_password_confirmation
            ]);

            $user = $request->user();

            if (!Hash::check($request->input('current_password'), $user->password)) {
                return response()->json(['message' => 'Current password does not match'], 403);
            }

            $user->password = Hash::make($request->input('new_password'));
            $user->updated_by = $user->id; // Current user is updating their own password
            $user->save();

            return response()->json(['message' => 'Password changed successfully']);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to change password!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified user from storage (soft delete).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself'], 403);
        }

        DB::beginTransaction();
        try {
            $user->deleted_by = $request->user()->id; // Set who soft-deleted the user
            $user->save(); // Save to record 'deleted_by' before actual soft delete
            $user->delete(); // This will set 'deleted_at'

            DB::commit();
            return response()->json(['message' => 'User soft-deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to soft-delete user!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Restore the specified soft-deleted user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore(Request $request, $id)
    {
        $user = User::onlyTrashed()->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found or not soft-deleted'], 404);
        }

        DB::beginTransaction();
        try {
            $user->deleted_by = null; // Clear 'deleted_by'
            $user->save(); // Save before restoring
            $user->restore(); // This will clear 'deleted_at'

            DB::commit();
            return response()->json(['message' => 'User restored successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to restore user!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update roles for a specified user.
     * This method is called from the 'update' route in web.php (users/{id}/role)
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateRole(Request $request, $id)
    {
        try {
            $user = User::withTrashed()->find($id);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $this->validate($request, [
                'role_ids' => 'required|array',
                'role_ids.*' => 'exists:roles,id',
            ]);

            DB::beginTransaction();

            // Sync the roles for the user
            $user->roles()->sync($request->input('role_ids'));
            $user->updated_by = $request->user()->id;
            $user->save(); // Save to update updated_by timestamp

            DB::commit();

            return response()->json(['message' => 'User roles updated successfully', 'user' => $user->load('roles')]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update user roles!', 'error' => $e->getMessage()], 500);
        }
    }
}
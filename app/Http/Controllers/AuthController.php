<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use Firebase\JWT\Key; // Pastikan ini juga diimpor jika diperlukan oleh JWT::decode
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth', ['except' => ['login', 'register']]);
    }

    public function register(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);
            DB::beginTransaction();
            $user = new User;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));
            if ($request->user()) {
                $user->created_by = $request->user()->id;
                $user->updated_by = $request->user()->id;
            }
            $user->save();
            $defaultRole = Role::where('name', 'pasien')->first();
            if ($defaultRole) {
                $user->roles()->attach($defaultRole->id);
            } else {
                throw new \Exception("Default role 'pasien' not found. Please seed roles table first.");
            }
            DB::commit();
            return response()->json(['message' => 'User registered successfully'], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'User registration failed!', 'error' => $e->getMessage()], 409);
        }
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);
        $user = User::where('email', $request->input('email'))->with('roles')->first();
        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $payload = ['iss' => "lumen-jwt", 'sub' => $user->id, 'iat' => time(), 'exp' => time() + 60 * 60 ];
        $jwt = JWT::encode($payload, env('JWT_SECRET'), 'HS256');

        // Saat login, kirim juga permissions
        $permissions = $user->getAllMenuPermissions();

        return response()->json([
            'token' => $jwt,
            'user' => array_merge($user->only(['id', 'name', 'email']), ['roles' => $user->roles->pluck('name')->toArray()]),
            'permissions' => $permissions, // Kirim permissions saat login
        ], 200);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Muat relasi peran user jika belum dimuat (walaupun sudah dimuat oleh Auth::user() jika guard sudah dikonfigurasi)
        $user->load('roles');

        // Dapatkan semua hak akses menu untuk user ini
        $menuPermissions = $user->getAllMenuPermissions();

        return response()->json([
            'user' => array_merge($user->only(['id', 'name', 'email']), ['roles' => $user->roles->pluck('name')->toArray()]),
            'permissions' => $menuPermissions, // Kirim permissions ke frontend
        ]);
    }
}
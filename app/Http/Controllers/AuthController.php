<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Role; // Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Validation\ValidationException; // Tambahkan
use Illuminate\Support\Facades\DB; // Tambahkan

class AuthController extends Controller
{
    public function __construct() { $this->middleware('auth', ['except' => ['login', 'register']]); }
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
            if ($request->user()) { $user->created_by = $request->user()->id; $user->updated_by = $request->user()->id; }
            $user->save();
            $defaultRole = Role::where('name', 'pasien')->first();
            if ($defaultRole) { $user->roles()->attach($defaultRole->id); }
            else { throw new \Exception("Default role 'pasien' not found. Please seed roles table first."); }
            DB::commit();
            return response()->json(['message' => 'User registered successfully'], 201);
        } catch (ValidationException $e) { DB::rollBack(); return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422); }
        catch (\Exception $e) { DB::rollBack(); return response()->json(['message' => 'User registration failed!', 'error' => $e->getMessage()], 409); }
    }
    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);
        $user = User::where('email', $request->input('email'))->with('roles')->first();
        if (!$user || !Hash::check($request->input('password'), $user->password)) { return response()->json(['message' => 'Invalid credentials'], 401); }
        $payload = ['iss' => "lumen-jwt", 'sub' => $user->id, 'iat' => time(), 'exp' => time() + 60 * 60 ];
        $jwt = JWT::encode($payload, env('JWT_SECRET'), 'HS256');
        return response()->json([ 'token' => $jwt, 'user' => array_merge($user->only(['id', 'name', 'email']), ['roles' => $user->roles->pluck('name')->toArray()]) ], 200);
    }
    public function profile(Request $request) { return response()->json($request->user()->only(['id', 'name', 'email', 'roles'])); }
}
<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // Untuk upload file
use Illuminate\Support\Facades\Log; // <-- TAMBAHKAN INI

class AppSettingController extends Controller
{
    public function __construct()
    {
        // Semua method membutuhkan autentikasi
        $this->middleware('auth');
        // Hanya super_admin yang bisa mengelola pengaturan aplikasi
       // $this->middleware('role:super_admin');
    }

    /**
     * Display the single app setting row.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        // Karena hanya ada satu baris, kita ambil yang pertama (atau buat jika belum ada)
        $appSetting = AppSetting::firstOrCreate(
            ['id' => 1], // Coba temukan berdasarkan ID 1
            ['application_name' => 'Hemodialysis SI', 'application_logo' => null] // Nilai default jika baru dibuat
        );

        return response()->json($appSetting);
    }

    /**
     * Update the single app setting row.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {

        try {
            $this->validate($request, [
                'application_name' => 'required|string|max:255',
                'application_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Max 2MB
            ]);

            DB::beginTransaction();

            $appSetting = AppSetting::firstOrCreate(
                ['id' => 1], // Pastikan kita selalu mengupdate baris pertama
                ['application_name' => 'Hemodialysis SI', 'application_logo' => null]
            );

            $appSetting->application_name = $request->input('application_name');

            // Handle logo upload
            if ($request->hasFile('application_logo')) {
                // Hapus logo lama jika ada
                if ($appSetting->application_logo) {
                    Storage::disk('public')->delete($appSetting->application_logo);
                }
                // Simpan logo baru
                $path = $request->file('application_logo')->store('logos', 'public');
                $appSetting->application_logo = $path;
            } elseif ($request->input('clear_logo')) { // Logika untuk menghapus logo tanpa upload baru
                 if ($appSetting->application_logo) {
                    Storage::disk('public')->delete($appSetting->application_logo);
                    $appSetting->application_logo = null;
                 }
            }


            $appSetting->save();

            DB::commit();

            return response()->json(['message' => 'Pengaturan aplikasi berhasil diperbarui!', 'setting' => $appSetting]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validasi gagal!', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui pengaturan aplikasi!', 'error' => $e->getMessage()], 500);
        }
    }

 
}
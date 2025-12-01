<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Petugas;
use Exception;


class PetugasController extends Controller
{
    public function store(Request $request)
    {
        try{
             $validated = $request->validate(
                [
                'nama'       => 'required|string',
                'username'   => 'required|string',
                'email'      => 'required|email',
                'password'   => 'required|string|confirmed|min:8', 
                'alamat'     => 'required|string|max:255',
                'no_telp'    => 'required|string|max:30'
                ],
                [
                'password.confirmed' => 'Konfirmasi password belum sesuai..', 
                'email.email'      => 'Alamat email tidak valid..',
                'password.min'   => 'Password harus memiliki minimal 8 karakter..', 
                'password.required'   => 'Password harus diisi..', 
                'username.required'   => 'Username harus diisi..',
                'nama.required'   => 'Nama harus diisi..',
                'alamat.required'   => 'Alamat harus diisi..',   
                ],

            );
            
            $email = $validated['email'];

            $emailExists = Member::where('email', $email)->exists() || Petugas::where('email', $email)->exists();

            if ($emailExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email sudah terdaftar. Gunakan email lain.',
                    'data' => []
                ], 422);
            }

            $username = $validated['username'];
            $usernameExists = Member::where('username', $username)->exists() || Petugas::where('username', $username)->exists();
            if ($usernameExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Username sudah terdaftar. Gunakan username lain.',
                    'data' => []
                ], 422);
            }

            $data = $validated;

            $data['status'] = 'aktif';
            $data['url_foto_profil'] = 'images/default-profile.jpeg'; // path relatif dari public/

            $petugas = Petugas::create($data);
            
            $token = $petugas->createToken('api')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Petugas berhasil ditambahkan',
                'petugas' => $petugas,
                'token'   => $token
            ], 200);

        }catch(Exception $e){
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 400);
        }  
    }

    public function show()
    {
        $petugas = Auth::guard('petugas')->user();
        if(!$petugas){
            return response()->json([
                'status' => false,
                'message' => 'Petugas tidak ditemukan',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data Petugas ditemukan',
            'data' => $petugas
        ], 200);
    }

    public function update(Request $request)
{
    try {
        /** @var Petugas $petugas */
        $petugas = Auth::guard('petugas')->user();
        if (!$petugas) {
            return response()->json([
                'status'  => false,
                'message' => 'Petugas tidak ditemukan',
                'data'    => []
            ], 404);
        }

        // ───────────────── VALIDASI ─────────────────
        $validator = Validator::make($request->all(), [
            'nama'    => 'sometimes|nullable|string',
            'username' => [
                'sometimes', 'nullable', 'string', 'max:50',
                Rule::unique('petugas', 'username')->ignore($petugas->id_petugas, 'id_petugas'),
            ],
            'email' => [
                'sometimes', 'nullable', 'email', 'max:100',
                Rule::unique('petugas', 'email')->ignore($petugas->id_petugas, 'id_petugas'),
            ],
            'alamat'          => 'sometimes|nullable|string|max:255',
            'no_telp'         => 'sometimes|nullable|string|max:30',
            'url_foto_profil' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Cek juga ke tabel member biar username/email benar-benar unik di kedua tabel
        $validator->after(function ($v) use ($request, $petugas) {
            if ($request->filled('username')) {
                $existsInMember = DB::table('member')
                    ->where('username', $request->username)
                    ->exists();

                if ($existsInMember) {
                    $v->errors()->add('username', 'Username ini sudah dipakai oleh member.');
                }
            }

            if ($request->filled('email')) {
                $existsInMember = DB::table('member')
                    ->where('email', $request->email)
                    ->exists();

                if ($existsInMember) {
                    $v->errors()->add('email', 'Email ini sudah terdaftar pada member.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // ───────────────── AMBIL DATA HASIL VALIDASI ─────────────────
        $data = $validator->validated();

        // Hapus dari array supaya tidak di-fill ke kolom url_foto_profil secara langsung
        unset($data['url_foto_profil']);

        // ───────────────── HANDLE UPLOAD FOTO PROFIL ─────────────────
        if ($request->hasFile('url_foto_profil')) {
            $path = $request->file('url_foto_profil')->store('profile', 'public');
            $petugas->url_foto_profil = $path;
        }

        // ───────────────── UPDATE FIELD BIASA (tanpa fill) ─────────────────
        foreach ($data as $key => $value) {
            // kalau mau, bisa skip null supaya tidak mengosongkan field
            if ($value !== null) {
                $petugas->{$key} = $value;
            }
        }

        $petugas->save();

        return response()->json([
            'status'  => true,
            'message' => 'Profil berhasil diperbarui',
            'data'    => $petugas->fresh()
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => $e->getMessage(),
            'data'    => []
        ], 400);
    }
}


   public function destroy()
{
    try {
        /** @var Petugas $petugas */
        $petugas = Auth::guard('petugas')->user();

        if (!$petugas) {
            return response()->json([
                'status' => false,
                'message' => 'petugas tidak ditemukan'
            ], 404);
        }

        $petugas->delete();

        return response()->json([
            'status' => true,
            'message' => 'Akun berhasil dihapus'
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Gagal menghapus akun: ' . $e->getMessage()
        ], 500);
    }
}

     public function changePassword(Request $request)
{
    /** @var Petugas $petugas */
    $petugas = Auth::guard('petugas')->user();

    $validated = $request->validate([
        'current_password'      => 'required',
        'new_password'          => 'required|min:8|confirmed', 
    ],
    [
        'new_password.confirmed' => 'Konfirmasi password tidak sesuai.',
        'new_password.min'       => 'Password baru harus memiliki minimal 8 karakter.',
        'current_password.required' => 'Password lama harus diisi.',
        'new_password.required' => 'Password baru harus diisi.',
    ]);

    // cek password lama
    if (!Hash::check($validated['current_password'], $petugas->password)) {
        return response()->json([
            'status'  => false,
            'message' => 'Password lama tidak sesuai.',
        ], 422);
    }

    // update password baru (di-hash)
    $petugas->password = Hash::make($validated['new_password']);
    $petugas->save();   // cukup sekali ya, yang satunya bisa kamu hapus

    return response()->json([
        'status'  => true,
        'message' => 'Password berhasil diubah.',
    ]);
}

    
}

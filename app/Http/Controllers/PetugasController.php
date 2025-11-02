<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
        try{
            $petugas = Auth::guard('petugas')->user();
            if(!$petugas){
                return response()->json([
                    'status' => false,
                    'message' => 'Petugas tidak ditemukan',
                    'data' => []
                ], 404);
            }


            $validator = Validator::make($request->all(),
                [
                    'nama'       => 'sometimes|nullable|string',
                    'username' => [
                        'sometimes', 'nullable', 'string', 'max:50',
                        Rule::unique('petugas','username')->ignore($petugas->id_petugas, 'id_petugas'),
                    ],

                    'email' => [
                        'sometimes', 'nullable', 'email', 'max:100',
                        Rule::unique('petugas','email')->ignore($petugas->id_petugas, 'id_petugas'),
                    ],
                    'alamat'     => 'sometimes|nullable|string|max:255',
                    'no_telp'    => 'sometimes|nullable|string|max:30'
                ],
                [
                    'email.unique'      => 'Email sudah digunakan.',
                    'username.unique'   => 'Username sudah digunakan.',
                ]
            );

            $validator->after(function ($v) use ($request) {
            if ($request->filled('username')) {
                    if (DB::table('member')->where('username', $request->username)->exists()) {
                        $v->errors()->add('username', 'Username sudah digunakan.');
                    }
                }
                if ($request->filled('email')) {
                    if (DB::table('member')->where('email', $request->email)->exists()) {
                        $v->errors()->add('email', 'Email sudah digunakan.');
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

            $data = $validator->validated();
            
            if($request->hasFile('url_foto_profil')){
                $image = $request->url_foto_profil;
                $imageName = $image->getClientOriginalName();
                $image->move(public_path('storage/profile'), $imageName);
                $petugas->update([
                    'url_foto_profil' => 'profile/' . $imageName
                ]);// path relatif dari public/
            }else{
                $imageName = $petugas->url_foto_profil;
                $petugas->update([
                    'url_foto_profil' => $imageName,
                ]);
            }


            $petugas->update($data);

            return response()->json([
                'status'  => true,
                'message' => 'Profil berhasil diperbarui',
                'data'    => $petugas->fresh()
            ], 200);

        }catch(Exception $e){
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 400);
        }
    }

    public function destroy()
    {
        try {
            $petugas = Auth::guard('petugas')->user();

            if (!$petugas) {
                return response()->json([
                    'status' => false,
                    'message' => 'petugas tidak ditemukan'
                ], 404);
            }

            // Hapus akun
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
}

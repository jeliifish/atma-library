<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Petugas;

class MemberController extends Controller
{
    public function store(Request $request)
    {
        try{
             $validated = $request->validate(
                [
                'nama'       => 'required|string',
                'username'   => 'required|string',
                'email'      => 'required|email|',
                'password'   => 'required|string|confirmed|min:8', // model akan auto-hash via $casts
                'alamat'     => 'required|string|max:255',
                'no_telp'    => 'required|string|max:30'
                ],
                [
                'password.confirmed' => 'Konfirmasi password belum sesuai..', 
                'email.email'      => 'Alamat email tidak valid..',
                'tgl_daftar' => 'nullable|date',
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

            $data['tgl_daftar'] = now()->toDateString();
            $data['status'] = 'aktif';
            $data['url_foto_profil'] = 'images/default-profile.jpeg'; // path relatif dari public/

            $member = Member::create($data);
            
            $token = $member->createToken('api')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Member berhasil ditambahkan',
                'member' => $member,
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
}

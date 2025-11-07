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


class MemberController extends Controller
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

            $data['tgl_daftar'] = now()->toDateString();
            $data['status'] = 'aktif';
            $data['url_foto_profil'] = 'images/default-profile.jpeg'; // path relatif dari public/

            $member = Member::create($data);

            $member->peminjaman()->firstOrCreate(
                [
                    'id_member' => $member->id_member,
                    'id_petugas' => null,
                    'tgl_pinjam' => now(),
                    'tgl_kembali' => now()->addDays(7),
                    'status' => 'draft'
                ]
            );

            
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

    public function show()
    {
        $member = Auth::guard('member')->user();
        if(!$member){
            return response()->json([
                'status' => false,
                'message' => 'Member tidak ditemukan',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data member ditemukan',
            'data' => $member
        ], 200);
    }

    public function update(Request $request)
    {
        try{
            
            $member = Auth::guard('member')->user();
            if(!$member){
                return response()->json([
                    'status' => false,
                    'message' => 'Member tidak ditemukan',
                    'data' => []
                ], 404);
            }

             $validator = Validator::make($request->all(),
                [
                    'nama'       => 'sometimes|nullable|string',
                    'username' => [
                        'sometimes', 'nullable', 'string', 'max:50',
                        Rule::unique('member','username')->ignore($member->id_member, 'id_member'),
                    ],

                    'email' => [
                        'sometimes', 'nullable', 'email', 'max:100',
                        Rule::unique('member','email')->ignore($member->id_member, 'id_member'),
                    ],
                    'alamat'     => 'sometimes|nullable|string|max:255',
                    'no_telp'    => 'sometimes|nullable|string|max:30'
                ]
            );

            $validator->after(function ($v) use ($request) {
            if ($request->filled('username')) {
                    if (DB::table('petugas')->where('username', $request->username)->exists()) {
                        $v->errors()->add('username', 'Username sudah digunakan.');
                    }
                }
                if ($request->filled('email')) {
                    if (DB::table('petugas')->where('email', $request->email)->exists()) {
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
                $member->update([
                    'url_foto_profil' => 'profile/' . $imageName
                ]);// path relatif dari public/
            }else{
                $imageName = $member->url_foto_profil;
                $member->update([
                    'url_foto_profil' => $imageName,
                ]);
            }



            $member->update($data);



            return response()->json([
                'status'  => true,
                'message' => 'Profil berhasil diperbarui',
                'data'    => $member->fresh()
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
            $member = Auth::guard('member')->user();


            if (!$member) {
                return response()->json([
                    'status' => false,
                    'message' => 'Member tidak ditemukan'
                ], 404);
            }

            // Hapus akun
            $member->delete();

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

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

class MemberController extends Controller
{
    // LIST untuk halaman Member List (PETUGAS)
    public function index()
    {
        $members = Member::orderBy('id_member')->get();

        return response()->json([
            'data' => $members,
        ]);
    }

    // REGISTER MEMBER (dipakai halaman register)
    public function store(Request $request)
    {
        try {
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
                    'email.email'        => 'Alamat email tidak valid..',
                    'password.min'       => 'Password harus memiliki minimal 8 karakter..',
                    'password.required'  => 'Password harus diisi..',
                    'username.required'  => 'Username harus diisi..',
                    'nama.required'      => 'Nama harus diisi..',
                    'alamat.required'    => 'Alamat harus diisi..',
                ]
            );

            $email = $validated['email'];
            $emailExists = Member::where('email', $email)->exists()
                || Petugas::where('email', $email)->exists();

            if ($emailExists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Email sudah terdaftar. Gunakan email lain.',
                    'data'    => []
                ], 422);
            }

            $username = $validated['username'];
            $usernameExists = Member::where('username', $username)->exists()
                || Petugas::where('username', $username)->exists();

            if ($usernameExists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Username sudah terdaftar. Gunakan username lain.',
                    'data'    => []
                ], 422);
            }

            $data = $validated;
            $data['tgl_daftar']      = now()->toDateString();
            $data['status']          = 'aktif';
            $data['url_foto_profil'] = 'images/default-profile.jpeg';
            $data['role'] = 'member';

            $member = Member::create($data);

            $member->peminjaman()->firstOrCreate(
                [
                    'id_member'   => $member->id_member,
                    'id_petugas'  => null,
                    'tgl_pinjam'  => now(),
                    'tgl_kembali' => now()->addDays(7),
                    'status'      => 'draft'
                ]
            );

            $token = $member->createToken('api')->plainTextToken;

            return response()->json([
                'status'  => true,
                'message' => 'Member berhasil ditambahkan',
                'member'  => $member,
                'token'   => $token
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
                'data'    => []
            ], 400);
        }
    }

    // PROFILE MEMBER (yang login)
    public function show()
    {
        $member = Auth::guard('member')->user();
        if (!$member) {
            return response()->json([
                'status'  => false,
                'message' => 'Member tidak ditemukan',
                'data'    => []
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Data member ditemukan',
            'data'    => $member
        ], 200);
    }

    // UPDATE PROFILE MEMBER (yang login)
    public function update(Request $request)
    {
        try {
            $member = Auth::guard('member')->user();
            if (!$member) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Member tidak ditemukan',
                    'data'    => []
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama'       => 'sometimes|nullable|string',
                'username'   => [
                    'sometimes', 'nullable', 'string', 'max:50',
                    Rule::unique('member', 'username')->ignore($member->id_member, 'id_member'),
                ],
                'email'      => [
                    'sometimes', 'nullable', 'email', 'max:100',
                    Rule::unique('member', 'email')->ignore($member->id_member, 'id_member'),
                ],
                'alamat'     => 'sometimes|nullable|string|max:255',
                'no_telp'    => 'sometimes|nullable|string|max:30',
                'url_foto_profil' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            $validator->after(function ($v) use ($request) {
                if ($request->filled('username')) {
                    if (DB::table('petugas')->where('username', $request->username)->exists()) {
                        $v->errors()->add('username', 'This username is already taken');
                    }
                }
                if ($request->filled('email')) {
                    if (DB::table('petugas')->where('email', $request->email)->exists()) {
                        $v->errors()->add('email', 'This email address is already registered');
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

            unset($data['url_foto_profil']);

            if ($request->hasFile('url_foto_profil')) {
                $path = $request->file('url_foto_profil')->store('profile', 'public');
                $member->url_foto_profil = $path;
            }

            $member->fill($data);
            $member->save();

            return response()->json([
                'status'  => true,
                'message' => 'Profil berhasil diperbarui',
                'data'    => $member->fresh()
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
                'data'    => []
            ], 400);
        }
    }

    // HAPUS AKUN MEMBER (yang login)
    public function destroy()
    {
        try {
            $member = Auth::guard('member')->user();

            if (!$member) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Member tidak ditemukan'
                ], 404);
            }

            $member->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Akun berhasil dihapus'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal menghapus akun: ' . $e->getMessage()
            ], 500);
        }
    }

    // HAPUS MEMBER BY ID (untuk PETUGAS)
    public function destroyById($id_member)
    {
        $member = Member::find($id_member);

        if (!$member) {
            return response()->json([
                'message' => 'Member tidak ditemukan',
            ], 404);
        }

        $member->delete();

        return response()->json([
            'message' => 'Member berhasil dihapus',
        ]);
    }

    // UPDATE MEMBER BY ID (untuk PETUGAS)
    public function updateById(Request $request, $id_member)
    {
        $member = Member::find($id_member);

        if (!$member) {
            return response()->json([
                'status'  => false,
                'message' => 'Member tidak ditemukan',
                'data'    => []
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama'         => 'sometimes|nullable|string|max:100',
            'email'        => [
                'sometimes', 'nullable', 'email', 'max:100',
                Rule::unique('member', 'email')->ignore($member->id_member, 'id_member'),
            ],
            'nomor_member' => 'sometimes|nullable|string|max:50',
            'no_telp'      => 'sometimes|nullable|string|max:30',
            'alamat'       => 'sometimes|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $member->fill($data);
        $member->save();

        return response()->json([
            'status'  => true,
            'message' => 'Member berhasil diperbarui',
            'data'    => $member->fresh(),
        ], 200);
    }

    // TOGGLE STATUS MEMBER BY ID (untuk PETUGAS)
    public function toggleStatus($id_member)
    {
        try {
            $member = Member::find($id_member);

            if (!$member) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Member tidak ditemukan',
                ], 404);
            }

            // pastikan kolom 'status' ada di tabel member
            $member->status = $member->status === 'aktif' ? 'nonaktif' : 'aktif';
            $member->save();

            return response()->json([
                'status'  => true,
                'message' => 'Status member berhasil diubah',
                'data'    => $member,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // GANTI PASSWORD MEMBER (yang login)
    public function changePassword(Request $request)
    {
        $member = Auth::guard('member')->user();

        $validated = $request->validate(
            [
                'current_password' => 'required',
                'new_password'     => 'required|min:8|confirmed',
            ],
            [
                'new_password.confirmed' => 'Konfirmasi password tidak sesuai.',
                'new_password.min'       => 'Password baru harus memiliki minimal 8 karakter.',
                'current_password.required' => 'Password lama harus diisi.',
                'new_password.required'  => 'Password baru harus diisi.',
            ]
        );

        if (!Hash::check($validated['current_password'], $member->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Password lama tidak sesuai.',
            ], 422);
        }

        $member->password = Hash::make($validated['new_password']);
        $member->save();

        return response()->json([
            'status'  => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }
}

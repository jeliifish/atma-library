<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Petugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
     // LOGIN
    public function login(Request $request)
    {
        $member = Member::where('email', $request->email)->first();
        if ($member && Hash::check( $request->password, $member->password)) {
            $token = $member->createToken('member')->plainTextToken;

                return response()->json([
                    'message' => 'Login berhasil',
                    'token'   => $token,
                    'member'  => $member
                ]);
        }

        $petugas = Petugas::where('email', $request->email)->first();
        if ($petugas && Hash::check( $request->password, $petugas->password)) {
            $token = $petugas->createToken('petugas')->plainTextToken;

                return response()->json([
                    'message' => 'Login berhasil',
                    'token'   => $token,
                    'petugas'  => $petugas
                ]);
        }

         if (!$petugas && !$member) {
            return response()->json([
                'message' => 'Email belum terdaftar'
            ], 404);
        }

        return response()->json(['message' => 'Email atau password salah'], 401);

    }


    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }
}

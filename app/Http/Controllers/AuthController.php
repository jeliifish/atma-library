<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Petugas;
use App\Models\Peminjaman;
use App\Models\CopyBuku;
use App\Models\DetailPeminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function addToDraft(Request $request)
    {
        try{
            $validated = $request->validate([
                'id_buku' => 'required|exists:buku,id_buku',
            ]);

            $member = Auth::guard('member')->user();

            return DB::transaction(function () use ($validated, $member) {
                // ambil peminjaman dari member sekarang dengan status 'menunggu'
                $draft = Peminjaman::where('id_member', $member->id_member)
                    ->where('status', 'draft')
                    ->latest('tgl_pinjam')
                    ->first();



                // cari copyan buku terakhir yang tersedia
              
                $copy = CopyBuku::where('id_buku', $validated['id_buku'])
                    ->where('status', 'tersedia')
                    ->orderBy('id_buku_copy', 'desc')
                    ->lockForUpdate()
                    ->first();

                if (!$copy) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Tidak ada copy buku yang tersedia.',
                    ], 409);
                }

                // tambah ke detail draft
                $detail = DetailPeminjaman::create([
                    'nomor_pinjam' => $draft->nomor_pinjam,
                    'id_buku'      => $validated['id_buku'],
                    'id_buku_copy' => $copy->id_buku_copy,
                    'tgl_kembali'  => null,
                ]);

                // tandain copy buku yang lagi diajuin
                $copy->update(['status' => 'dipinjam']);

                return response()->json([
                    'status' => true,
                    'message' => 'Buku berhasil ditambahkan ke daftar peminjaman sementara.',
                    'data' => compact('draft', 'detail')
                ]);
            });
        }catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menambahkan buku ke draft: ' . $e->getMessage(),
            ], 500);
        }
        
    }

    public function submitDraft(Request $request)
    {
        try{
            $member = Auth::guard('member')->user();

            //ambl draft peminjaman terakhir dengan status menunggu dan punya detail peminjaman
            $draft = Peminjaman::where('id_member', $member->id_member)
                ->where('status', 'draft')
                ->whereHas('detailPeminjaman')
                ->latest('nomor_pinjam')
                ->first();

            if (!$draft) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada peminjaman draft yang bisa diajukan.'
                ], 404);
            }

            if($draft->detailPeminjaman->isEmpty()){
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada buku dalam draft peminjaman.'
                ], 400);
            }
            
            $draft->update(['status' => 'menunggu']);
            $draft->save();
            $draft = $draft->fresh(['detailPeminjaman.copyBuku','member','petugas']);
            
         
            return response()->json([
                'status' => true,
                'message' => 'Peminjaman berhasil diajukan, menunggu persetujuan petugas.',
                'data' => $draft
            ]);
        }catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengajukan peminjaman: ' . $e->getMessage(),
            ], 500);
        }
       
    }   

    public function returnBook()
    {
        try{
            $member = Auth::guard('member')->user();
            if (!$member) {
                return response()->json([
                    'status' => false,
                    'message' => 'Member tidak ditemukan.'
                ], 404);
            }

             $validated = $request->validate([
                'id_buku_copy' => 'required|exists:buku_copy,id_buku_copy',
            ]);

            $peminjaman = Peminjaman::where('id_member', $member->id_member)    //
            ->where('status', 'disetujui')
            ->whereHas('detailPeminjaman', function ($query) use ($validated) {
                $query->where('id_buku_copy', $validated['id_buku_copy']);
            })
            ->with('detailPeminjaman')
            ->first();

            // $peminjaman = Peminjaman::where('id_member', $member->id_member) //nanti kalo udh dikembaliin smw, status peminjaman 
            //         ->where('status', 'disetujui')                           //jadi "selesai" 
            //         ->whereHas('detailPeminjaman')
            //         ->latest('nomor_pinjam')
            //         ->first();

            $detailSelected = $peminjaman->detailPeminjaman
                    ->where('id_buku_copy', $validated['id_buku_copy'])
                    ->first();

            if (!$peminjaman) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada peminjaman yang sedang dipinjam.'
                ], 404);
            }

       
            // // menghitung denda jika ada keterlambatan
            // foreach ($peminjaman->detailPeminjaman as $detail) {
            //     $dueDate = $peminjaman->tgl_kembali;
            //     $returnDate = $detail->tgl_kembali;

            //     if ($returnDate->gt($dueDate)) {
            //         $daysLate = $dueDate->diffInDays($returnDate);
            //         $dendaPerDay = 2000; // contoh denda per hari
            //         $totalDenda = $daysLate * $dendaPerDay;

            //         // Simpan atau proses denda sesuai kebutuhan
            //     }
            // }
            
            $detailSelected->status = 'dikembalikan';   //cm yg dipilih yg statusnya berubah
            $detailSelected->save();

            return response()->json([
                'status' => true,
                'message' => 'Buku berhasil dikembalikan.',
                'data' => $peminjaman
            ]);

        }catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengembalikan buku: ' . $e->getMessage(),
            ], 500);
        }
        
    }
    

}

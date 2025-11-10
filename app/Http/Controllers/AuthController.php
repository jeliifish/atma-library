<?php

namespace App\Http\Controllers;


use App\Models\Petugas;
use App\Models\Peminjaman;
use App\Models\CopyBuku;
use App\Models\DetailPeminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
use App\Models\Denda;
use Carbon\Carbon;


class AuthController extends Controller
{
     // LOGIN
    public function login(Request $request)
    {
        $member = Member::where('email', $request->email)->first();
        if ($member && Hash::check($request->password, $member->password)) {
            $token = $member->createToken('member')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'token'   => $token,
                'user'    => [
                    'id'    => $member->id,
                    'nama'  => $member->nama,
                    'email' => $member->email,
                    'role'  => 'member'
                ]
            ]);
        }

        $petugas = Petugas::where('email', $request->email)->first();
        if ($petugas && Hash::check($request->password, $petugas->password)) {
            $token = $petugas->createToken('petugas')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'token'   => $token,
                'user'    => [
                    'id'    => $petugas->id,
                    'nama'  => $petugas->nama,
                    'email' => $petugas->email,
                    'role'  => 'petugas'
                ]
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
                // ambil peminjaman dari member sekarang dengan status 'draft'
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
                    'status'       => 'menunggu',
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
           
            $member->peminjaman()->firstOrCreate(
                [
                'id_member' => $member->id_member,
                 'status' => 'draft'
                ],
                ['id_petugas' => null,
                 'tgl_pinjam' => now(),
                 'tgl_kembali' => now()->addDays(7)
                ]
            );


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

  public function returnBook(Request $request)
  {
    try{
        $member = Auth::guard('member')->user();
        if (!$member) {
            return response()->json(['status' => false, 'message' => 'Member tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'id_buku_copy' => 'required|exists:copy_buku,id_buku_copy',
        ]);

        return DB::transaction(function () use ($member, $validated) {

            // cari peminjaman yang udah disetui yang berisi id_buku_copy yang mau dikembaliin
            $peminjaman = Peminjaman::where('id_member', $member->id_member)
                ->where('status', 'disetujui')
                ->whereHas('detailPeminjaman', function ($q) use ($validated) {
                    $q->where('id_buku_copy', $validated['id_buku_copy'])
                    ->where('status', '!=', 'dikembalikan');
                })
                ->orderByDesc('nomor_pinjam') 
                ->first();


            if (!$peminjaman) {
                return response()->json([
                    'status' => false,
                    'message' => 'Buku tidak ditemukan pada peminjaman aktif atau sudah dikembalikan.'
                ], 404);
            }

            // cari detailPeminjaman yang mau dikembaliin dan langsung diupdate 
            $updatedDetail = DetailPeminjaman::where('nomor_pinjam', $peminjaman->nomor_pinjam)
                ->where('id_buku_copy', $validated['id_buku_copy'])
                ->where('status', '!=', 'dikembalikan')
                ->lockForUpdate()
                ->update([
                    'status'      => 'dikembalikan',
                    'tgl_kembali' => now()->addDays(8),
                ]); // mengembalikan jumlah baris yang diupdate

            if ($updatedDetail === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Detail peminjaman tidak ditemukan atau sudah dikembalikan.'
                ], 404);
            }

            // update status copy buku nya jadi tersedia
            CopyBuku::where('id_buku_copy', $validated['id_buku_copy'])
                ->lockForUpdate()
                ->update(['status' => 'tersedia']);

            $due = Carbon::parse($peminjaman->tgl_kembali)->startOfDay();
            $now = Carbon::now()->startOfDay();
            $hariTelat = $due->diffInDays($now, false);
            $hariTelat = max(0, $hariTelat);

            if ($hariTelat > 0) {
                
                $hargaPerHari = 1000; // contoh Rp1000/hari
                $totalDenda = $hariTelat * $hargaPerHari;

                Denda::create([
                    'nomor_pinjam'   => $peminjaman->nomor_pinjam,
                    'id_buku_copy'   => $validated['id_buku_copy'],
                    'hari_telat'     => $hariTelat,
                    'harga_per_hari' => $hargaPerHari,
                    'total_denda'    => $totalDenda,
                    'status'         => 'belum'
                ]);
            }
            // ngecek apakah smua buku di peminjaman ini udah dikembaliin semua atau belum
            $isComplete = DetailPeminjaman::where('nomor_pinjam', $peminjaman->nomor_pinjam)
                ->where('status', '!=', 'dikembalikan')
                ->exists();

            if (!$isComplete) {
                $peminjaman->update(['status' => 'selesai']);
            }

            $peminjaman = Peminjaman::with([
                    'detailPeminjaman.copyBuku',
                    'member',
                    'petugas'
                ])->find($peminjaman->nomor_pinjam);

            return response()->json([
                'status'  => true,
                'message' => 'Pengembalian buku berhasil.',
                'peminjaman'  => $peminjaman,
            ]);
        });

    }catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Terjadi kesalahan saat mengembalikan buku: ' . $e->getMessage(),
        ], 500);
    }
  }
}
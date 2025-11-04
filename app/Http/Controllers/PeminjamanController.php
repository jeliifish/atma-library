<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use Illuminate\Support\Facades\DB;
use App\Models\CopyBuku;

class PeminjamanController extends Controller
{
    //index
   public function index()
    {
        // Pastikan hanya petugas yang bisa akses endpoint ini
        $petugas = Auth::guard('petugas')->user();

        if (!$petugas) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Hanya petugas yang dapat mengakses data ini.'
            ], 403);
        }

        // ambil semua peminjaman dengan relasi-relasinya
        $peminjaman = Peminjaman::with([
            'member',                      // untuk lihat siapa yang meminjam
            'petugas',                     // untuk tahu siapa yang melayani
            'detailPeminjaman.copyBuku'    // untuk lihat buku-buku yang dipinjam
        ])->get();

        return response()->json([
            'status' => true,
            'message' => 'Daftar semua peminjaman berhasil diambil.',
            'data' => $peminjaman
        ]);
    }

    public function show($nomor_pinjam)
    {
        $member = Auth::guard('member')->user();
        $peminjaman = Peminjaman::where('id_member', $member->id_member)
                        ->where('nomor_pinjam', $nomor_pinjam)
                        ->with('detailPeminjaman.copyBuku')
                        ->first();

        if (!$peminjaman) {
            return response()->json([
                'status' => false,
                'message' => 'Peminjaman tidak ditemukan.',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail peminjaman berhasil diambil.',
            'data' => $peminjaman
        ]);
    }

    public function store()
    {
        try{
            $member = Auth::guard('member')->user();
        
            $tgl_pinjam = now();
            $tgl_kembali = now()->addDays(7); 

            $peminjaman = Peminjaman::create([
                'id_member' => $member->id_member,
                'id_petugas' => null,
                'tgl_pinjam' => $tgl_pinjam,
                'tgl_kembali' => $tgl_kembali,
                'status' => 'draft',
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Peminjaman berhasil dibuat.',
                'data'    => $peminjaman
            ]);
        }catch(Exception $e){
            return response()->json([
                'status'  => false,
                'message' => 'Gagal membuat peminjaman: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }
        
    }


    public function updateStatus(Request $request, $nomor_pinjam)
    {
        try{
            $request->validate([
                'status' => 'required|in:disetujui,ditolak,dikembalikan',
            ]);

            // pastikan yang login petugas
            $petugas = Auth::guard('petugas')->user();
            if (!$petugas) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Hanya petugas yang dapat mengubah status peminjaman.'
                ], 403);
            }


            $peminjaman = Peminjaman::with('detailPeminjaman')->find($nomor_pinjam);
            if (!$peminjaman) {
                return response()->json([
                    'status' => false,
                    'message' => 'Peminjaman tidak ditemukan.'
                ], 404);
            }

            DB::transaction(function () use ($request, $peminjaman) {
                $newStatus = $request->status;

                // update header
                $peminjaman->update([
                    'status' => $newStatus,
                    'id_petugas' => $petugas->id_petugas,
                ]);

                // kalau disetujui → set semua detail jadi disetujui dan ubah status copy-nya
                if ($newStatus === 'disetujui') {
                    foreach ($peminjaman->detailPeminjaman as $detail) {
                        $detail->update(['status' => 'disetujui']);

                        // ubah stok copy
                        $copy = CopyBuku::find($detail->id_buku_copy);
                        if ($copy) {
                            $copy->update(['status' => 'dipinjam']);
                        }
                    }
                }

                // kalau ditolak → set semua detail jadi ditolak & copy buku dikembalikan tersedia
                if ($newStatus === 'ditolak') {
                    foreach ($peminjaman->detailPeminjaman as $detail) {
                        $detail->update(['status' => 'ditolak']);
                        $copy = CopyBuku::find($detail->id_buku_copy);
                        if ($copy) {
                            $copy->update(['status' => 'tersedia']);
                        }
                    }
                }

                // kalau selesai → semua detail sudah dikembalikan
                if ($newStatus === 'dikembalikan') {
                    foreach ($peminjaman->detailPeminjaman as $detail) {
                        $detail->update(['tgl_kembali' => now(), 'status' => 'dikembalikan']);
                        $copy = CopyBuku::find($detail->id_buku_copy);
                        if ($copy) {
                            $copy->update(['status' => 'tersedia']);
                        }
                    }
                }
            });

            return response()->json([
                'status' => true,
                'message' => "Status peminjaman $nomor_pinjam berhasil diperbarui.",
                'data' => $peminjaman->fresh('detailPeminjaman')
            ]);

        }catch(Exception $e){
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memperbarui status peminjaman: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }
       
    }

}

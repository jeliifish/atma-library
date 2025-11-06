<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use App\Models\CopyBuku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
    // 📖 1. Tampilkan semua data peminjaman
    public function index()
    {
        $peminjaman = Peminjaman::with('detailPeminjaman.copyBuku.buku', 'member', 'petugas')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar semua peminjaman',
            'data' => $peminjaman
        ]);
    }

    // 📝 2. Ajukan peminjaman (oleh member)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_member' => 'required|exists:member,id_member',
            'id_buku_copy' => 'required|array|min:1',
            'id_buku_copy.*' => 'exists:copy_buku,id_buku_copy'
        ],[
            'id_member.exists' => 'Member tidak ditemukan, silakan periksa ID member.',
            'id_buku_copy.*.exists' => 'ID salinan buku yang dipilih tidak ditemukan di database.'
        ]);

        DB::beginTransaction();
        try {
            // 🚫 Cek apakah ada buku yang sedang dipinjam
            $bukuDipinjam = CopyBuku::whereIn('id_buku_copy', $validated['id_buku_copy'])
                ->whereIn('status', ['dipinjam', 'menunggu']) // termasuk dipinjam atau menunggu
                ->pluck('id_buku_copy');

            if ($bukuDipinjam->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Beberapa buku tidak tersedia untuk dipinjam.',
                    'buku_tidak_tersedia' => $bukuDipinjam
                ], 422);
            }

            // 🧾 Buat transaksi peminjaman
            $peminjaman = Peminjaman::create([
                'id_member' => $validated['id_member'],
                'id_petugas' => 2, // nanti bisa diubah ke auth()->user()->id_petugas
                'tgl_pinjam' => now(),
                'tgl_kembali' => now()->addDays(7),
                'status' => 'menunggu'
            ]);

            // 📚 Tambahkan detail untuk setiap buku
            foreach ($validated['id_buku_copy'] as $bukuCopy) {
                DetailPeminjaman::create([
                    'nomor_pinjam' => $peminjaman->nomor_pinjam,
                    'id_buku_copy' => $bukuCopy,
                    'tgl_kembali' => $peminjaman->tgl_kembali,
                    'status' => 'menunggu'
                ]);
                // Lock buku agar tidak bisa dipinjam orang lain
                CopyBuku::where('id_buku_copy', $bukuCopy)->update(['status' => 'menunggu']); 
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Peminjaman berhasil diajukan dan menunggu persetujuan petugas.',
                'data' => $peminjaman
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 3. Petugas menyetujui peminjaman
    public function approve($nomor_pinjam)
    {
        DB::beginTransaction();
        try {
            $details = DetailPeminjaman::where('nomor_pinjam', $nomor_pinjam)->get();

            if ($details->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor peminjaman tidak ditemukan.'
                ], 404);
            }

            foreach ($details as $detail) {
                $detail->update(['status' => 'disetujui']);
                CopyBuku::where('id_buku_copy', $detail->id_buku_copy)->update(['status' => 'dipinjam']);
            }

            Peminjaman::where('nomor_pinjam', $nomor_pinjam)->update(['status' => 'disetujui']);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Peminjaman berhasil disetujui.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ❌ 4. Petugas menolak peminjaman
    public function reject($nomor_pinjam)
    {
        DB::beginTransaction();
        try {
            $details = DetailPeminjaman::where('nomor_pinjam', $nomor_pinjam)->get();

            if ($details->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor peminjaman tidak ditemukan.'
                ], 404);
            }

            foreach ($details as $detail) {
                $detail->update(['status' => 'ditolak']);
                // buku kembali tersedia jika ditolak
                CopyBuku::where('id_buku_copy', $detail->id_buku_copy)->update(['status' => 'tersedia']);
            }

            Peminjaman::where('nomor_pinjam', $nomor_pinjam)->update(['status' => 'ditolak']);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Peminjaman ditolak oleh petugas.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // 🔁 5. Pengembalian buku
    public function pengembalian($nomor_pinjam)
    {
        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::where('nomor_pinjam', $nomor_pinjam)->first();
            if (!$peminjaman) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor peminjaman tidak ditemukan.'
                ], 404);
            }

            $details = DetailPeminjaman::where('nomor_pinjam', $nomor_pinjam)->get();
            if ($details->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Detail peminjaman tidak ditemukan.'
                ], 404);
            }

            foreach ($details as $detail) {
                if ($detail->status === 'disetujui') {
                    $detail->update(['status' => 'dikembalikan']);
                    CopyBuku::where('id_buku_copy', $detail->id_buku_copy)->update(['status' => 'tersedia']);
                }
            }

            $peminjaman->update(['status' => 'dikembalikan', 'tgl_kembali' => now()]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Buku berhasil dikembalikan.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Member;
use App\Models\Peminjaman;
use App\Models\PembayaranDenda;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function summary()
    {
        $totalBuku       = Buku::count();
        $totalMember     = Member::count();
        $peminjamanAktif = Peminjaman::where('status', 'dipinjam')->count();
        $totalDendaBayar = PembayaranDenda::sum('total_bayar');

        return response()->json([
            'total_buku'        => $totalBuku,
            'total_member'      => $totalMember,
            'peminjaman_aktif'  => $peminjamanAktif,
            'total_denda_bayar' => $totalDendaBayar,
        ]);
    }

    // daftar peminjaman terbaru (misal 20 terakhir)
    public function loans()
    {
        $peminjaman = Peminjaman::with(['member', 'detailPeminjaman'])
            ->orderBy('tgl_pinjam', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($p) {
                return [
                    'nomor_pinjam'   => $p->nomor_pinjam,
                    'tgl_pinjam'     => $p->tgl_pinjam,
                    'tgl_kembali'    => $p->tgl_kembali,
                    'status'         => $p->status,
                    'nama_member'    => optional($p->member)->nama,
                    'jumlah_buku'    => $p->detailPeminjaman->count(),
                    'total_denda'    => $p->total_denda ?? 0, // sesuaikan field kalau beda
                ];
            });

        return response()->json([
            'data' => $peminjaman,
        ]);
    }

    // daftar pembayaran denda terbaru (misal 20 terakhir)
    public function fines()
    {
        $pembayaran = PembayaranDenda::with('member')
            ->orderBy('tgl_bayar', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($p) {
                return [
                    'id_pembayaran' => $p->id_pembayaran,
                    'tgl_bayar'     => $p->tgl_bayar,
                    'nama_member'   => optional($p->member)->nama,
                    'total_bayar'   => $p->total_bayar,
                    'keterangan'    => $p->keterangan ?? '-',
                ];
            });

        return response()->json([
            'data' => $pembayaran,
        ]);
    }
}

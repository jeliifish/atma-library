<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Denda;
use App\Models\PembayaranDenda;
use App\Models\DetailPembayaranDenda;

class PembayaranController extends Controller
{
     public function showUnpaidFineDetails()
    {
        $member = Auth::guard('member')->user();

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'Member tidak terautentikasi.'
            ], 401);
        }

        // Ambil semua denda yang statusnya 'unpaid' milik member ini
        $unpaidFines = Denda::where('status', 'unpaid')
            ->whereHas('peminjaman', function($q) use ($member) {
                $q->where('id_member', $member->id_member);
            })
            // Tambahkan relasi untuk mendapatkan detail buku (CopyBuku -> Buku) dan Peminjaman
            ->with(['copyBuku.buku', 'peminjaman.detailPeminjaman']) 
            ->get();

        // Hitung total keseluruhan denda
        $totalUnpaidFine = $unpaidFines->sum('total_denda');

        $groupedFines = $unpaidFines->groupBy('nomor_pinjam')->map(function ($items, $nomor_pinjam) {
        $tgl_jatuh_tempo = $items->first()->peminjaman->tgl_kembali ?? null;

        return [
            'nomor_pinjam' => $nomor_pinjam,
            'tgl_jatuh_tempo' => $tgl_jatuh_tempo,
            'total_denda_pinjaman' => $items->sum('total_denda'),
            'books' => $items->map(function ($denda) {
                $detail = $denda->peminjaman
                    ? $denda->peminjaman->detailPeminjaman->firstWhere('id_buku_copy', $denda->id_buku_copy)
                    : null;

                return [
                    'id_denda' => $denda->id_denda,
                    'tgl_pinjam' => $denda->peminjaman->tgl_pinjam ?? null,
                    'tgl_kembali' => $detail->tgl_kembali ?? null,
                    'judul' => $denda->copyBuku->buku->judul ?? 'N/A',
                    'url_foto_cover' => $denda->copyBuku->buku->url_foto_cover ?? null,
                    'penulis' => $denda->copyBuku->buku->penulis ?? 'N/A',
                    'id_buku_copy' => $denda->id_buku_copy,
                    'hari_telat' => $denda->hari_telat,
                    'denda_per_buku' => $denda->total_denda,
                    'status' => $denda->status,
                ];
            }),
        ];
    })->values();


        return response()->json([
            'status' => true,
            'message' => 'Detail denda yang belum dibayar berhasil dimuat.',
            'total_denda_keseluruhan' => $totalUnpaidFine,
            'data' => $groupedFines
        ]);
    }

    public function showPaidFineDetails()
    {
        $member = Auth::guard('member')->user();

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'Member tidak terautentikasi.'
            ], 401);
        }

        // Ambil semua denda yang sudah dibayar milik member ini beserta relasi yang dibutuhkan
        $paidFines = Denda::where('status', 'paid')
            ->whereHas('peminjaman', function ($q) use ($member) {
                $q->where('id_member', $member->id_member);
            })
            ->with([
                'copyBuku.buku',
                'peminjaman.detailPeminjaman',
                'pembayaranDenda',
            ])
            ->get();

        // Hitung total keseluruhan denda yang sudah dibayar
        $totalPaidFine = $paidFines->sum('total_denda');

        // Kelompokkan per nomor pinjam
        $groupedFines = $paidFines->groupBy('nomor_pinjam')->map(function ($items, $nomor_pinjam) {
            $tgl_jatuh_tempo = $items->first()->peminjaman->tgl_kembali ?? null;

            return [
                'nomor_pinjam' => $nomor_pinjam,
                'tgl_jatuh_tempo' => $tgl_jatuh_tempo,
                'total_denda_pinjaman' => $items->sum('total_denda'),
                'books' => $items->map(function ($denda) {
                    $detail = $denda->peminjaman
                        ? $denda->peminjaman->detailPeminjaman->firstWhere('id_buku_copy', $denda->id_buku_copy)
                        : null;

                    // Ambil pembayaran terbaru jika ada
                    $payment = $denda->pembayaranDenda
                        ? $denda->pembayaranDenda->sortByDesc('tgl_bayar')->first()
                        : null;

                    return [
                        'id_denda' => $denda->id_denda,
                        'tgl_pinjam' => $denda->peminjaman->tgl_pinjam ?? null,
                        'tgl_kembali' => $detail->tgl_kembali ?? null,
                        'judul' => $denda->copyBuku->buku->judul ?? 'N/A',
                        'url_foto_cover' => $denda->copyBuku->buku->url_foto_cover ?? null,
                        'penulis' => $denda->copyBuku->buku->penulis ?? 'N/A',
                        'id_buku_copy' => $denda->id_buku_copy,
                        'hari_telat' => $denda->hari_telat,
                        'denda_per_buku' => $denda->total_denda,
                        'status' => $denda->status,
                        'metode' => $payment->metode ?? null,
                        'tgl_bayar' => $payment->tgl_bayar ?? null,
                    ];
                }),
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Detail denda yang sudah dibayar berhasil dimuat.',
            'total_denda_keseluruhan' => $totalPaidFine,
            'data' => $groupedFines
        ]);
    }





    public function bayarDenda(Request $request)
    {
        $request->validate([
            'id_denda' => 'required|array', 
            'metode'    => 'required|in:cash,transfer,qris,ewallet',
        ]);

        $member = Auth::guard('member')->user();

        return DB::transaction(function () use ($request, $member) {
            // ambil denda yang dipilih dan belum dibayar
            $dendaList = Denda::whereIn('id_denda', $request->id_denda)
                        ->where('status', 'unpaid')
                        ->whereHas('peminjaman', function($q) use ($member){
                            $q->where('id_member', $member->id_member);
                        })
                        ->lockForUpdate()
                        ->get();

            if ($dendaList->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Denda tidak ditemukan atau sudah dibayar.'
                ], 404);
            }

            // total pembayaran
            $totalBayar = $dendaList->sum('total_denda');

            // record pembayaran
            $pembayaran = PembayaranDenda::create([
                'id_member' => $member->id_member,
                'tgl_bayar' => now(),
                'total'     => $totalBayar,
                'metode'    => $request->metode,
            ]);

            // detail pembayaran
            foreach ($dendaList as $denda) {
                DetailPembayaranDenda::create([
                    'id_pembayaran' => $pembayaran->id_pembayaran,
                    'id_denda'      => $denda->id_denda,
                    'nominal_bayar'  => $denda->total_denda,
                ]);

                // update status denda jadi lunas
                $denda->update(['status' => 'paid']);
            }

            $pembayaran = $pembayaran->fresh('detailPembayaran.denda');

            return response()->json([
                'status' => true,
                'message' => 'Pembayaran berhasil.',
                'data'   => $pembayaran
            ]);
        });
    }

    public function riwayatPembayaran()
    {
        $member = Auth::guard('member')->user();

        $riwayat = PembayaranDenda::with('detailPembayaran.denda.copyBuku')
                    ->where('id_member', $member->id_member)
                    ->orderBy('tgl_bayar', 'desc')
                    ->get();

        return response()->json([
            'status' => true,
            'message' => 'Riwayat pembayaran',
            'data' => $riwayat
        ]);
    }
}

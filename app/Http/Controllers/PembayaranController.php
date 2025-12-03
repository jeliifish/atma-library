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
   public function listUnpaid(Request $request)
    {
        $member = Auth::guard('member')->user();

        $denda = Denda::with([
                'peminjaman' => function ($q) {
                    $q->select(
                        'nomor_pinjam',
                        'id_member',
                        'tgl_pinjam',
                        'tgl_kembali'      // jatuh tempo
                    );
                },
                'detailPeminjaman' => function ($q) {
                    $q->select(
                        'id_detail',
                        'nomor_pinjam',
                        'id_buku_copy',
                        'tgl_kembali' // tanggal kembali sebenarnya
                    );
                },
                'copyBuku.buku' => function ($q) {
                    $q->select(
                        'id_buku',
                        'judul',
                        'penulis',
                        'url_foto_cover'
                    );
                },
            ])
            ->whereIn('status', 'unpaid')
            ->whereHas('peminjaman', function ($q) use ($member) {
                $q->where('id_member', $member->id_member);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $data = $denda->map(function ($item) {
            return [
                'id_denda'         => $item->id_denda,
                'nomor_pinjam'     => $item->nomor_pinjam,
                'id_buku_copy'     => $item->id_buku_copy,
                'total_denda'      => $item->total_denda,
                'status_denda'     => $item->status,

                // dari peminjaman
                'tgl_pinjam'       => optional($item->peminjaman)->tgl_pinjam,
                'tgl_jatuh_tempo'  => optional($item->peminjaman)->tgl_kembali,

                // dari detail peminjaman (per copy)
                'tgl_dikembalikan' => optional($item->detailPeminjaman)->tgl_kembali,

                // info buku
                'judul'            => optional(optional($item->copyBuku)->buku)->judul,
                'penulis'          => optional(optional($item->copyBuku)->buku)->penulis,
                'url_foto_cover'   => optional(optional($item->copyBuku)->buku)->url_foto_cover,
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => 'Daftar denda yang belum dibayar',
            'data'    => $data,
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
                        ->where('status', 'belum')
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
                $denda->update(['status' => 'lunas']);
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

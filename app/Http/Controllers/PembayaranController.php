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
    //daftar denda yang belum dibayar
    public function daftarDenda()
    {
        $member = Auth::guard('member')->user();

        $denda = Denda::where('status', 'belum')
                    ->whereHas('peminjaman', function($q) use ($member){
                        $q->where('id_member', $member->id_member);
                    })
                    ->with('copyBuku', 'peminjaman')
                    ->get();

        return response()->json([
            'status' => true,
            'message' => 'Daftar denda yang belum dibayar',
            'data' => $denda
        ]);
    }

    public function bayarDenda(Request $request)
    {
        $request->validate([
            'denda_ids' => 'required|array', 
            'metode'    => 'required|in:cash,transfer,qris,ewallet',
        ]);

        $member = Auth::guard('member')->user();

        return DB::transaction(function () use ($request, $member) {
            // ambil denda yang dipilih dan belum dibayar
            $dendaList = Denda::whereIn('id_denda', $request->denda_ids)
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
                'status'    => 'lunas',
            ]);

            // detail pembayaran
            foreach ($dendaList as $denda) {
                DetailPembayaranDenda::create([
                    'id_pembayaran' => $pembayaran->id_pembayaran,
                    'id_denda'      => $denda->id_denda,
                    'jumlah_bayar'  => $denda->total_denda,
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

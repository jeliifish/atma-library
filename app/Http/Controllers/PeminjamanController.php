<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\CopyBuku;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use App\Models\DetailPeminjaman;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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

    public function showLatest()
    {
        try{
            $member = Auth::guard('member')->user();
            $peminjaman = Peminjaman::with([
                'member',
                'petugas',
                'detailPeminjaman.copyBuku'
            ])
            ->where('id_member', $member->id_member)
            ->latest('nomor_pinjam') // bisa diganti latest('tgl_pinjam') kalau mau based on tanggal
            ->first();

            // $peminjaman = Peminjaman::where('id_member', $member->id_member)
            //                 ->where('nomor_pinjam', $nomor_pinjam)
            //                 ->with('detailPeminjaman.copyBuku')
            //                 ->first();

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
        }catch (\Exception $e) {
            return response()->json([
                    'status' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
        }
        
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
        try {
            $request->validate([
                'status' => 'required|in:approved,rejected',
            ]);

            // pastikan yang login petugas
            $petugas = Auth::guard('petugas')->user();
            if (!$petugas) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Akses ditolak. Hanya petugas yang dapat mengubah status peminjaman.'
                ], 403);
            }

            return DB::transaction(function () use ($request, $petugas, $nomor_pinjam) {

                // HEADER: cuma satu peminjaman yg dikunci
                $peminjaman = Peminjaman::with('detailPeminjaman')
                    ->lockForUpdate()
                    ->find($nomor_pinjam);

                if (!$peminjaman) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Peminjaman tidak ditemukan.'
                    ], 404);
                }

                $newStatus = $request->status;

                // update header
                $peminjaman->update([
                    'status'     => $newStatus,
                    'id_petugas' => $petugas->id_petugas,
                ]);

                // DETAIL: HANYA milik nomor_pinjam INI & yg statusnya pending
                $details = DetailPeminjaman::where('nomor_pinjam', $nomor_pinjam)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->get();

                    \Log::info('APPROVE PEMINJAMAN', [
                        'nomor_pinjam_header' => $nomor_pinjam,
                        'detail_yang_keupdate' => $details->map(function ($d) {
                            return [
                                'nomor_pinjam' => $d->nomor_pinjam,
                                'id_buku_copy' => $d->id_buku_copy,
                                'status'       => $d->status,
                            ];
                        })->toArray()
                    ]);

                if ($newStatus === 'approved') {
                    foreach ($details as $detail) {
                        // status detail dari pending -> borrowed
                        DetailPeminjaman::where('nomor_pinjam', $detail->nomor_pinjam)
                            ->where('id_buku_copy', $detail->id_buku_copy)
                            ->update(['status' => 'borrowed']);

                        // copy buku jadi borrowed
                        CopyBuku::where('id_buku_copy', $detail->id_buku_copy)
                            ->update(['status' => 'borrowed']);
                    }
                }

                if ($newStatus === 'rejected') {
                    foreach ($details as $detail) {
                        DetailPeminjaman::where('nomor_pinjam', $detail->nomor_pinjam)
                            ->where('id_buku_copy', $detail->id_buku_copy)
                            ->update(['status' => 'rejected']);

                        CopyBuku::where('id_buku_copy', $detail->id_buku_copy)
                            ->update(['status' => 'available']);
                    }
                }

                return response()->json([
                    'status'  => true,
                    'message' => "Status peminjaman $nomor_pinjam berhasil diperbarui.",
                    'data'    => $peminjaman->fresh('detailPeminjaman')
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memperbarui status peminjaman: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }

    public function laporanPeminjamanPerHari(Request $request)
    {
        // Hanya petugas yang boleh akses laporan
        $petugas = Auth::guard('petugas')->user();
        if (!$petugas) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized. Hanya petugas yang dapat melihat laporan.',
            ], 403);
        }

        $start = $request->query('start')
            ? Carbon::parse($request->query('start'))->startOfDay()
            : now()->subDays(6)->startOfDay();

        $end = $request->query('end')
            ? Carbon::parse($request->query('end'))->endOfDay()
            : now()->endOfDay();

        $rows = Peminjaman::select(
                DB::raw('DATE(tgl_pinjam) as tanggal'),
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('tgl_pinjam', [$start, $end])
            ->where('status', '!=', 'draft')     // supaya draft nggak ikut ke laporan
            ->groupBy(DB::raw('DATE(tgl_pinjam)'))
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal'); // jadi array dengan key = 'YYYY-MM-DD'

        $period = CarbonPeriod::create($start, $end);
        $data = [];

        foreach ($period as $date) {
            $tanggal = $date->toDateString(); // format: 2025-12-02
            $data[] = [
                'tanggal' => $tanggal,
                'total'   => isset($rows[$tanggal]) ? (int)$rows[$tanggal]->total : 0,
            ];
        }

        return response()->json([
            'status'  => true,
            'message' => 'Laporan peminjaman per hari.',
            'data'    => $data,
            'meta'    => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ],
        ]);
    }

    public function updateStatusBulk(Request $request)
    {
        try {
            $request->validate([
                'status' => 'required|in:approved,rejected',
                'nomor_pinjam' => 'required|array',
                'nomor_pinjam.*' => 'integer',
            ]);

            $petugas = Auth::guard('petugas')->user();
            if (!$petugas) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Akses ditolak. Hanya petugas yang dapat mengubah status peminjaman.'
                ], 403);
            }

            return DB::transaction(function () use ($request, $petugas) {
                $newStatus = $request->status;
                $nomorPinjamList = $request->nomor_pinjam;

                $peminjamanList = Peminjaman::with('detailPeminjaman')
                    ->whereIn('nomor_pinjam', $nomorPinjamList)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('nomor_pinjam');

                $notFound = array_values(array_diff($nomorPinjamList, $peminjamanList->keys()->all()));
                if (!empty($notFound)) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Peminjaman tidak ditemukan.',
                        'data'    => ['not_found' => $notFound],
                    ], 404);
                }

                $updated = [];

                foreach ($peminjamanList as $nomorPinjam => $peminjaman) {
                    $peminjaman->update([
                        'status'     => $newStatus,
                        'id_petugas' => $petugas->id_petugas,
                    ]);

                    $details = DetailPeminjaman::where('nomor_pinjam', $nomorPinjam)
                        ->where('status', 'pending')
                        ->lockForUpdate()
                        ->get();

                    \Log::info('BULK APPROVE/REJECT PEMINJAMAN', [
                        'nomor_pinjam_header' => $nomorPinjam,
                        'detail_yang_keupdate' => $details->map(function ($d) {
                            return [
                                'nomor_pinjam' => $d->nomor_pinjam,
                                'id_buku_copy' => $d->id_buku_copy,
                                'status'       => $d->status,
                            ];
                        })->toArray()
                    ]);

                    if ($newStatus === 'approved') {
                        foreach ($details as $detail) {
                            DetailPeminjaman::where('nomor_pinjam', $detail->nomor_pinjam)
                                ->where('id_buku_copy', $detail->id_buku_copy)
                                ->update(['status' => 'borrowed']);

                            CopyBuku::where('id_buku_copy', $detail->id_buku_copy)
                                ->update(['status' => 'borrowed']);
                        }
                    }

                    if ($newStatus === 'rejected') {
                        foreach ($details as $detail) {
                            DetailPeminjaman::where('nomor_pinjam', $detail->nomor_pinjam)
                                ->where('id_buku_copy', $detail->id_buku_copy)
                                ->update(['status' => 'rejected']);

                            CopyBuku::where('id_buku_copy', $detail->id_buku_copy)
                                ->update(['status' => 'available']);
                        }
                    }

                    $updated[] = $peminjaman->fresh('detailPeminjaman');
                }

                return response()->json([
                    'status'  => true,
                    'message' => 'Status peminjaman berhasil diperbarui.',
                    'data'    => $updated,
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memperbarui status peminjaman: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }

    
    public function destroyPending($nomor_pinjam)
    {
        try {
            $petugas = Auth::guard('petugas')->user();
            if (!$petugas) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Akses ditolak. Hanya petugas yang dapat menghapus peminjaman.'
                ], 403);
            }

            return DB::transaction(function () use ($nomor_pinjam) {
                $peminjaman = Peminjaman::with('detailPeminjaman')
                    ->where('nomor_pinjam', $nomor_pinjam)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if (!$peminjaman) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Peminjaman pending tidak ditemukan.',
                    ], 404);
                }

                foreach ($peminjaman->detailPeminjaman as $detail) {
                    CopyBuku::where('id_buku_copy', $detail->id_buku_copy)
                        ->update(['status' => 'available']);
                }

                DetailPeminjaman::where('nomor_pinjam', $nomor_pinjam)->delete();
                $peminjaman->delete();

                return response()->json([
                    'status'  => true,
                    'message' => "Peminjaman pending {$nomor_pinjam} berhasil dihapus.",
                    'data'    => null
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal menghapus peminjaman pending: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }

}

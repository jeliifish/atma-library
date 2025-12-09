<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Member;
use App\Models\Peminjaman;
use App\Models\PembayaranDenda;
use App\Models\DetailPeminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function summary()
    {
        $totalBuku          = Buku::count();
        $totalMember        = Member::count();
        $detailAktif        = DetailPeminjaman::where('status', 'borrowed')->count();
        $totalBayar         = PembayaranDenda::sum('total');
        $peminjamanReturned = DetailPeminjaman::where('status', 'returned')->count();

        return response()->json([
            'total_buku'           => $totalBuku,
            'total_member'         => $totalMember,
            'detail_aktif'         => $detailAktif,     
            'peminjaman_returned'  => $peminjamanReturned,
            'total_denda_bayar'    => $totalBayar,     
            'total_bayar'          => $totalBayar,
        ]);
    }



    public function borrowingByCategory(Request $request)
    {
        $petugas = Auth::guard('petugas')->user();
        if (!$petugas) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized. Hanya petugas yang dapat mengakses laporan ini.',
            ], 403);
        }

        $start = $request->query('start')
            ? Carbon::parse($request->query('start'))->startOfMonth()
            : Carbon::now()->subMonths(5)->startOfMonth();

        $end = $request->query('end')
            ? Carbon::parse($request->query('end'))->endOfMonth()
            : Carbon::now()->endOfMonth();

        $baseQuery = DB::table('detail_peminjaman as dp')
            ->join('peminjaman as p', 'dp.nomor_pinjam', '=', 'p.nomor_pinjam')
            ->join('copy_buku as cb', 'dp.id_buku_copy', '=', 'cb.id_buku_copy')
            ->join('buku as b', 'cb.id_buku', '=', 'b.id_buku')
            ->join('buku_kategori as bk', 'b.id_buku', '=', 'bk.id_buku')
            ->join('kategori as k', 'bk.id_kategori', '=', 'k.id_kategori')
            ->whereBetween('p.tgl_pinjam', [$start, $end])
            ->whereIn('dp.status', ['borrowed', 'returned'])
            ->whereIn('p.status', ['approved', 'completed']);

        $volumeByCategory = (clone $baseQuery)
            ->select('k.id_kategori', 'k.nama_kategori', DB::raw('COUNT(*) as total_peminjaman'))
            ->groupBy('k.id_kategori', 'k.nama_kategori')
            ->orderByDesc('total_peminjaman')
            ->get();

        $monthlyTrend = (clone $baseQuery)
            ->select(
                DB::raw("DATE_FORMAT(p.tgl_pinjam, '%Y-%m') as bulan"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        $monthlyTrendByCategory = (clone $baseQuery)
            ->select(
                DB::raw("DATE_FORMAT(p.tgl_pinjam, '%Y-%m') as bulan"),
                'k.id_kategori',
                'k.nama_kategori',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('bulan', 'k.id_kategori', 'k.nama_kategori')
            ->orderBy('bulan')
            ->get();

        $topCategories = $volumeByCategory->take(5)->values();
        $bottomCategories = $volumeByCategory->sortBy('total_peminjaman')->take(5)->values();

        return response()->json([
            'status'  => true,
            'message' => 'Borrowing by category/genre.',
            'data'    => [
                'volume_by_category'      => $volumeByCategory,
                'monthly_trend'           => $monthlyTrend,
                'monthly_trend_by_category' => $monthlyTrendByCategory,
                'top_categories'          => $topCategories,
                'bottom_categories'       => $bottomCategories,
            ],
            'meta'    => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ],
        ]);
    }

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
                    'total_denda'    => $p->total_denda ?? 0,
                ];
            });

        return response()->json([
            'data' => $peminjaman,
        ]);
    }

    // daftar pembayaran denda terbaru (misal 20 terakhir)
    public function fines()
    {
        $pembayaran = PembayaranDenda::with(['member', 'detailPembayaran.denda'])
            ->orderBy('tgl_bayar', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($p) {
                return [
                    'id_pembayaran' => $p->id_pembayaran,
                    'tgl_bayar'     => $p->tgl_bayar,
                    'nama_member'   => optional($p->member)->nama,
                    'total'         => $p->total,
                    'total_bayar'   => $p->total,     
                    'metode'        => $p->metode,
                    'keterangan'    => $p->metode,    
                    'detail'        => $p->detailPembayaran->map(function ($d) {
                        return [
                            'id_detail_pembayaran' => $d->id_detail_pembayaran,
                            'id_denda'             => $d->id_denda,
                            'nominal_bayar'        => $d->nominal_bayar,
                        ];
                    }),
                ];
            });

        return response()->json([
            'data' => $pembayaran,
        ]);
    }
}

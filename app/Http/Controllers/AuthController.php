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
                    'id'    => $member->id_member,
                    'nama'  => $member->nama,
                    'email' => $member->email,
                    'role'  => 'member',
                    'url_foto_profil' => $member->url_foto_profil
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
                    'id'    => $petugas->id_petugas,
                    'nama'  => $petugas->nama,
                    'email' => $petugas->email,
                    'role'  => 'petugas',
                    'url_foto_profil' => $petugas->url_foto_profil
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
                    ->where('status', 'available')
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
                    'status'       => 'pending',
                ]);

                // tandain copy buku yang lagi diajuin
                $copy->update(['status' => 'borrowed']);

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
            
            $draft->update(['status' => 'pending']);
           
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
        try {
            $member = Auth::guard('member')->user();
            if (!$member) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Member tidak ditemukan.'
                ], 404);
            }

            $validated = $request->validate([
                'nomor_pinjam' => 'required|exists:peminjaman,nomor_pinjam',
                'id_buku_copy' => 'required|exists:copy_buku,id_buku_copy',
            ]);

            return DB::transaction(function () use ($member, $validated) {
                $peminjaman = Peminjaman::where('nomor_pinjam', $validated['nomor_pinjam'])
                    ->where('id_member', $member->id_member)
                    ->where('status', 'approved')
                    ->first();

                if (!$peminjaman) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Peminjaman tidak ditemukan atau belum disetujui.'
                    ], 404);
                }

                $detail = DetailPeminjaman::where('nomor_pinjam', $peminjaman->nomor_pinjam)
                    ->where('id_buku_copy', $validated['id_buku_copy'])
                    ->where('status', 'borrowed')
                    ->lockForUpdate()
                    ->first();

                if (!$detail) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Detail peminjaman tidak ditemukan atau sudah dikembalikan.'
                    ], 404);
                }

                // update detail → returned (pakai query, bukan instance update)
                DetailPeminjaman::where('nomor_pinjam', $detail->nomor_pinjam)
                    ->where('id_buku_copy', $detail->id_buku_copy)
                    ->update([
                        'status'      => 'returned',
                        'tgl_kembali' => now()->addDays(8), // tgl real dikembalikan
                    ]);

                // update copy → available
                $copy = CopyBuku::where('id_buku_copy', $validated['id_buku_copy'])
                    ->lockForUpdate()
                    ->first();
                if ($copy) {
                    $copy->update(['status' => 'available']);
                }

                // hitung denda
                $due = Carbon::parse($peminjaman->tgl_kembali)->startOfDay();
                $now = Carbon::now()->addDays(8)->startOfDay();
                $hariTelat = max(0, $due->diffInDays($now, false));

                if ($hariTelat > 0) {
                    $hargaPerHari = 1000;
                    $totalDenda   = $hariTelat * $hargaPerHari;

                    Denda::create([
                        'nomor_pinjam'   => $peminjaman->nomor_pinjam,
                        'id_buku_copy'   => $validated['id_buku_copy'],
                        'hari_telat'     => $hariTelat,
                        'harga_per_hari' => $hargaPerHari,
                        'total_denda'    => $totalDenda,
                        'status'         => 'unpaid'
                    ]);
                }

                $masihAktif = DetailPeminjaman::where('nomor_pinjam', $peminjaman->nomor_pinjam)
                    ->whereIn('status', ['pending', 'borrowed'])
                    ->exists();

                if (!$masihAktif) {
                    $peminjaman->update(['status' => 'completed']);
                }

                $peminjaman = Peminjaman::with([
                    'detailPeminjaman.copyBuku',
                    'member',
                    'petugas'
                ])->find($peminjaman->nomor_pinjam);

                return response()->json([
                    'status'     => true,
                    'message'    => 'Pengembalian buku berhasil.',
                    'peminjaman' => $peminjaman,
                ]);
            });

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Terjadi kesalahan saat mengembalikan buku: ' . $e->getMessage(),
            ], 500);
        }
    }




   public function returnAllBooks(Request $request)
    {
        try {
            $member = Auth::guard('member')->user();

            if (!$member) {
                return response()->json([
                    'status' => false,
                    'message' => 'Member tidak ditemukan.'
                ], 404);
            }

            return DB::transaction(function () use ($member) {

                // Ambil semua DETAIL yg masih BORROWED saja
                $details = DetailPeminjaman::whereHas('peminjaman', function ($q) use ($member) {
                        $q->where('id_member', $member->id_member)
                        ->where('status', 'approved');   // hanya peminjaman yg aktif
                    })
                    ->where('status', 'borrowed')        
                    ->lockForUpdate()
                    ->get();

                if ($details->isEmpty()) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Tidak ada buku yang masih dipinjam.'
                    ], 404);
                }

                $updated = [];

                foreach ($details as $detail) {

                    // update detail → returned
                    DetailPeminjaman::where('nomor_pinjam', $detail->nomor_pinjam)
                        ->where('id_buku_copy', $detail->id_buku_copy)
                        ->update([
                            'status'      => 'returned',
                            'tgl_kembali' => now()->addDays(8),
                        ]);

                    // ubah copy menjadi available
                    CopyBuku::where('id_buku_copy', $detail->id_buku_copy)
                        ->update(['status' => 'available']);

                    // cek denda
                    $due = Carbon::parse($detail->peminjaman->tgl_kembali)->startOfDay();
                    $now = Carbon::now()->startOfDay()->addDays(8);
                    $hariTelat = max(0, $due->diffInDays($now, false));

                    if ($hariTelat > 0) {
                        $hargaPerHari = 1000;
                        $totalDenda   = $hariTelat * $hargaPerHari;

                        Denda::create([
                            'nomor_pinjam'   => $detail->nomor_pinjam,
                            'id_buku_copy'   => $detail->id_buku_copy,
                            'hari_telat'     => $hariTelat,
                            'harga_per_hari' => $hargaPerHari,
                            'total_denda'    => $totalDenda,
                            'status'         => 'unpaid',
                        ]);
                    }

                    $updated[] = $detail->id_buku_copy;
                }

                // UPDATE STATUS PEMINJAMAN: hanya kalau TIDAK ADA lagi pending/borrowed
                $peminjamanIds = $details->pluck('nomor_pinjam')->unique();

                foreach ($peminjamanIds as $nomor) {

                    // cek masih ada detail yg statusnya aktif (pending/borrowed) atau tidak
                    $masihAktif = DetailPeminjaman::where('nomor_pinjam', $nomor)
                        ->whereIn('status', ['pending', 'borrowed'])   
                        ->exists();

                    if (!$masihAktif) {
                        // baru boleh completed kalau udah nggak ada pending & borrowed
                        Peminjaman::where('nomor_pinjam', $nomor)->update([
                            'status' => 'completed',
                        ]);
                    }
                }

                return response()->json([
                    'status'         => true,
                    'message'        => 'All borrowed books have been returned successfully.',
                    'returned_books' => $updated,
                ]);
            });

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal mengembalikan semua buku: ' . $e->getMessage(),
            ], 500);
        }
    }


     // untuk ambil detail peminjaman yg masih draft
    public function getDraft()
    {
         $member = Auth::guard('member')->user();

        $draft = Peminjaman::with(['detailPeminjaman.copyBuku.buku'])
            ->where('id_member', $member->id_member)
            ->where('status', 'draft')
            ->first();
            if (!$draft) {
                return response()->json([
                    'status'  => true,
                    'message' => 'Draft kosong.',
                    'data'    => null,
                ]);
            }

        return response()->json([
            'status'  => true,
            'message' => 'Draft ditemukan.',
            'data'    => $draft,
        ]);
    }

     // untuk ambil detail peminjaman yg masih draft
     public function getPendingAndBorrowed()
    {
        try {
            $member = Auth::guard('member')->user();

            if (!$member) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Member not authenticated',
                ], 401);
            }

          $rows = DB::table('peminjaman as p')
            ->join('detail_peminjaman as d', 'p.nomor_pinjam', '=', 'd.nomor_pinjam')
            ->join('copy_buku as c', 'd.id_buku_copy', '=', 'c.id_buku_copy')
            ->join('buku as b', 'c.id_buku', '=', 'b.id_buku')
            ->where('p.id_member', $member->id_member)
            ->where(function ($q) {
                // Peminjaman pending => detail harus pending
                $q->where(function ($q) {
                    $q->where('p.status', 'pending')
                    ->where('d.status', 'pending');
                })
                // Peminjaman approved => detail boleh pending/borrowed (sesuai kebutuhan)
                ->orWhere(function ($q) {
                    $q->where('p.status', 'approved')
                    ->whereIn('d.status', ['pending', 'borrowed']);
                });
            })
            ->orderBy('p.nomor_pinjam', 'asc')
            ->select([
                'p.nomor_pinjam',
                'p.tgl_pinjam',
                'p.status as status_peminjaman', // opsional, biar FE tahu status transaksi
                'd.status as status_detail',
                'd.id_buku_copy',
                'b.judul',
                'b.penulis',
                'b.url_foto_cover',
            ])
            ->get();


            return response()->json([
                'status'  => true,
                'message' => 'Pending & borrowed loan details loaded.',
                'data'    => $rows,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

      // untuk ambil detail peminjaman yg masih draft
    public function pendingRequests()
    {
        try {
            // kalau mau dibatasi hanya untuk petugas yang login:
            $petugas = Auth::guard('petugas')->user();

            if (!$petugas) {
                return response()->json([
                    'status'  => false,
                    'message' => 'petugas not authenticated',
                ], 401);
            }

            $rows = DB::table('peminjaman as p')
                ->join('detail_peminjaman as d', 'p.nomor_pinjam', '=', 'd.nomor_pinjam')
                ->join('copy_buku as c', 'd.id_buku_copy', '=', 'c.id_buku_copy')
                ->join('buku as b', 'c.id_buku', '=', 'b.id_buku')
                ->join('member as m', 'p.id_member', '=', 'm.id_member')
                // >> hanya peminjaman yang pending
                ->where('p.status', 'pending')
                // >> hanya detail yang masih pending (belum approved / borrowed)
                ->where('d.status', 'pending')
                ->orderBy('p.nomor_pinjam', 'asc')
                ->select([
                    'p.nomor_pinjam',
                    'p.tgl_pinjam',
                    'p.status as status_peminjaman',

                    'd.id_buku_copy',
                    'd.status as status_detail',

                    'b.judul',
                    'b.penulis',
                    'b.url_foto_cover',

                    'm.id_member',
                    'm.url_foto_profil',
                    'm.nama',
                ])
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Pending borrow requests loaded.',
                'data'    => $rows,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load pending borrow requests: ' . $e->getMessage(),
            ], 500);
        }
    }

    // untuk ambil riwayat peminjaman yg SUDAH dikembalikan (detail = returned)
    public function getBorrowedHistory()
    {
        try {
            $member = Auth::guard('member')->user();

            if (!$member) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Member not authenticated',
                ], 401);
            }

            $rows = DB::table('peminjaman as p')
                ->join('detail_peminjaman as d', 'p.nomor_pinjam', '=', 'd.nomor_pinjam')
                ->join('copy_buku as c', 'd.id_buku_copy', '=', 'c.id_buku_copy')
                ->join('buku as b', 'c.id_buku', '=', 'b.id_buku')
                ->where('p.id_member', $member->id_member)
                // buang cart / draft, history = transaksi yang sudah pernah diajukan
                ->where('p.status', '!=', 'draft')
                // hanya detail yang SUDAH dikembalikan atau ditolak
                ->whereIn('d.status', ['returned', 'rejected'])
                // boleh pakai tgl_kembali desc biar yg terbaru di atas
                ->orderBy('d.tgl_kembali', 'desc')
                ->select([
                    'p.nomor_pinjam',
                    'p.tgl_pinjam',
                    'd.tgl_kembali',
                    'd.status as status_detail', // = returned
                    'd.id_buku_copy',
                    'b.judul',
                    'b.penulis',
                    'b.url_foto_cover',
                ])
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Returned loan history loaded.',
                'data'    => $rows,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

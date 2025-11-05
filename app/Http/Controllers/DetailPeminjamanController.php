<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DetailPeminjamanController extends Controller
{

   

    // public function store(Request $request)
    // {
    //     $peminjaman = Peminjaman::find($request->nomor_pinjam);
    //     $copyBuku = CopyBuku::find($request->id_buku_copy);
        

    //     $detailPeminjaman = DetailPeminjaman::create([
    //         'nomor_pinjam' => $peminjaman->nomor_pinjam,
    //         'id_buku_copy' => $request->id_buku_copy,
    //         'tgl_kembali' => $request->tgl_kembali,
    //     ]);


    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Detail peminjaman berhasil ditambahkan.',
    //         'data'    => $detailPeminjaman
    //     ]);
    // }
}

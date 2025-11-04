<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use App\Models\CopyBuku;
use Illuminate\Http\Request;

class PeminjamanController extends Controller
{
    // TAMPILKAN SEMUA PEMINJAMAN
    public function index()
    {
        $peminjaman = Peminjaman::with('detail.copyBuku.buku', 'member', 'petugas')->get();
        return view('peminjaman.index', compact('peminjaman'));
    }

    // AJUKAN PEMINJAMAN (Oleh Member)
    public function store(Request $request)
    {
        $request->validate([
            'id_member' => 'required',
            'id_buku_copy' => 'required|array',
            'tgl_kembali' => 'required|date'
        ]);

        // Buat transaksi
        $peminjaman = Peminjaman::create([
            'id_member' => $request->id_member,
            'id_petugas' => auth()->user->id(), // atau null jika pengajuan dari member
            'tgl_pinjam' => now(),
            'tgl_kembali' => $request->tgl_kembali
        ]);

        // Isi detail_peminjaman STATUS AWAL MENUNGGU
        foreach ($request->id_buku_copy as $copy) {
            DetailPeminjaman::create([
                'nomor_pinjam' => $peminjaman->nomor_pinjam,
                'id_buku_copy' => $copy,
                'id_member' => $request->id_member,
                'status' => 'menunggu'
            ]);
        }

        return back()->with('success', 'Peminjaman diajukan, menunggu persetujuan petugas.');
    }

    // PETUGAS MENYETUJUI PEMINJAMAN
    public function approve($id)
    {
        $detail = DetailPeminjaman::where('nomor_pinjam', $id)->get();

        foreach ($detail as $d) {
            $d->update(['status' => 'disetujui']);
            $d->copyBuku->update(['status' => 'dipinjam']);
        }

        return back()->with('success', 'Peminjaman disetujui.');
    }

    // PETUGAS MENOLAK PEMINJAMAN
    public function reject($id)
    {
        $detail = DetailPeminjaman::where('nomor_pinjam', $id)->get();

        foreach ($detail as $d) {
            $d->update(['status' => 'ditolak']);
        }

        return back()->with('warning', 'Peminjaman ditolak.');
    }

    // PROSES PENGEMBALIAN
    public function pengembalian($id)
    {
        $detail = DetailPeminjaman::where('nomor_pinjam', $id)->get();

        foreach ($detail as $d) {
            $d->update(['status' => 'dikembalikan']);
            $d->copyBuku->update(['status' => 'dikembalikan']);
        }

        return back()->with('success', 'Buku dikembalikan.');
    }
}

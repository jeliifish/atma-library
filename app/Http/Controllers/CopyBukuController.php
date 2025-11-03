<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Petugas;
use App\Models\CopyBuku;
use App\Models\Buku;
use Illuminate\Validation\ValidationException;

class CopyBukuController extends Controller
{

    public function index()
    {
        $copyBuku = CopyBuku::all();

        return response()->json([
            'status' => true,
            'message' => 'Daftar semua copy buku',
            'data' => $copyBuku
        ], 200);
    }

    //store new buku copy
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_buku' => 'required|exists:buku,id_buku',
            'rak' => 'required|string',
        ]);

        $buku = Buku::where('id_buku', $validated['id_buku'])->first();
        if (!$buku) {
            return response()->json([
                'status' => false,
                'message' => 'Buku tidak ditemukan.',
                'data' => []
            ], 404);    
        }

        $validated['status'] = 'tersedia';
        
        $copyBuku = CopyBuku::create($validated);
         
        return response()->json([
            'status' => true,
            'message' => 'Berhasil menambah copy buku.',
            'data' => $copyBuku
        ], 200);
    }

    public function show($id_buku_copy)
    {
        try {
            $copyBuku = CopyBuku::find($id_buku_copy);

            if (!$copyBuku) {
                return response()->json([
                    'status' => false,
                    'message' => "Copy Buku dengan ID $id_buku_copy tidak ditemukan.",
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Detail buku berhasil ditemukan.',
                'data' => $copyBuku
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function update($id_buku_copy)
    {
        try {
            $copyBuku = CopyBuku::find($id_buku_copy);

            if (!$copyBuku) {
                return response()->json([
                    'status' => false,
                    'message' => "Copy Buku dengan ID $id_buku_copy tidak ditemukan.",
                    'data' => []
                ], 404);
            }

            $validated = request()->validate([
                'rak' => 'sometimes|string',
                'status' => 'sometimes|in:dipinjam,tersedia',
            ]);

            $copyBuku->update($validated);

            return response()->json([
                'status' => true,
                'message' => "Copy Buku dengan ID $id_buku_copy berhasil diperbarui.",
                'data' => $copyBuku
            ], 200);

        }catch (\Illuminate\Validation\ValidationException $e) {
            
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id_buku_copy)
    {
        try {
            
            $copyBuku = CopyBuku::find($id_buku_copy);

            if (!$copyBuku) {
                return response()->json([
                    'status' => false,
                    'message' => "Copy Buku dengan ID $id_buku_copy tidak ditemukan.",
                    'data' => []
                ], 404);
            }

            //hapus
            $copyBuku->delete();

            return response()->json([
                'status' => true,
                 'message' => "Copy Buku dengan ID $id_buku_copy berhasil dihapus.",
            ], 200);

        } catch (\Exception $e) {
            
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus copy buku: ' . $e->getMessage()
            ], 500);
        }
    }
}

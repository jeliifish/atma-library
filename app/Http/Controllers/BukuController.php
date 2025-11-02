<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class BukuController extends Controller
{
    // Aktifkan middleware auth sanctum
    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum')->except(['index', 'show']);
    // }

    /**
     * Tampilkan semua data buku
     */
    public function index()
    {
        $buku = Buku::all();

        return response()->json([
            'status' => true,
            'message' => 'Daftar semua buku',
            'data' => $buku
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            
            $validated = $request->validate(
                [
                    'judul' => 'required|string|max:255',
                    'penulis' => 'required|string|max:255',
                    'penerbit' => 'required|string|max:255',
                    'ISBN' => 'required|string|max:50|unique:buku,ISBN',
                    'tahun_terbit' => 'required|integer|min:1000|max:' . date('Y'),
                    'url_foto_cover' => 'nullable|string|max:255',
                ],
                [
                    'judul.required' => 'Judul buku wajib diisi.',
                    'penulis.required' => 'Nama penulis wajib diisi.',
                    'penerbit.required' => 'Nama penerbit wajib diisi.',
                    'ISBN.required' => 'Nomor ISBN wajib diisi.',
                    'ISBN.unique' => 'Nomor ISBN sudah terdaftar.',
                    'tahun_terbit.required' => 'Tahun terbit wajib diisi.',
                    'tahun_terbit.integer' => 'Tahun terbit harus berupa angka.',
                    'tahun_terbit.min' => 'Tahun terbit tidak valid.',
                    'tahun_terbit.max' => 'Tahun terbit tidak boleh melebihi tahun sekarang.',
                ]
            );

            $buku = Buku::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Buku berhasil ditambahkan',
                'data' => $buku
            ], 201);

        } catch (Exception $e) {
            
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function show($id_buku)
    {
        try {
            $buku = Buku::find($id_buku);

            if (!$buku) {
                return response()->json([
                    'status' => false,
                    'message' => "Buku dengan ID $id_buku tidak ditemukan.",
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Detail buku berhasil ditemukan.',
                'data' => $buku
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function update(Request $request, $id_buku)
    {
        try {
            // cek buku tersedia atau ngga
            $buku = Buku::find($id_buku);

            if (!$buku) {
                return response()->json([
                    'status' => false,
                    'message' => 'Buku tidak ditemukan.'
                ], 404);
            }

            // Validasi data yang dikirim (hanya field yang diubah saja yang wajib)
            $validated = $request->validate([
                'judul' => 'sometimes|required|string|max:255',
                'penulis' => 'sometimes|required|string|max:255',
                'penerbit' => 'sometimes|required|string|max:255',
                'ISBN' => 'sometimes|required|string|max:50|unique:buku,ISBN,' . $id_buku . ',id_buku',
                'tahun_terbit' => 'sometimes|required|integer',
                'url_foto_cover' => 'nullable|string|max:255'
            ]);

            //update
            $buku->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Buku berhasil diperbarui.',
                'data' => $buku
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            
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

    public function destroy($id_buku)
    {
        try {
            
            $buku = Buku::find($id_buku);

            if (!$buku) {
                return response()->json([
                    'status' => false,
                    'message' => 'Buku tidak ditemukan.'
                ], 404);
            }

            //hapus
            $buku->delete();

            return response()->json([
                'status' => true,
                'message' => 'Buku berhasil dihapus.'
            ], 200);

        } catch (\Exception $e) {
            
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus buku: ' . $e->getMessage()
            ], 500);
        }
    }

}

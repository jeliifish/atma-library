<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Database\QueryException;

class KategoriController extends Controller
{
    /**
     * Tampilkan semua kategori
     */
    public function index()
    {
        try {
            $kategori = Kategori::all();

            return response()->json([
                'status' => true,
                'message' => 'Daftar semua kategori.',
                'data' => $kategori
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Tambahkan kategori baru
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori',
            ]);

            $kategori = Kategori::create([
                'nama_kategori' => $validated['nama_kategori']
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Kategori berhasil ditambahkan.',
                'data' => $kategori
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (QueryException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan database.',
                'data' => []
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan server.',
                'data' => []
            ], 500);
        }
    }



    /**
     * Tampilkan satu kategori berdasarkan ID
     */
    public function show($id_kategori)
    {
        try {
            $kategori = Kategori::find($id_kategori);

            if (!$kategori) {
                return response()->json([
                    'status' => false,
                    'message' => "Kategori dengan ID $id_kategori tidak ditemukan.",
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Detail kategori ditemukan.',
                'data' => $kategori
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Update data kategori
     */
    public function update(Request $request, $id_kategori)
    {
        try {
            $kategori = Kategori::find($id_kategori);

            if (!$kategori) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kategori tidak ditemukan.'
                ], 404);
            }

            $validated = $request->validate([
                'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori,' . $id_kategori . ',id_kategori'
            ], [
                'nama_kategori.required' => 'Nama kategori wajib diisi.',
                'nama_kategori.unique' => 'Nama kategori sudah ada.'
            ]);

            $kategori->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Kategori berhasil diperbarui.',
                'data' => $kategori
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Hapus kategori
     */
    public function destroy($id_kategori)
    {
        try {
            $kategori = Kategori::find($id_kategori);

            if (!$kategori) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kategori tidak ditemukan.'
                ], 404);
            }

            $kategori->delete();

            return response()->json([
                'status' => true,
                'message' => 'Kategori berhasil dihapus.'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}

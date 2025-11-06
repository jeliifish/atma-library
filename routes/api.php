<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\MemberMiddleware;
use App\Http\Middleware\PetugasMiddleware;

use App\Http\Controllers\MemberController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\CopyBukuController;
use App\Http\Controllers\PeminjamanController;

Route::post('/register/member', [MemberController::class, 'store']);
Route::post('/register/petugas', [PetugasController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// route public /api/buku/...
Route::get('/buku', [BukuController::class, 'index']);
Route::get('/buku/{id_buku}', [BukuController::class, 'show']);

// route public /api/copyBuku/...
Route::get('/copyBuku', [CopyBukuController::class, 'index']);
Route::get('/copyBuku/{id_buku_copy}', [CopyBukuController::class, 'show']);

//route member /api/member/...
Route::middleware(['auth:sanctum', MemberMiddleware::class])->prefix('member')->group(function () {
    
    Route::get('/profile', [MemberController::class, 'show']);
    Route::post('/profile/update', [MemberController::class, 'update']);
    Route::delete('/profile/delete', [MemberController::class, 'destroy']);
});

//route petugas /api/petugas/...
Route::middleware(['auth:sanctum', PetugasMiddleware::class])->prefix('petugas')->group(function () {
 
    Route::get('/profile', [PetugasController::class, 'show']);
    Route::post('/profile/update', [PetugasController::class, 'update']);
    Route::delete('/profile/delete', [PetugasController::class, 'destroy']);

    Route::post('/buku', [BukuController::class, 'store']);
    Route::put('/buku/{id_buku}', [BukuController::class, 'update']);
    Route::delete('/buku/{id_buku}', [BukuController::class, 'destroy']);

    Route::post('/copyBuku', [CopyBukuController::class, 'store']);
    Route::put('/copyBuku/{id_buku_copy}', [CopyBukuController::class, 'update']);
    Route::delete('/copyBuku/{id_buku_copy}', [CopyBukuController::class, 'destroy']);
});

// route kategori
Route::get('/kategori', [KategoriController::class, 'index']);
Route::post('/kategori', [KategoriController::class, 'store']);
Route::get('/kategori/{id_kategori}', [KategoriController::class, 'show']);
Route::put('/kategori/{id_kategori}', [KategoriController::class, 'update']);
Route::delete('/kategori/{id_kategori}', [KategoriController::class, 'destroy']);

// Menampilkan semua peminjaman
Route::get('/peminjaman', [PeminjamanController::class, 'index']);

// Mengajukan peminjaman (member melakukan request pinjam)
Route::post('/peminjaman', [PeminjamanController::class, 'store']);

// Petugas menyetujui peminjaman
Route::put('/peminjaman/{nomor_pinjam}/approve', [PeminjamanController::class, 'approve']);

// Petugas menolak peminjaman
Route::put('/peminjaman/{nomor_pinjam}/reject', [PeminjamanController::class, 'reject']);

// Mengembalikan buku
Route::put('/peminjaman/{nomor_pinjam}/kembalikan', [PeminjamanController::class, 'pengembalian']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

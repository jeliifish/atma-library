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
use App\Http\Controllers\DetailPeminjamanController;

Route::post('/register/member', [MemberController::class, 'store']);
Route::post('/register/petugas', [PetugasController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// route public /api. .
Route::get('/buku', [BukuController::class, 'index']);
Route::get('/buku/{id_buku}', [BukuController::class, 'show']);

// route public /api. .
Route::get('/copyBuku', [CopyBukuController::class, 'index']);
Route::get('/copyBuku/{id_buku_copy}', [CopyBukuController::class, 'show']);

// route public /api. .
route::get('/peminjaman/showLatest', [PeminjamanController::class, 'showLatest']);

//route member /api/member/...
Route::middleware(['auth:sanctum', MemberMiddleware::class])->prefix('member')->group(function () {
    
    Route::get('/profile', [MemberController::class, 'show']);
    Route::post('/profile/update', [MemberController::class, 'update']);
    Route::delete('/profile/delete', [MemberController::class, 'destroy']);

    Route::post('/peminjaman', [PeminjamanController::class, 'store']);
    Route::post('/detailPeminjaman', [AuthController::class, 'addToDraft']);
    Route::post('/detailPeminjaman/submit', [AuthController::class, 'submitDraft']);

    Route::put('/peminjaman/kembali', [AuthController::class, 'returnBook']);
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

    route::get('/peminjaman', [PeminjamanController::class, 'index']);
    route::get('/peminjaman/{nomor_pinjam}', [PeminjamanController::class, 'show']);
    Route::put('/peminjaman/{nomor_pinjam}/update', [PeminjamanController::class, 'updateStatus']);
    

});

// route kategori
Route::get('/kategori', [KategoriController::class, 'index']);
Route::post('/kategori', [KategoriController::class, 'store']);
Route::get('/kategori/{id_kategori}', [KategoriController::class, 'show']);
Route::put('/kategori/{id_kategori}', [KategoriController::class, 'update']);
Route::delete('/kategori/{id_kategori}', [KategoriController::class, 'destroy']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

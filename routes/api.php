<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\MemberController;
use App\Http\Middleware\MemberMiddleware;
use App\Http\Middleware\PetugasMiddleware;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\CopyBukuController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\ReportController;

Route::post('/register/member', [MemberController::class, 'store']);
Route::post('/register/petugas', [PetugasController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// route public /api...
Route::get('/buku', [BukuController::class, 'index']);
Route::get('/buku/search', [BukuController::class, 'search']);
Route::get('/books/random', [BukuController::class, 'randomBooks']);
Route::get('/buku/{id_buku}', [BukuController::class, 'show']);
Route::post('/buku/byKategori', [BukuController::class, 'showBukuByKategori']);

Route::get('/copyBuku', [CopyBukuController::class, 'index']);
Route::get('/copyBuku/{id_buku_copy}', [CopyBukuController::class, 'show']);

Route::get('/peminjaman/showLatest', [PeminjamanController::class, 'showLatest']);
Route::get('/peminjaman/getPendingAndBorrowed', [AuthController::class, 'getPendingAndBorrowed']);

// route kategori
Route::get('/kategori', [KategoriController::class, 'index']);
Route::post('/kategori', [KategoriController::class, 'store']);
Route::get('/kategori/{id_kategori}', [KategoriController::class, 'show']);
Route::put('/kategori/{id_kategori}', [KategoriController::class, 'update']);
Route::delete('/kategori/{id_kategori}', [KategoriController::class, 'destroy']);

// route member & peminjaman untuk MEMBER yang login /api/member/...
Route::middleware(['auth:sanctum', MemberMiddleware::class])->prefix('member')->group(function () {

    Route::get('/profile', [MemberController::class, 'show']);
    Route::post('/profile/update', [MemberController::class, 'update']);
    Route::delete('/profile/delete', [MemberController::class, 'destroy']);

    Route::post('/changePassword', [MemberController::class, 'changePassword']);

    Route::post('/peminjaman', [PeminjamanController::class, 'store']);
    Route::post('/detailPeminjaman', [AuthController::class, 'addToDraft']);
    Route::post('/detailPeminjaman/submit', [AuthController::class, 'submitDraft']);

    Route::get('/cart', [AuthController::class, 'getDraft']);

    Route::put('/peminjaman/kembali', [AuthController::class, 'returnBook']);
    Route::put('/peminjaman/kembaliSemua', [AuthController::class, 'returnAllBooks']);

    //route pembayaran
    // Route::post('/denda/bayar', [PembayaranController::class, 'bayarDenda']); // bayar denda

    route::get('/peminjaman/riwayat', [AuthController::class, 'getBorrowedHistory']); //

    Route::get('/denda', [PembayaranController::class, 'showUnpaidFineDetails']);
    Route::get('/denda/paid', [PembayaranController::class, 'showPaidFineDetails']);
    Route::post('/denda/bayar', [PembayaranController::class, 'bayarDenda']);
    Route::get('/denda/riwayat', [PembayaranController::class, 'riwayatPembayaran']);
});

// route untuk PETUGAS /api/petugas/...
Route::middleware(['auth:sanctum', PetugasMiddleware::class])->prefix('petugas')->group(function () {

    Route::get('/profile', [PetugasController::class, 'show']);
    Route::post('/profile/update', [PetugasController::class, 'update']);
    Route::delete('/profile/delete', [PetugasController::class, 'destroy']);

    Route::post('/changePassword', [PetugasController::class, 'changePassword']);

    Route::post('/buku', [BukuController::class, 'store']);
    Route::post('/buku/{id_buku}', [BukuController::class, 'update']);
    Route::delete('/buku/{id_buku}', [BukuController::class, 'destroy']);

    Route::post('/copyBuku', [CopyBukuController::class, 'store']);
    Route::put('/copyBuku/{id_buku_copy}', [CopyBukuController::class, 'update']);
    Route::delete('/copyBuku/{id_buku_copy}', [CopyBukuController::class, 'destroy']);
    Route::delete('/copyBuku/delete-latest', [CopyBukuController::class, 'destroyLatest']);
    Route::get('/copyBuku/count/{id_buku}', [CopyBukuController::class, 'getCopyCount']);

    Route::get('/peminjaman', [PeminjamanController::class, 'index']);
    Route::get('/peminjaman/{nomor_pinjam}', [PeminjamanController::class, 'show']);
    Route::put('/peminjaman/{nomor_pinjam}/update', [PeminjamanController::class, 'updateStatus']);
    Route::put('/peminjaman/approve/all', [PeminjamanController::class, 'updateStatusBulk']);
    Route::get('/peminjaman-per-hari', [PeminjamanController::class, 'laporanPeminjamanPerHari']);
    Route::get('/pendingRequests', [AuthController::class, 'pendingRequests']);

    // MEMBER LIST API (dipakai halaman MemberList)
    Route::get('/members', [MemberController::class, 'index']);
    Route::put('/members/{id_member}', [MemberController::class, 'updateById']);
    Route::delete('/members/{id_member}', [MemberController::class, 'destroyById']);

    // REPORTS untuk petugas
    Route::get('/reports/summary', [ReportController::class, 'summary']);
    Route::get('/reports/borrowing-by-category', [ReportController::class, 'borrowingByCategory']);
    Route::get('/reports/loans', [ReportController::class, 'loans']);
    Route::get('/reports/fines', [ReportController::class, 'fines']);

    Route::put('/members/{id_member}/toggle-status', [MemberController::class, 'toggleStatus']);
});

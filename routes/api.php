<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\MemberController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\AuthController;

Route::post('/member/register', [MemberController::class, 'store']);
   Route::post('/petugas/register', [PetugasController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/member/profile', [MemberController::class, 'show']);
    Route::post('/member/profile/update', [MemberController::class, 'update']);
    Route::delete('/member/profile/delete', [MemberController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
 
    Route::get('/petugas/profile', [PetugasController::class, 'show']);
    Route::post('/petugas/profile/update', [PetugasController::class, 'update']);
    Route::delete('/petugas/profile/delete', [PetugasController::class, 'destroy']);
});

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

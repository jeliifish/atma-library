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

    
}

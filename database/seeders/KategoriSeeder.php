<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriList = [
            [ 'id_kategori' => 'KTG0001', 'nama_kategori' => 'Fiction' ],
            [ 'id_kategori' => 'KTG0002', 'nama_kategori' => 'Science' ],
            [ 'id_kategori' => 'KTG0003', 'nama_kategori' => 'History' ],
            [ 'id_kategori' => 'KTG0004', 'nama_kategori' => 'Fantasy' ],
            [ 'id_kategori' => 'KTG0005', 'nama_kategori' => 'Technology' ],
            [ 'id_kategori' => 'KTG0006', 'nama_kategori' => 'Education' ],
        ];

        DB::table('kategori')->insert($kategoriList);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Buku;
use App\Models\Kategori;
use App\Models\BukuKategori;

class BukuKategoriSeeder extends Seeder
{
    public function run(): void
    {
        $allBooks = Buku::all();
        $allCategories = Kategori::all();

        if ($allBooks->isEmpty() || $allCategories->isEmpty()) {
            return;
        }

        foreach ($allBooks as $book) {
            // pilih kategori random (2 s/d 3)
            $kategoriIds = $allCategories
                ->random(rand(2, 3))
                ->pluck('id_kategori')
                ->toArray();

            foreach ($kategoriIds as $idKategori) {
                BukuKategori::create([
                    'id_buku'     => $book->id_buku,
                    'id_kategori' => $idKategori
                ]);
            }
        }
    }
}

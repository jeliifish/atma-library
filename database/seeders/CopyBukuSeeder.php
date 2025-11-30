<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Buku;

class CopyBukuSeeder extends Seeder
{
    public function run(): void
    {
        // berapa copy per buku
        $copiesPerBook = 3;

        $copies = [];

        // ambil semua buku yang sudah diseed
        $books = Buku::all();

        foreach ($books as $buku) {
            for ($i = 1; $i <= $copiesPerBook; $i++) {
                $copies[] = [
                    'id_buku_copy' => sprintf('%s-%03d', $buku->id_buku, $i), // BKU0001-001
                    'id_buku'      => $buku->id_buku,
                    'rak'          => 'R-' . rand(1, 20),
                    'status'       => 'available',
                ];
            }
        }

        // insert sekali banyak
        DB::table('copy_buku')->insert($copies);
    }
}

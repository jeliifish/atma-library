<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Buku;

class CopyBukuSeeder extends Seeder
{
    public function run(): void
    {
        // Konfigurasi jumlah copy & rak per buku
        // key = id_buku dari BukuSeeder (BKU0001 s/d BKU0020)
        $copyConfig = [
            // Harry Potter series (lebih banyak copy)
            'BKU0001' => ['count' => 5, 'rak_base' => 'FAN-01'],
            'BKU0002' => ['count' => 5, 'rak_base' => 'FAN-02'],
            'BKU0003' => ['count' => 5, 'rak_base' => 'FAN-03'],

            // Classics
            'BKU0004' => ['count' => 3, 'rak_base' => 'CLS-01'], // To Kill a Mockingbird
            'BKU0005' => ['count' => 4, 'rak_base' => 'DYS-01'], // 1984 (dystopian)
            'BKU0006' => ['count' => 3, 'rak_base' => 'CLS-02'], // Great Gatsby
            'BKU0007' => ['count' => 3, 'rak_base' => 'CLS-03'], // Pride & Prejudice

            // Fantasy & adventure
            'BKU0008' => ['count' => 4, 'rak_base' => 'FAN-04'], // The Hobbit
            'BKU0011' => ['count' => 4, 'rak_base' => 'FAN-05'], // LOTR 1
            'BKU0012' => ['count' => 4, 'rak_base' => 'FAN-06'], // LOTR 2
            'BKU0013' => ['count' => 4, 'rak_base' => 'FAN-07'], // LOTR 3
            'BKU0014' => ['count' => 4, 'rak_base' => 'FAN-08'], // Narnia

            // Literary / contemporary
            'BKU0009' => ['count' => 3, 'rak_base' => 'LIT-01'], // Catcher in the Rye
            'BKU0018' => ['count' => 3, 'rak_base' => 'CON-01'], // Fault in Our Stars

            // Philosophy / personal dev
            'BKU0010' => ['count' => 3, 'rak_base' => 'PHI-01'], // The Alchemist

            // Dystopian YA (Hunger Games, Maze Runner, Divergent)
            'BKU0015' => ['count' => 4, 'rak_base' => 'DYS-02'], // Hunger Games
            'BKU0016' => ['count' => 4, 'rak_base' => 'DYS-03'], // Catching Fire
            'BKU0017' => ['count' => 4, 'rak_base' => 'DYS-04'], // Mockingjay
            'BKU0019' => ['count' => 3, 'rak_base' => 'DYS-05'], // Maze Runner
            'BKU0020' => ['count' => 3, 'rak_base' => 'DYS-06'], // Divergent
        ];

        $copies = [];

        // Ambil semua buku yang sudah ada di tabel buku
        $books = Buku::all();

        foreach ($books as $buku) {
            // Ambil konfigurasi untuk buku ini, kalau tidak ada pakai default
            $config = $copyConfig[$buku->id_buku] ?? [
                'count'    => 3,
                'rak_base' => 'R-GEN',
            ];

            $count = $config['count'];
            $rakBase = $config['rak_base'];

            for ($i = 1; $i <= $count; $i++) {
                $copies[] = [
                    'id_buku_copy' => sprintf('%s-%03d', $buku->id_buku, $i), // contoh: BKU0001-001
                    'id_buku'      => $buku->id_buku,
                    'rak'          => sprintf('%s-%02d', $rakBase, $i),      // contoh: FAN-01-01
                    'status'       => 'available',
                ];
            }
        }

        if (!empty($copies)) {
            DB::table('copy_buku')->insert($copies);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BukuKategori;

class BukuKategoriSeeder extends Seeder
{
    public function run(): void
    {
        // Mapping buku → kategori
        $mapping = [
            'BKU0001' => ['KTG0001', 'KTG0006'],
            'BKU0002' => ['KTG0001', 'KTG0006'],
            'BKU0003' => ['KTG0001', 'KTG0006'],
            'BKU0004' => ['KTG0010', 'KTG0018'],
            'BKU0005' => ['KTG0003', 'KTG0021'],
            'BKU0006' => ['KTG0010', 'KTG0011'],
            'BKU0007' => ['KTG0010', 'KTG0011'],
            'BKU0008' => ['KTG0001', 'KTG0006'],
            'BKU0009' => ['KTG0011', 'KTG0008'],
            'BKU0010' => ['KTG0019', 'KTG0015'],
            'BKU0011' => ['KTG0001', 'KTG0006'],
            'BKU0012' => ['KTG0001', 'KTG0006'],
            'BKU0013' => ['KTG0001', 'KTG0006'],
            'BKU0014' => ['KTG0001', 'KTG0006'],
            'BKU0015' => ['KTG0003', 'KTG0007'],
            'BKU0016' => ['KTG0003', 'KTG0007'],
            'BKU0017' => ['KTG0003', 'KTG0007'],
            'BKU0018' => ['KTG0008', 'KTG0015'],
            'BKU0019' => ['KTG0003', 'KTG0007'],
            'BKU0020' => ['KTG0003', 'KTG0007'],
        ];

        foreach ($mapping as $idBuku => $kategoriList) {
            foreach ($kategoriList as $idKategori) {
                BukuKategori::create([
                    'id_buku'     => $idBuku,
                    'id_kategori' => $idKategori,
                ]);
            }
        }
    }
}

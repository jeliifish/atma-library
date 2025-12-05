<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriSeeder extends Seeder
{
    public function run()
    {
        // List kategori tanpa ID
        $kategoriList = [
            'Fantasy',
            'Science Fiction',
            'Dystopian',
            'Horror',
            'Mystery / Thriller',
            'Adventure',
            'Action',
            'Contemporary Fiction',
            'Historical Fiction',
            'Classic Literature',
            'Literary Fiction',
            'Mythology',
            'Memoir / Biography',
            'Self-Help',
            'Personal Development',
            'Psychology',
            'Economics',
            'History',
            'Philosophy',
            'Business / Entrepreneurship',
            'Political Commentary',
        ];

        $insertData = [];

        foreach ($kategoriList as $index => $nama) {
            $num = str_pad($index + 1, 4, '0', STR_PAD_LEFT);

            $insertData[] = [
                'id_kategori' => "KTG{$num}",
                'nama_kategori' => $nama,
            ];
        }

        DB::table('kategori')->insert($insertData);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BukuSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            [
                'id_buku'       => 'BKU0001',
                'judul'         => 'The Great Gatsby',
                'penulis'       => 'F. Scott Fitzgerald',
                'penerbit'      => 'Scribner',
                'deskripsi'     => 'A classic novel set in the Roaring Twenties.',
                'ISBN'          => '9780743273565',
                'tahun_terbit'  => 1925,
                'url_foto_cover'=> 'https://images-na.ssl-images-amazon.com/images/I/81af+MCATTL.jpg',
            ],
            [
                'id_buku'       => 'BKU0002',
                'judul'         => 'The Hobbit',
                'penulis'       => 'J. R. R. Tolkien',
                'penerbit'      => 'HarperCollins',
                'deskripsi'     => 'Bilbo Baggins joins a quest to reclaim the Lonely Mountain.',
                'ISBN'          => '9780261103344',
                'tahun_terbit'  => 1937,
                'url_foto_cover'=> 'https://m.media-amazon.com/images/I/71aFt4+OTOL.jpg',
            ],
            [
                'id_buku'       => 'BKU0003',
                'judul'         => 'To Kill a Mockingbird',
                'penulis'       => 'Harper Lee',
                'penerbit'      => 'Harper Perennial',
                'deskripsi'     => 'A novel about racial injustice in the American South.',
                'ISBN'          => '9780061120084',
                'tahun_terbit'  => 1960,
                'url_foto_cover'=> 'https://m.media-amazon.com/images/I/81OtwkiU9IL.jpg',
            ],
            [
                'id_buku'       => 'BKU0004',
                'judul'         => '1984',
                'penulis'       => 'George Orwell',
                'penerbit'      => 'Penguin Books',
                'deskripsi'     => 'A dystopian novel about totalitarianism.',
                'ISBN'          => '9780451524935',
                'tahun_terbit'  => 1949,
                'url_foto_cover'=> 'https://m.media-amazon.com/images/I/71kxa1-0mfL.jpg',
            ],
            [
                'id_buku'       => 'BKU0005',
                'judul'         => "Harry Potter and the Sorcerer's Stone",
                'penulis'       => 'J. K. Rowling',
                'penerbit'      => 'Bloomsbury',
                'deskripsi'     => 'The first book in the Harry Potter series.',
                'ISBN'          => '9780747532699',
                'tahun_terbit'  => 1997,
                'url_foto_cover'=> 'https://m.media-amazon.com/images/I/81YOuOGFCJL.jpg',
            ],
        ];

        DB::table('buku')->insert($books);
    }
}

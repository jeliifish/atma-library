<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BukuSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        // 20 Data Buku
        $bukuList = [
            [
                'judul' => "Harry Potter and the Philosopher's Stone",
                'penulis' => 'J.K. Rowling',
                'penerbit' => 'Bloomsbury',
                'ISBN' => '9780747532699',
                'tahun_terbit' => 1997,
                'url_foto_cover' => 'https://res.cloudinary.com/bloomsbury-atlas/image/upload/w_568,c_scale,dpr_1.5/jackets/9781408855652.jpg',
                'deskripsi' => 'The first book in the Harry Potter series, introducing the wizarding world and Hogwarts.'
            ],
            [
                'judul' => 'Harry Potter and the Chamber of Secrets',
                'penulis' => 'J.K. Rowling',
                'penerbit' => 'Bloomsbury',
                'ISBN' => '9780747538493',
                'tahun_terbit' => 1998,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/15095437-M.jpg',
                'deskripsi' => 'Harry returns to Hogwarts and uncovers the mystery of the Chamber of Secrets.'
            ],
            [
                'judul' => 'Harry Potter and the Prisoner of Azkaban',
                'penulis' => 'J.K. Rowling',
                'penerbit' => 'Bloomsbury',
                'ISBN' => '9780747542155',
                'tahun_terbit' => 1999,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/14852024-M.jpg',
                'deskripsi' => 'Harry faces Sirius Black, a dangerous escaped prisoner linked to his past.'
            ],
            [
                'judul' => 'To Kill a Mockingbird',
                'penulis' => 'Harper Lee',
                'penerbit' => 'HarperCollins',
                'ISBN' => '9780061120084',
                'tahun_terbit' => 1960,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/14817209-M.jpg',
                'deskripsi' => 'A classic novel about racial injustice and moral growth in the American South.'
            ],
            [
                'judul' => '1984',
                'penulis' => 'George Orwell',
                'penerbit' => 'Penguin Books',
                'ISBN' => '9780451524935',
                'tahun_terbit' => 1949,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/12693610-M.jpg',
                'deskripsi' => 'A dystopian novel exploring surveillance, totalitarianism, and loss of freedom.'
            ],
            [
                'judul' => 'The Great Gatsby',
                'penulis' => 'F. Scott Fitzgerald',
                'penerbit' => 'Scribner',
                'ISBN' => '9780743273565',
                'tahun_terbit' => 1925,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/12364437-M.jpg',
                'deskripsi' => 'A tragic story of wealth, love, and the American Dream set in the Jazz Age.'
            ],
            [
                'judul' => 'Pride and Prejudice',
                'penulis' => 'Jane Austen',
                'penerbit' => 'Oxford Press',
                'ISBN' => '9780199535569',
                // PENTING: YEAR MySQL harus 1901–2155 → pakai 1901
                'tahun_terbit' => 1901,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/8090214-M.jpg',
                'deskripsi' => 'A timeless romantic novel exploring manners, marriage, and social expectations.'
            ],
            [
                'judul' => 'The Hobbit',
                'penulis' => 'J.R.R. Tolkien',
                'penerbit' => 'HarperCollins',
                'ISBN' => '9780261103344',
                'tahun_terbit' => 1937,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/14627222-M.jpg',
                'deskripsi' => 'Bilbo Baggins embarks on an adventurous quest filled with dwarves, dragons, and magic.'
            ],
            [
                'judul' => 'The Catcher in the Rye',
                'penulis' => 'J.D. Salinger',
                'penerbit' => 'Little, Brown',
                'ISBN' => '9780316769488',
                'tahun_terbit' => 1951,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/14894937-M.jpg',
                'deskripsi' => 'A coming-of-age story following Holden Caulfield’s struggles with identity and society.'
            ],
            [
                'judul' => 'The Alchemist',
                'penulis' => 'Paulo Coelho',
                'penerbit' => 'HarperOne',
                'ISBN' => '9780062315007',
                'tahun_terbit' => 1988,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/15121528-M.jpg',
                'deskripsi' => 'A philosophical adventure about following one’s dreams and discovering personal legend.'
            ],
            [
                'judul' => 'The Lord of the Rings: The Fellowship of the Ring',
                'penulis' => 'J.R.R. Tolkien',
                'penerbit' => 'Allen & Unwin',
                'ISBN' => '9780261103573',
                'tahun_terbit' => 1954,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/8231851-M.jpg',
                'deskripsi' => 'The beginning of the epic quest to destroy the One Ring.'
            ],
            [
                'judul' => 'The Lord of the Rings: The Two Towers',
                'penulis' => 'J.R.R. Tolkien',
                'penerbit' => 'Allen & Unwin',
                'ISBN' => '9780261102361',
                'tahun_terbit' => 1954,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/8231852-M.jpg',
                'deskripsi' => 'The Fellowship is broken, and new battles unfold across Middle-earth.'
            ],
            [
                'judul' => 'The Lord of the Rings: The Return of the King',
                'penulis' => 'J.R.R. Tolkien',
                'penerbit' => 'Allen & Unwin',
                'ISBN' => '9780261102378',
                'tahun_terbit' => 1955,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/8231853-M.jpg',
                'deskripsi' => 'The final confrontation against Sauron and the end of the Third Age.'
            ],
            [
                'judul' => 'The Chronicles of Narnia: The Lion, the Witch and the Wardrobe',
                'penulis' => 'C.S. Lewis',
                'penerbit' => 'Geoffrey Bles',
                'ISBN' => '9780064471046',
                'tahun_terbit' => 1950,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/8611872-M.jpg',
                'deskripsi' => 'Four siblings discover the magical land of Narnia through a wardrobe.'
            ],
            [
                'judul' => 'The Hunger Games',
                'penulis' => 'Suzanne Collins',
                'penerbit' => 'Scholastic Press',
                'ISBN' => '9780439023481',
                'tahun_terbit' => 2008,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/7884861-M.jpg',
                'deskripsi' => 'Katniss Everdeen fights for survival in a deadly televised competition.'
            ],
            [
                'judul' => 'Catching Fire',
                'penulis' => 'Suzanne Collins',
                'penerbit' => 'Scholastic Press',
                'ISBN' => '9780439023498',
                'tahun_terbit' => 2009,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/7884862-M.jpg',
                'deskripsi' => 'Katniss becomes the symbol of a growing rebellion.'
            ],
            [
                'judul' => 'Mockingjay',
                'penulis' => 'Suzanne Collins',
                'penerbit' => 'Scholastic Press',
                'ISBN' => '9780439023511',
                'tahun_terbit' => 2010,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/7884863-M.jpg',
                'deskripsi' => 'The final battle for freedom as Katniss leads the revolution.'
            ],
            [
                'judul' => 'The Fault in Our Stars',
                'penulis' => 'John Green',
                'penerbit' => 'Dutton Books',
                'ISBN' => '9780525478812',
                'tahun_terbit' => 2012,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/8231990-M.jpg',
                'deskripsi' => 'A heartbreaking love story between two teenagers fighting cancer.'
            ],
            [
                'judul' => 'The Maze Runner',
                'penulis' => 'James Dashner',
                'penerbit' => 'Delacorte Press',
                'ISBN' => '9780385737951',
                'tahun_terbit' => 2009,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/7269251-M.jpg',
                'deskripsi' => 'A group of boys trapped in a deadly maze struggle to find a way out.'
            ],
            [
                'judul' => 'Divergent',
                'penulis' => 'Veronica Roth',
                'penerbit' => 'HarperCollins',
                'ISBN' => '9780007420421',
                'tahun_terbit' => 2011,
                'url_foto_cover' => 'https://covers.openlibrary.org/b/id/7302061-M.jpg',
                'deskripsi' => 'In a divided society, Tris discovers she is Divergent and doesn’t fit into any faction.'
            ],
        ];

        // Generate and insert with id_buku = BKU0001...BKU0020
        $insertData = [];
        foreach ($bukuList as $index => $buku) {
            $num = str_pad($index + 1, 4, '0', STR_PAD_LEFT);
            $insertData[] = array_merge($buku, [
                'id_buku'    => "BKU{$num}",
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('buku')->insert($insertData);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        // --- MEMBER DEFAULT (akun contoh) ---
        Member::create([
            'nama'           => 'Zelika Aninda',
            'username'       => 'zelika',
            'password'       => 'password123',   // otomatis di-hash karena casts()
            'alamat'         => 'Yogyakarta',
            'email'          => 'zelika@example.com',
            'no_telp'        => '081234567890',
            'tgl_daftar'     => now(),
            'url_foto_profil'=> null, // otomatis pakai default-profile.jpeg
            'status'         => 'aktif',
        ]);

        // --- MEMBER TAMBAHAN (dummy data) ---
        $dummy = [
            [
                'nama' => 'Rina Kartika',
                'username' => 'rina123',
                'email' => 'rina@example.com',
                'alamat' => 'Depok',
                'no_telp' => '081234500001',
            ],
            [
                'nama' => 'Andi Wijaya',
                'username' => 'andi88',
                'email' => 'andi@example.com',
                'alamat' => 'Jakarta',
                'no_telp' => '081234500002',
            ],
            [
                'nama' => 'Siti Rahma',
                'username' => 'rahma22',
                'email' => 'rahma@example.com',
                'alamat' => 'Bandung',
                'no_telp' => '081234500003',
            ],
        ];

        foreach ($dummy as $m) {
            Member::create([
                'nama'           => $m['nama'],
                'username'       => $m['username'],
                'password'       => 'user12345', // hashed otomatis
                'alamat'         => $m['alamat'],
                'email'          => $m['email'],
                'no_telp'        => $m['no_telp'],
                'tgl_daftar'     => now(),
                'url_foto_profil'=> null,
                'status'         => 'aktif',
            ]);
        }
    }
}

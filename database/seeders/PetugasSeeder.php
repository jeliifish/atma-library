<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Petugas;
use Illuminate\Support\Facades\Hash;  

class PetugasSeeder extends Seeder
{
    public function run(): void
    {
        // --- PETUGAS UTAMA (contoh akun admin/petugas) ---
        Petugas::create([
            'nama'           => 'Admin Perpustakaan',
            'username'       => 'adminperpus',
            'password'       =>  Hash::make('admin123'),  // otomatis di-hash
            'alamat'         => 'Yogyakarta',
            'email'          => 'admin@library.com',
            'no_telp'        => '081234567001',
            'tgl_daftar'     => now(),
            'url_foto_profil'=> null, // akan pakai default-profile.jpeg
            'status'         => 'aktif',
        ]);

        // --- PETUGAS TAMBAHAN (dummy) ---
        $dummy = [
            [
                'nama' => 'Budi Santoso',
                'username' => 'budi',
                'email' => 'budi@library.com',
                'alamat' => 'Jakarta',
                'no_telp' => '081234500101'
            ],
            [
                'nama' => 'Citra Lestari',
                'username' => 'citra',
                'email' => 'citra@library.com',
                'alamat' => 'Bandung',
                'no_telp' => '081234500102'
            ]
        ];

        foreach ($dummy as $p) {
            Petugas::create([
                'nama'           => $p['nama'],
                'username'       => $p['username'],
                'password'       => Hash::make('petugas123'),
                'alamat'         => $p['alamat'],
                'email'          => $p['email'],
                'no_telp'        => $p['no_telp'],
                'tgl_daftar'     => now(),
                'url_foto_profil'=> null,
                'status'         => 'aktif',
            ]);
        }
    }
}

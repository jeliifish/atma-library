<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MemberSeeder::class,
            PetugasSeeder::class,
            
            KategoriSeeder::class,
            BukuSeeder::class,
            BukuKategoriSeeder::class,

            CopyBukuSeeder::class,

            PeminjamanSeeder::class,
            DetailPeminjamanSeeder::class,

            DendaSeeder::class,
            PembayaranDendaSeeder::class,
            DetailPembayaranDendaSeeder::class,
        ]);
    }
}

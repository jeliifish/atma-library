<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE copy_buku 
            MODIFY COLUMN status ENUM('tersedia','dipinjam','menunggu') NOT NULL DEFAULT 'tersedia'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE copy_buku 
            MODIFY COLUMN status ENUM('tersedia','dipinjam') NOT NULL DEFAULT 'tersedia'");
    }

    
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('detail_peminjaman', function (Blueprint $table) {
            $table->unsignedBigInteger('nomor_pinjam');
            $table->string('id_buku_copy');
            $table->date('tgl_kembali');
            $table->enum('status', ['menunggu', 'disetujui','ditolak']);
            $table->timestamps();

            $table->foreign('nomor_pinjam')
                    ->references('nomor_pinjam')
                    ->on('peminjaman')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            
            $table->foreign('id_buku_copy')
                    ->references('id_buku_copy')
                    ->on('copy_buku')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_peminjaman');
    }
};

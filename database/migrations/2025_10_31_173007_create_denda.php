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
        Schema::create('denda', function (Blueprint $table) {
            $table->id('id_denda');
            $table->unsignedBigInteger('nomor_pinjam');
            $table->string('id_buku_copy');
            $table->integer('hari_telat');
            $table->decimal('harga_per_hari', 12, 0);
            $table->decimal('total_denda',  12, 0);
            $table->enum('status', ['belum', 'lunas'])->default('belum');
            $table->timestamps();

            $table->foreign('nomor_pinjam')
                    ->references('nomor_pinjam')
                    ->on('peminjaman')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');

            $table->foreign('id_buku_copy')
                    ->references('id_buku_copy')
                    ->on('copy_buku')
                    ->cascadeOnDelete();

            $table->unique(['nomor_pinjam', 'id_buku_copy']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('denda');
    }
};

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
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id('nomor_pinjam');
            $table->unsignedBigInteger('id_member');
            $table->unsignedBigInteger('id_petugas');
            $table->date('tgl_pinjam');
            $table->date('tgl_kembali');
            $table->timestamps();

            $table->foreign('id_member')
                    ->references('id_member')
                    ->on('member')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            
            $table->foreign('id_petugas')
                    ->references('id_petugas')
                    ->on('petugas')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};

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
            $table->integer('hari_telat');
            $table->float('harga_per_hari');
            $table->float('total_denda');
            $table->timestamps();

            $table->foreign('nomor_pinjam')
                    ->references('nomor_pinjam')
                    ->on('peminjaman')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
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

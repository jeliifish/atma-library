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
        Schema::create('copy_buku', function (Blueprint $table) {
            $table->string('id_buku_copy',20)->primary();
            $table->string('id_buku');
            $table->string('rak');
            $table->enum('status', ['dipinjam', 'tersedia']);
            $table->timestamps();

            $table->foreign('id_buku')
                    ->references('id_buku')
                    ->on('buku')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('copy_buku');
    }
};

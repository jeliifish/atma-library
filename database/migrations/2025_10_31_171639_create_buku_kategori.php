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
        Schema::create('buku_kategori', function (Blueprint $table) {
            $table->string('id_buku');
            $table->string('id_kategori');
            $table->timestamps();

            // Cegah duplikasi pasangan
            $table->primary(['id_buku', 'id_kategori']);

            $table->foreign('id_buku')
                    ->references('id_buku')
                    ->on('buku')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');

            $table->foreign('id_kategori')
                    ->references('id_kategori')
                    ->on('kategori')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buku_kategori');
    }
};

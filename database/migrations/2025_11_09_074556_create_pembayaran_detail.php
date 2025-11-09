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
        Schema::create('pembayaran_detail', function (Blueprint $table) {
            $table->id('id_pembayaran_detail');
            $table->unsignedBigInteger('id_pembayaran');
            $table->unsignedBigInteger('id_denda');
            $table->integer('nominal');
            $table->timestamps();

            $table->foreign('id_pembayaran')->references('id_pembayaran')->on('pembayaran_denda')->cascadeOnDelete();
            $table->foreign('id_denda')->references('id_denda')->on('denda')->cascadeOnDelete();

            $table->unique(['id_pembayaran', 'id_denda']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran_detail');
    }
};

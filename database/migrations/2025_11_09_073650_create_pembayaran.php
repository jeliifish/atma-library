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
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->unsignedBigInteger('id_member');
            $table->unsignedBigInteger('id_petugas')->nullable();
            $table->dateTime('tgl_bayar');
            $table->integer('total');
            $table->enum('metode', ['cash', 'transfer','qris', 'ewallet'])->default('cash');
            $table->timestamps();

            $table->foreign('id_member')->references('id_member')->on('member')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};

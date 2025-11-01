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
        Schema::create('member', function (Blueprint $table) {
            $table->id('id_member');
            $table->string('nama');
            $table->string('username');
            $table->string('password');
            $table->string('alamat');
            $table->string('email');
            $table->integer('no_telp');
            $table->date('tgl_daftar');
            $table->string('url_foto_profil')->nullable();
            $table->enum('status',['aktif','nonaktif']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member');
    }
};

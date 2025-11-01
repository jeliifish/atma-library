<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Petugas extends Model
{
    protected $table = 'petugas';
    protected $primaryKey = 'id_petugas';
    public $timestamps = false;

     protected $fillable = [
        'nama',
        'username',
        'password',
        'alamat',
        'email',
        'no_telp',
        'tgl_daftar',
        'url_foto_profil',
        'status'
    ];

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'id_petugas', 'id_petugas');
    }
}

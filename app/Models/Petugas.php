<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; 
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Petugas extends Authenticatable
{
    use HasApiTokens, Notifiable;
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

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'id_petugas', 'id_petugas');
    }
}

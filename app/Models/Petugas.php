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

    public function getUrlFotoProfilAttribute($value)
    {
        // kalau benar-benar kosong → pakai default
        if (!$value) {
            return url('images/default-profile.jpeg');
        }

        // kalau path dari storage (hasil upload)
        if (str_starts_with($value, 'profile/')) {
            return url('storage/' . $value);
        }

        // kalau sudah disimpan sebagai path relatif di public (images/default-profile.jpeg)
        return url($value);
    }


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

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
        if (!$value) {
            return url('images/default-profile.jpeg');
        }

        if (str_starts_with($value, 'profile/')) {
            return url('storage/' . $value);
        }

        return url($value);
    }

    // casts password DIHAPUS supaya tidak di-hash otomatis lagi
    // protected function casts(): array
    // {
    //     return [
    //         'password' => 'hashed',
    //     ];
    // }

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'id_petugas', 'id_petugas');
    }
}

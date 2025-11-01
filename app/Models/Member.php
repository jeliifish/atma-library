<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'member';
    protected $primaryKey = 'id_member';
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
        return $this->hasMany(Peminjaman::class, 'id_member', 'id_member');
    }

 
}

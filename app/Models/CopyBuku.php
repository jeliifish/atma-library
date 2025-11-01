<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CopyBuku extends Model
{
    protected $table = 'copy_buku';
    protected $primaryKey = 'id_buku_copy';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_buku',
        'rak',
        'status'
    ];

    public function detailPeminjaman()
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_buku_copy', 'id_buku_copy');
    }

    public function peminjaman()
    {
        return $this->belongsToMany(Peminjaman::class, 'detail_peminjaman', 'id_buku_copy', 'nomor_pinjam')
            ->withPivot(['id_member', 'tgl_kembali', 'status']);
    }

    public function buku()
    {
        return $this->belongsTo(Buku::class, 'id_buku', 'id_buku');
    }
}

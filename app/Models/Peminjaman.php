<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Peminjaman extends Model
{
    protected $table = 'peminjaman';
    protected $primaryKey = 'nomor_pinjam';
    public $timestamps = false;

    protected $fillable = [
        'id_member',
        'id_petugas',
        'tgl_pinjam',
        'tgl_kembali',
        'status'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'id_member', 'id_member');
    }

     public function denda()
    {
        return $this->hasOne(Denda::class, 'nomor_pinjam', 'nomor_pinjam');
    }

    public function petugas()
    {
        return $this->belongsTo(Petugas::class, 'id_petugas', 'id_petugas');
    }

    public function detailPeminjaman()
    {
        return $this->hasMany(DetailPeminjaman::class, 'nomor_pinjam', 'nomor_pinjam');
    }

    public function copyBuku()
    {
        return $this->belongsToMany(CopyBuku::class, 'detail_peminjaman', 'nomor_pinjam', 'id_buku_copy')
            ->withPivot(['id_member', 'tgl_kembali', 'status', 'created_at', 'updated_at']);
    }
}

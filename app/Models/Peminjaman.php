<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Peminjaman extends Model
{
    protected $table = 'peminjaman';
    protected $primaryKey = 'nomor_pinjam';
    public $incrementing = true; // karena bukan auto-increment
    protected $keyType = 'int'; // karena ID-nya berupa teks (PMJ0001)
    public $timestamps = false;

    protected $fillable = [
        'nomor_pinjam', // tambahkan ini supaya bisa mass-assignment
        'id_member',
        'id_petugas',
        'tgl_pinjam',
        'tgl_kembali'
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
            ->withPivot(['tgl_kembali', 'status']);
    }
}

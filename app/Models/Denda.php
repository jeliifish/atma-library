<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Denda extends Model
{
    protected $table = 'denda';
    protected $primaryKey = 'id_denda';
    public $timestamps = false;

    protected $fillable = [
        'nomor_pinjam',
        'id_buku_copy',
        'hari_telat',
        'harga_per_hari',
        'total_denda',
        'status'
    ];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'nomor_pinjam', 'nomor_pinjam');
    }

    public function copyBuku()
    {
        return $this->belongsTo(CopyBuku::class, 'id_buku_copy', 'id_buku_copy');
    }

    public function pembayaranDenda()
    {
        return $this->belongsToMany(
            PembayaranDenda::class,
            'detail_pembayaran_denda',
            'id_denda',
            'id_pembayaran'
        )->withPivot('nominal')
         ->withTimestamps();
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPeminjaman extends Model
{
    protected $table = 'detail_peminjaman';
    protected $primaryKey = null;
    public  $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'nomor_pinjam',
        'id_buku_copy',
        'tgl_kembali',
        'status',
    ];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'nomor_pinjam', 'nomor_pinjam');
    }

    public function copyBuku()
    {
        return $this->belongsTo(CopyBuku::class, 'id_buku_copy', 'id_buku_copy');
    }
}

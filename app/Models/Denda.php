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
        'hari_telat',
        'harga_per_hari',
        'total_denda'
    ];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'nomor_pinjam', 'nomor_pinjam');
    }

}

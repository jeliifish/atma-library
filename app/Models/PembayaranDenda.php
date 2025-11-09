<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranDenda extends Model
{
    protected $table = 'pembayaran_denda';
    protected $primaryKey = 'id_pembayaran';
    protected $fillable = [
        'id_member',
        'tgl_bayar',
        'total_bayar',
        'status', // 'belum' atau 'lunas'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'id_member', 'id_member');
    }

    public function denda()
    {
        return $this->belongsToMany(
            Denda::class,
            'detail_pembayaran_denda',
            'id_pembayaran',
            'id_denda'
        )->withPivot('nominal_bayar')
         ->withTimestamps();
    }
}

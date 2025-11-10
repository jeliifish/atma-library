<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranDenda extends Model
{
    protected $table = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';
    protected $fillable = [
        'id_member',
        'tgl_bayar',
        'total',
        'metode',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'id_member', 'id_member');
    }

    public function denda()
    {
        return $this->belongsToMany(
            Denda::class,
            'detail_pembayaran',
            'id_pembayaran',
            'id_denda'
        )->withPivot('nominal_bayar')
         ->withTimestamps();
    }

    public function detailPembayaran()
    {
        return $this->hasMany(DetailPembayaranDenda::class, 'id_pembayaran', 'id_pembayaran');
    }
}

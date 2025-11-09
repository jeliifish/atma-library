<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';
    protected $fillable = [
        'id_member',
        'jumlah_bayar',
        'metode',
        'status',
        'tgl_bayar'
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
        )->withPivot('nominal')
         ->withTimestamps();
    }
}

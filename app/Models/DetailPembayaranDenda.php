<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPembayaranDenda extends Model
{
    protected $table = 'detail_pembayaran';
    protected $primaryKey = 'id_detail'; // kalau kamu punya kolom id sendiri
    public $timestamps = true;

    protected $fillable = [
        'id_pembayaran',
        'id_denda',
        'nominal_bayar',
        
    ];

    public function pembayaranDenda()
    {
        return $this->belongsTo(PembayaranDenda::class, 'id_pembayaran', 'id_pembayaran');
    }

    public function denda()
    {
        return $this->belongsTo(Denda::class, 'id_denda', 'id_denda');
    }
}

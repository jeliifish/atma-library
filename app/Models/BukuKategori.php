<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Pivot;

class BukuKategori extends Pivot
{
    protected $table = 'buku_kategori';
    protected $primaryKey = null;
    public $timestamps = false;

    protected $fillable = [
        'id_buku',
        'id_kategori'
    ];
    
}

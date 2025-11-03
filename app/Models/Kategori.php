<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Kategori extends Model
{
    protected $table = 'kategori';
    protected $primaryKey = 'id_kategori';
    public $timestamps = false;

    protected $fillable = [
        'nama_kategori',
        'deskripsi'
    ];

    public function buku()
    {
        return $this->belongsToMany(Buku::class, 'buku_kategori', 'id_kategori', 'id_buku');
    }
}

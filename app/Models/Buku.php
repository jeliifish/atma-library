<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buku extends Model
{
    protected $table = 'buku';
    protected $keyType = 'string';
    protected $primaryKey = 'id_buku';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'judul',
        'penulis',
        'penerbit',
        'ISBN',
        'tahun_terbit',
        'url_foto_cover'
    ];

    public function copyBuku()
    {
        return $this->hasMany(CopyBuku::class, 'id_buku', 'id_buku');
    }

    public function kategoris()
    {
        return $this->belongsToMany(Kategori::class, 'buku_kategori', 'id_buku', 'id_kategori');
    }

}

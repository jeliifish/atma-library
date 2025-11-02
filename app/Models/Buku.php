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

    // === AUTO GENERATE ID BKU0001, BKU0002, ... ===
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($buku) {
            if (!$buku->id_buku) {
                $lastBuku = Buku::orderBy('id_buku', 'desc')->first();
                if ($lastBuku) {
                    $lastNumber = (int) substr($lastBuku->id_buku, 3);
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }

                $buku->id_buku = 'BKU' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function copyBuku()
    {
        return $this->hasMany(CopyBuku::class, 'id_buku', 'id_buku');
    }

    public function kategoris()
    {
        return $this->belongsToMany(Kategori::class, 'buku_kategori', 'id_buku', 'id_kategori');
    }

}

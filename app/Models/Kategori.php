<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Kategori extends Model
{
    protected $table = 'kategori';
    protected $primaryKey = 'id_kategori';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nama_kategori',
        'deskripsi'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kategori) {
            if (empty($kategori->id_kategori)) {
                $kategori->id_kategori = self::generateNextId();
            }
        });
    }

    /**
     * Generate ID berikutnya
     */
    protected static function generateNextId()
    {
        $lastId = DB::table('kategori')
            ->where('id_kategori', 'LIKE', 'KTG%')
            ->orderBy('id_kategori', 'desc')
            ->value('id_kategori');

        if (!$lastId) {
            return 'KTG0001';
        }

        // Extract angka dari KTG0001 format
        $number = (int) substr($lastId, 3);
        $nextNumber = $number + 1;

        return 'KTG' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function buku()
    {
        return $this->belongsToMany(Buku::class, 'buku_kategori', 'id_kategori', 'id_buku');
    }
}

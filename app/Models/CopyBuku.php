<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class CopyBuku extends Model
{
    protected $table = 'copy_buku';
    protected $primaryKey = 'id_buku_copy';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_buku',
        'rak',
        'status'
    ];

      // === AUTO-ID: BKU0001-001, BKU0001-002, ... ===
    protected static function booted()
    {
        static::creating(function (CopyBuku $model) {
            if (empty($model->id_buku)) {
                throw new \InvalidArgumentException('id_buku wajib diisi untuk membuat id_buku_copy.');
            }

            if (empty($model->id_buku_copy)) {
                $model->id_buku_copy = static::nextCopyId($model->id_buku);
            }
        });
    }

    public static function nextCopyId(string $id_buku): string
    {
        // Versi aman-konkuren (pakai transaksi + lock)
        return DB::transaction(function () use ($id_buku) {
            // Kunci baris-baris terkait buku ini agar nomor tidak dobel saat insert bersamaan
            DB::table('copy_buku')
                ->where('id_buku', $id_buku)
                ->lockForUpdate()
                ->get();

            // Ambil suffix terbesar setelah tanda '-'
            $latest = static::where('id_buku', $id_buku)
                ->where('id_buku_copy', 'like', $id_buku . '-%')
                ->orderByRaw("CAST(SUBSTRING_INDEX(id_buku_copy, '-', -1) AS UNSIGNED) DESC")
                ->value('id_buku_copy');

            $nextNumber = $latest
                ? (int) substr($latest, strlen($id_buku) + 1) + 1
                : 1;

            return sprintf('%s-%03d', $id_buku, $nextNumber);
        });
    }


    public function detailPeminjaman()
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_buku_copy', 'id_buku_copy');
    }

    public function peminjaman()
    {
        return $this->belongsToMany(Peminjaman::class, 'detail_peminjaman', 'id_buku_copy', 'nomor_pinjam')
            ->withPivot(['id_member', 'tgl_kembali', 'status']);
    }

    public function buku()
    {
        return $this->belongsTo(Buku::class, 'id_buku', 'id_buku');
    }
}

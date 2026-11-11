<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'tbl_produk';

    protected $primaryKey = 'id_produk';

    protected $fillable = [
        'id_kategori',
        'sku',
        'nama_produk',
        'slug',
        'deskripsi',
        'harga',
        'gambar',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }

    public function stokCabang(): HasMany
    {
        return $this->hasMany(StokCabang::class, 'id_produk', 'id_produk');
    }

    public function detailTransaksi(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'id_produk', 'id_produk');
    }

    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class, 'id_produk', 'id_produk');
    }

    public function stokDiCabang(?int $idCabang): int
    {
        if (! $idCabang) {
            return (int) $this->stokCabang()->sum('stok');
        }

        return (int) $this->stokCabang()->where('id_cabang', $idCabang)->value('stok');
    }
}

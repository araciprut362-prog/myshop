<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokCabang extends Model
{
    use HasFactory;

    protected $table = 'tbl_stok_cabang';

    protected $primaryKey = 'id_stok';

    public $timestamps = false;

    protected $fillable = [
        'id_cabang',
        'id_produk',
        'stok',
        'stok_minimum',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'stok' => 'integer',
            'stok_minimum' => 'integer',
            'updated_at' => 'datetime',
        ];
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}

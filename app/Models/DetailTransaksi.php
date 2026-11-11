<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailTransaksi extends Model
{
    use HasFactory;

    protected $table = 'tbl_detail_transaksi';

    protected $primaryKey = 'id_detail';

    public $timestamps = false;

    protected $fillable = [
        'id_transaksi',
        'id_produk',
        'nama_produk_snapshot',
        'sku_snapshot',
        'harga_satuan',
        'jumlah',
        'diskon_item',
        'subtotal',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'diskon_item' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'jumlah' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi', 'id_transaksi');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}

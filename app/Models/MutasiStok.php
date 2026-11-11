<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiStok extends Model
{
    use HasFactory;

    protected $table = 'tbl_mutasi_stok';

    protected $primaryKey = 'id_mutasi';

    public $timestamps = false;

    protected $fillable = [
        'id_cabang',
        'id_produk',
        'id_user',
        'id_transaksi',
        'jenis_mutasi',
        'jumlah',
        'keterangan',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'created_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi', 'id_transaksi');
    }
}

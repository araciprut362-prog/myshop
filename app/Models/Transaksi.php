<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'tbl_transaksi';

    protected $primaryKey = 'id_transaksi';

    protected $fillable = [
        'kode_transaksi',
        'tanggal',
        'waktu_transaksi',
        'id_pelanggan',
        'id_kasir',
        'id_cabang',
        'jenis_transaksi',
        'status_transaksi',
        'status_pembayaran',
        'status_stok',
        'subtotal',
        'diskon',
        'pajak',
        'ongkir',
        'total_belanja',
        'alamat_pengiriman',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'waktu_transaksi' => 'datetime',
            'subtotal' => 'decimal:2',
            'diskon' => 'decimal:2',
            'pajak' => 'decimal:2',
            'ongkir' => 'decimal:2',
            'total_belanja' => 'decimal:2',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_kasir', 'id_user');
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }

    public function detailTransaksi(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'id_transaksi', 'id_transaksi');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'id_transaksi', 'id_transaksi');
    }

    public function pembayaranTerbaru(): HasOne
    {
        return $this->hasOne(Pembayaran::class, 'id_transaksi', 'id_transaksi')->latest('id_pembayaran');
    }

    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class, 'id_transaksi', 'id_transaksi');
    }
}

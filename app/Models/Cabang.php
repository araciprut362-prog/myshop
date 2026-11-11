<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cabang extends Model
{
    use HasFactory;

    protected $table = 'tbl_cabang';

    protected $primaryKey = 'id_cabang';

    public $timestamps = false;

    protected $fillable = [
        'kode_cabang',
        'nama_cabang',
        'alamat',
        'kota',
        'telepon',
        'status',
        'created_at',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_cabang', 'id_cabang');
    }

    public function stok(): HasMany
    {
        return $this->hasMany(StokCabang::class, 'id_cabang', 'id_cabang');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_cabang', 'id_cabang');
    }

    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class, 'id_cabang', 'id_cabang');
    }

    public function keranjang(): HasMany
    {
        return $this->hasMany(Keranjang::class, 'id_cabang', 'id_cabang');
    }
}

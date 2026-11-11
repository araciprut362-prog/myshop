<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pelanggan extends Model
{
    use HasFactory;

    protected $table = 'tbl_pelanggan';

    protected $primaryKey = 'id_pelanggan';

    protected $fillable = [
        'nama_pelanggan',
        'kota',
        'no_hp',
        'alamat',
        'kode_pos',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function keranjang(): HasMany
    {
        return $this->hasMany(Keranjang::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function keranjangAktif(): HasOne
    {
        return $this->hasOne(Keranjang::class, 'id_pelanggan', 'id_pelanggan')
            ->where('status', 'AKTIF')
            ->latest('id_keranjang');
    }
}

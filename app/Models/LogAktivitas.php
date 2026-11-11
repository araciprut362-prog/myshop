<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends Model
{
    use HasFactory;

    protected $table = 'tbl_log_aktivitas';

    protected $primaryKey = 'id_log';

    public $timestamps = false;

    protected $fillable = [
        'id_user',
        'aktivitas',
        'tabel_terkait',
        'id_referensi',
        'keterangan',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public static function catat(?int $idUser, string $aktivitas, ?string $tabel = null, ?int $idReferensi = null, ?string $keterangan = null, ?string $ip = null): static
    {
        return static::create([
            'id_user' => $idUser,
            'aktivitas' => $aktivitas,
            'tabel_terkait' => $tabel,
            'id_referensi' => $idReferensi,
            'keterangan' => $keterangan,
            'ip_address' => $ip ?? request()->ip(),
        ]);
    }
}

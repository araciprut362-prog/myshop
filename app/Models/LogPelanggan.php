<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogPelanggan extends Model
{
    use HasFactory;

    protected $table = 'log_pelanggan';

    protected $primaryKey = 'id_log';

    public $timestamps = false;

    protected $fillable = [
        'aktivitas',
        'id_pelanggan',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }
}

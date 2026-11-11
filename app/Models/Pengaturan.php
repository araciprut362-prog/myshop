<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    use HasFactory;

    protected $table = 'tbl_pengaturan';

    protected $primaryKey = 'id_pengaturan';

    public $timestamps = false;

    protected $fillable = [
        'nama_pengaturan',
        'nilai_pengaturan',
        'updated_at',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $setting = static::where('nama_pengaturan', $key)->first();

        return $setting ? $setting->nilai_pengaturan : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['nama_pengaturan' => $key],
            ['nilai_pengaturan' => $value]
        );
    }
}

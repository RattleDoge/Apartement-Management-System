<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    protected $fillable = [
        'nama_fasilitas',
        'max_pengunjung',
        'jumlah_orang',
        'durasi',
        'max_terlambat',
        'min_hadir',
        'open_fasilitas',
        'close_fasilitas',
        'check_billing',
        'is_berbayar',
        'biaya',
        'icon',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'is_berbayar' => 'boolean',
            'biaya'       => 'decimal:2',
        ];
    }
}

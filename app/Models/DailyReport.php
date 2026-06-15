<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    protected $fillable = [
        'nama_staff', 'departemen', 'tanggal', 'kegiatan', 'lokasi', 'keterangan',
    ];

    protected $casts = ['tanggal' => 'date'];
}

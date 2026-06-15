<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreventiveMaintenance extends Model
{
    protected $fillable = [
        'judul', 'area', 'tanggal', 'jam_mulai', 'jam_selesai',
        'penanggung_jawab', 'status', 'catatan',
    ];

    protected $casts = ['tanggal' => 'date'];

    public static function statusOptions(): array
    {
        return ['Terjadwal', 'Selesai', 'Dibatalkan'];
    }
}

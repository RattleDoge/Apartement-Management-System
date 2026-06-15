<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyContact extends Model
{
    protected $fillable = [
        'nama',
        'alamat',
        'kategori',
        'telp',
        'no_wa',
        'latitude',
        'longitude',
    ];

    public static function kategoriOptions(): array
    {
        return [
            'Rumah Sakit',
            'Klinik',
            'Kantor Polisi',
            'Pemadam Kebakaran',
            'Ambulans',
            'PLN',
            'PDAM',
            'Lainnya',
        ];
    }
}

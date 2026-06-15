<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandoverUnit extends Model
{
    protected $fillable = [
        'lot_no', 'tipe_unit', 'str_date', 'cmg_date', 'pic',
        'ppjb', 'bast', 'house_rule',
        'ipl_sf_paydate', 'ipl_sf_period', 'until_month', 'next_month',
        'key_count', 'access_card_count',
        'no_access_card', 'no_intercom', 'no_telpon',
        'daya_listrik', 'stand_awal_listrik', 'stand_awal_air',
        'pas_foto', 'foto_ktp', 'no_ktp',
        'input_by', 'billing_aktif',
    ];

    protected function casts(): array
    {
        return [
            'str_date'       => 'date',
            'cmg_date'       => 'date',
            'ipl_sf_paydate' => 'date',
            'ppjb'               => 'boolean',
            'bast'               => 'boolean',
            'house_rule'         => 'boolean',
            'billing_aktif'      => 'boolean',
            'stand_awal_listrik' => 'decimal:2',
            'stand_awal_air'     => 'decimal:2',
        ];
    }
}

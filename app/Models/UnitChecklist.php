<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitChecklist extends Model
{
    protected $fillable = [
        'lot_no',
        'tenant_name',
        'checklist_date',
        'defect',
        'no_mtr_water',
        'current_read',
        'first_water_invoice',
    ];

    protected $casts = [
        'checklist_date'      => 'date',
        'first_water_invoice' => 'date',
    ];
}

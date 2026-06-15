<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    protected $fillable = [
        'user_id',
        'staff_id',
        'nik_karyawan',
        'departemen',
        'jabatan',
        'digital_signature',
    ];

    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

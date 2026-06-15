<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'nama_staff',
        'departemen',
        'status',
        'no_hp_otp',
        'email',
        'role',
        'finger_id',
        'pt',
        'project',
    ];

    public static function departemenOptions(): array
    {
        return ['AM', 'CS', 'ENG', 'FA', 'HKP', 'SEC'];
    }
}

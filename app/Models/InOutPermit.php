<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InOutPermit extends Model
{
    protected $fillable = [
        'nomor', 'unit', 'tenant_name', 'tanggal', 'tanggal_ijin',
        'jam', 'jenis', 'descs', 'request_by', 'request_via',
        'status', 'foto',
        'approved_cs_by', 'approved_cs_at',
        'approved_fa_by', 'approved_fa_at',
        'approved_sec_by', 'approved_sec_at',
        'input_by', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'         => 'date',
            'tanggal_ijin'    => 'date',
            'approved_cs_at'  => 'datetime',
            'approved_fa_at'  => 'datetime',
            'approved_sec_at' => 'datetime',
            'is_active'       => 'boolean',
        ];
    }

    public static function jenisOptions(): array
    {
        return ['Masuk', 'Keluar'];
    }

    public static function requestViaOptions(): array
    {
        return ['aplikasi', 'Phone', 'WhatsApp', 'Email', 'Letter', 'Visit', 'FO'];
    }

    public static function statusOptions(): array
    {
        return [
            'Pesan Diterima',
            'Approve by Customer Service',
            'Approve by FA',
            'Approve by Security',
            'Tidak Disetujui',
        ];
    }

    // Check whether a given status can still be actioned (approved/rejected)
    public function isPending(): bool
    {
        return !in_array($this->status, ['Approve by Security', 'Tidak Disetujui']);
    }

    // Label for the next approval stage
    public function nextStageLabel(): string
    {
        return match ($this->status) {
            'Pesan Diterima'              => 'CS',
            'Approve by Customer Service' => 'FA',
            'Approve by FA'               => 'Security',
            default                       => '',
        };
    }
}

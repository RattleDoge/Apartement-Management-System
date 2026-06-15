<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $fillable = [
        'ex_in', 'no_complain', 'no_wo', 'jenis_wo', 'sub_jenis_wo',
        'tanggal', 'estimated_close', 'lot_no', 'name', 'descs',
        'status_comp', 'durasi', 'durasi_bln', 'request_by', 'request_via',
        'assign_dep', 'assign_staff', 'item_service', 'input_by',
        'balas_request', 'balas_by', 'balas_at', 'foto_pengecekan',
        'work_started', 'work_closed', 'action_by', 'action_taken', 'foto_close',
        'notified_l1_at', 'notified_l2_at',
        'notified_work_l1_at', 'notified_work_l2_at',
        'is_berbayar', 'biaya_wo', 'keterangan_biaya',
        'fin_by', 'fin_at', 'fin_status', 'fin_notes',
        'bukti_bayar_wo', 'tgl_bukti_bayar_wo',
        'cs_status', 'cs_by', 'cs_at', 'cs_notes',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'         => 'datetime',
            'estimated_close' => 'date',
            'item_service'    => 'array',
            'balas_at'        => 'datetime',
            'work_started'    => 'datetime',
            'work_closed'     => 'datetime',
            'notified_l1_at'       => 'datetime',
            'notified_l2_at'       => 'datetime',
            'notified_work_l1_at'  => 'datetime',
            'notified_work_l2_at'  => 'datetime',
            'is_berbayar'        => 'boolean',
            'biaya_wo'           => 'decimal:2',
            'fin_at'             => 'datetime',
            'tgl_bukti_bayar_wo' => 'datetime',
            'cs_at'              => 'datetime',
        ];
    }

    public function scopePending($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('status_comp')
              ->orWhereNotIn('status_comp', ['Selesai', 'Work Order Close']);
        });
    }

    public static function jenisWoOptions(): array
    {
        return [
            'CIVIL', 'ELECTRICAL', 'PLUMBING', 'MECHANICAL',
            'LIFT', 'WATER / ELECTRICITY', 'PERGANTIAN ACCESS CARD',
            'GENERAL', 'PAINTING', 'HVAC',
        ];
    }

    public static function jenisWoDeptMap(): array
    {
        return [
            'CIVIL'                  => 'ENG',
            'ELECTRICAL'             => 'ENG',
            'PLUMBING'               => 'ENG',
            'MECHANICAL'             => 'ENG',
            'LIFT'                   => 'ENG',
            'WATER / ELECTRICITY'    => 'ENG',
            'PERGANTIAN ACCESS CARD' => 'ENG',
            'GENERAL'                => 'ENG',
            'PAINTING'               => 'ENG',
            'HVAC'                   => 'ENG',
        ];
    }

    public static function statusCompOptions(): array
    {
        return ['Pesan Diterima', 'Dalam Pengecekan', 'Dalam Proses', 'Selesai', 'Work Order Close'];
    }

    public static function assignDepOptions(): array
    {
        return ['ENG', 'CS', 'SEC', 'HKP', 'FA'];
    }

    public static function generateNoWo(string $prefix): string
    {
        $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $romanMonth = $roman[now()->month - 1];
        $year   = now()->year;
        $prefix = strtoupper($prefix);

        // Counter terpisah per prefix — EX dan IN masing-masing urut sendiri
        $maxNum = 0;
        static::whereNotNull('no_wo')
            ->where('no_wo', 'like', $prefix . '%')
            ->pluck('no_wo')
            ->each(function ($noWo) use (&$maxNum, $prefix) {
                if (preg_match('/^' . $prefix . '(\d+)/', $noWo, $m)) {
                    $maxNum = max($maxNum, (int) $m[1]);
                }
            });

        return sprintf('%s%05d/%s/%d-MAP', $prefix, $maxNum + 1, $romanMonth, $year);
    }

    public static function requestViaOptions(): array
    {
        return ['Phone', 'WhatsApp', 'Email', 'Letter', 'Visit', 'FO'];
    }

    public static function subJenisWoOptions(): array
    {
        return [
            'Keramik', 'Plafond', 'Pintu', 'Jendela', 'Tembok',
            'Lampu', 'Panel Listrik', 'Stop Kontak', 'Kabel',
            'Pipa', 'Kran', 'Wastafel', 'Toilet', 'Shower',
            'AC Split', 'AC Central', 'Exhaust Fan',
            'Lift / Elevator', 'Pompa Air',
            'Meter Air', 'Meter Listrik',
            'Kunci', 'Teralis', 'Pagar',
        ];
    }
}

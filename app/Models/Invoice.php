<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'bulan', 'tahun', 'no_invoice', 'inv_date', 'jatuh_tempo',
        'debtor_acct', 'debtor_name', 'kategori', 'description',
        'ipl_amount',
        'kwh_prev', 'kwh_curr', 'kwh_used', 'daya_terpasang', 'kwh_tariff', 'listrik_amount',
        'pju_amount', 'biaya_tambahan', 'beban_tetap',
        'meter_prev', 'meter_curr', 'meter_m3', 'water_tariff', 'air_amount',
        'denda', 'other_charges', 'amount',
        'handphone', 'email', 'virtual_account',
        'status_bayar', 'tgl_bayar', 'paid_by',
        'bukti_bayar', 'tgl_bukti_bayar',
        'uploaded_by', 'upload_batch',
    ];

    protected function casts(): array
    {
        return [
            'inv_date'          => 'date',
            'jatuh_tempo'       => 'date',
            'tgl_bayar'         => 'date',
            'tgl_bukti_bayar'   => 'datetime',
            'is_berbayar'    => 'boolean',
            'ipl_amount'     => 'decimal:2',
            'listrik_amount' => 'decimal:2',
            'pju_amount'     => 'decimal:2',
            'biaya_tambahan' => 'decimal:2',
            'beban_tetap'    => 'decimal:2',
            'air_amount'     => 'decimal:2',
            'denda'          => 'decimal:2',
            'other_charges'  => 'decimal:2',
            'amount'         => 'decimal:2',
            'kwh_prev'       => 'decimal:3',
            'kwh_curr'       => 'decimal:3',
            'kwh_used'       => 'decimal:3',
            'kwh_tariff'     => 'decimal:2',
            'meter_prev'     => 'decimal:3',
            'meter_curr'     => 'decimal:3',
            'meter_m3'       => 'decimal:3',
            'water_tariff'   => 'decimal:2',
        ];
    }

    public static function kategoriOptions(): array
    {
        return ['IPL', 'Listrik', 'Air', 'IPL+Listrik+Air', 'Lainnya'];
    }

    public static function statusBayarOptions(): array
    {
        return ['Belum Lunas', 'Lunas'];
    }

    public static function bulanOptions(): array
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April',   5 => 'Mei',       6 => 'Juni',
            7 => 'Juli',    8 => 'Agustus',   9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }

    // CSV template column headers for upload
    public static function uploadTemplateHeaders(): array
    {
        return [
            'NO_INVOICE', 'INV_DATE', 'JATUH_TEMPO', 'DEBTOR_ACCT', 'DEBTOR_NAME', 'KATEGORI', 'DESCRIPTION',
            'IPL_AMOUNT', 'KWH_PREV', 'KWH_CURR', 'DAYA_TERPASANG', 'KWH_TARIFF',
            'PJU_AMOUNT', 'BIAYA_TAMBAHAN', 'BEBAN_TETAP',
            'METER_PREV', 'METER_CURR', 'WATER_TARIFF',
            'DENDA', 'OTHER_CHARGES', 'HANDPHONE', 'EMAIL', 'VIRTUAL_ACCOUNT',
        ];
    }

    private static function parseDate(string $raw): ?string
    {
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'] as $fmt) {
            try {
                $d = \Carbon\Carbon::createFromFormat($fmt, trim($raw));
                if ($d) return $d->format('Y-m-d');
            } catch (\Exception) {}
        }
        return null;
    }

    // Map a parsed file row → invoice array for create()
    public static function rowToData(array $row, string $uploadedBy, string $batchId): ?array
    {
        $get = function (string $key) use ($row): string {
            return trim($row[$key] ?? $row[strtolower($key)] ?? $row[strtoupper($key)] ?? '');
        };

        $noInvoice = $get('NO_INVOICE');
        if (!$noInvoice) return null;

        // Parse date (supports d/m/Y, Y-m-d, d-m-Y)
        $invDateRaw = $get('INV_DATE');
        $invDate = null;
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'] as $fmt) {
            try {
                $invDate = \Carbon\Carbon::createFromFormat($fmt, $invDateRaw);
                if ($invDate) break;
            } catch (\Exception) {}
        }

        $clean = fn($v) => (float) str_replace([',', ' '], ['', ''], $v);

        $iplAmount   = $clean($get('IPL_AMOUNT'));
        $kwhPrev     = $clean($get('KWH_PREV'));
        $kwhCurr     = $clean($get('KWH_CURR'));
        $kwhUsed     = max(0, $kwhCurr - $kwhPrev);
        $kwhTariff   = $clean($get('KWH_TARIFF'));
        $listrik     = round($kwhUsed * $kwhTariff, 2);
        $pju         = $clean($get('PJU_AMOUNT'));
        $biayaTamb   = $clean($get('BIAYA_TAMBAHAN'));
        $bebanTetap  = $clean($get('BEBAN_TETAP'));

        $mPrev    = $clean($get('METER_PREV'));
        $mCurr    = $clean($get('METER_CURR'));
        $mM3      = max(0, $mCurr - $mPrev);
        $wTariff  = $clean($get('WATER_TARIFF'));
        $air      = round($mM3 * $wTariff + $bebanTetap, 2);

        $denda    = $clean($get('DENDA'));
        $other    = $clean($get('OTHER_CHARGES'));
        $total    = round($iplAmount + $listrik + $pju + $biayaTamb + $air + $denda + $other, 2);

        return [
            'no_invoice'      => $noInvoice,
            'inv_date'        => $invDate?->format('Y-m-d'),
            'jatuh_tempo'     => $get('JATUH_TEMPO') ? self::parseDate($get('JATUH_TEMPO')) : $invDate?->addDays(14)->format('Y-m-d'),
            'bulan'           => $invDate?->month ?? now()->month,
            'tahun'           => $invDate?->year  ?? now()->year,
            'debtor_acct'     => $get('DEBTOR_ACCT'),
            'debtor_name'     => $get('DEBTOR_NAME'),
            'kategori'        => $get('KATEGORI') ?: 'IPL+Listrik+Air',
            'description'     => $get('DESCRIPTION'),
            'ipl_amount'      => $iplAmount,
            'kwh_prev'        => $kwhPrev  ?: null,
            'kwh_curr'        => $kwhCurr  ?: null,
            'kwh_used'        => $kwhUsed  ?: null,
            'daya_terpasang'  => $get('DAYA_TERPASANG') ?: null,
            'kwh_tariff'      => $kwhTariff ?: null,
            'listrik_amount'  => $listrik,
            'pju_amount'      => $pju      ?: null,
            'biaya_tambahan'  => $biayaTamb ?: null,
            'beban_tetap'     => $bebanTetap ?: null,
            'meter_prev'      => $mPrev    ?: null,
            'meter_curr'      => $mCurr    ?: null,
            'meter_m3'        => $mM3      ?: null,
            'water_tariff'    => $wTariff  ?: null,
            'air_amount'      => $air,
            'denda'           => $denda,
            'other_charges'   => $other,
            'amount'          => $total,
            'handphone'       => $get('HANDPHONE'),
            'email'           => $get('EMAIL'),
            'virtual_account' => $get('VIRTUAL_ACCOUNT'),
            'status_bayar'    => 'Belum Lunas',
            'uploaded_by'     => $uploadedBy,
            'upload_batch'    => $batchId,
        ];
    }
}

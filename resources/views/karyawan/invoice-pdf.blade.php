<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Invoice {{ $invoice->no_invoice }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 10px; color: #000; background:#fff; }

/* ── Header ── */
.header-table { width:100%; border-collapse:collapse; margin-bottom: 10px; }
.logo-cell { width:38%; vertical-align:top; padding-right:12px; }
.logo-title { font-size:22px; font-weight:bold; color:#2e7d32; letter-spacing:1px; }
.logo-sub   { font-size:9px; color:#555; margin-top:2px; letter-spacing:0.5px; }
.info-cell  { width:62%; vertical-align:top; }
.info-table { width:100%; border-collapse:collapse; }
.info-table td { font-size:9.5px; padding:1px 3px; vertical-align:top; }
.info-label { white-space:nowrap; color:#444; }
.info-colon { width:6px; }
.info-val   { font-weight:bold; }

/* ── Section titles ── */
.section-title {
    font-size:9px; font-weight:bold; letter-spacing:0.5px;
    background:#f5f5f5; border-top:1px solid #000; border-bottom:1px solid #ccc;
    padding:3px 6px; margin-top:8px; margin-bottom:0;
}
.no-inv-line { font-size:9px; padding:2px 6px; color:#444; }

/* ── Billing rows ── */
.bill-table { width:100%; border-collapse:collapse; margin-top:2px; }
.bill-table td { font-size:9.5px; padding:2px 6px; vertical-align:top; }
.bill-table .lbl { color:#333; }
.bill-table .amt { text-align:right; width:90px; white-space:nowrap; }
.bill-table .sub { padding-left:20px; color:#555; }
.bill-table .total-row td { border-top:1px solid #999; font-weight:bold; }
.bill-table .blank-row td { border-top:1px solid #ccc; }
.bill-table .indent  { padding-left: 14px; }
.bill-table .sub-amt { text-align:right; width:115px; white-space:nowrap; padding-right:4px; }

/* ── Grand total ── */
.grand-table { width:100%; border-collapse:collapse; margin-top:8px; border-top:2px solid #000; }
.grand-table td { font-size:11px; font-weight:bold; padding:4px 6px; }
.grand-table .amt { text-align:right; }
.terbilang { font-size:9px; font-style:italic; padding:4px 6px 8px; }

/* ── Notes ── */
.notes-section { margin-top:8px; border-top:1px solid #999; padding-top:6px; }
.notes-section p { font-size:8.5px; color:#333; line-height:1.5; margin-bottom:1px; }
.notes-section .note-title { font-weight:bold; margin-bottom:3px; }

/* ── Separator ── */
hr.sep { border:none; border-top:1px solid #000; margin:6px 0; }
</style>
</head>
<body style="padding:20px 28px;">

@php
    /* ── Helpers ── */
    $fmt  = fn($v)  => 'Rp ' . number_format((float)$v, 0, ',', '.');
    $tgl  = fn($d)  => $d ? \Carbon\Carbon::parse($d)->isoFormat('DD MMMM YYYY') : '—';

    /* ── Electricity tiers ── */
    $kwhBase   = (float)($invoice->listrik_amount ?? 0);
    $pju       = (float)($invoice->pju_amount     ?? 0);
    $biayaTamb = (float)($invoice->biaya_tambahan ?? 0);
    $totalList = $kwhBase + $pju + $biayaTamb;

    /* ── Water tiers ── */
    $m3       = (float)($invoice->meter_m3  ?? 0);
    $wTariff  = (float)($invoice->water_tariff ?? 0);
    $bebanTetap = (float)($invoice->beban_tetap ?? 0);
    /* tier 1: 0-10, tier2: 11-20, tier3: >20 — rates Madison Park */
    $r1 = 12550; $r2 = 17500; $r3 = 21500;
    $t1 = min($m3, 10)              * $r1;
    $t2 = min(max($m3 - 10, 0), 10) * $r2;
    $t3 = max($m3 - 20, 0)          * $r3;
    /* If water_tariff provided (custom), override tier calc */
    $airTierTotal = ($wTariff > 0 && $m3 > 0) ? round($m3 * $wTariff, 2) : ($t1 + $t2 + $t3);
    $totalAir  = $airTierTotal + $bebanTetap;

    /* Use stored air_amount if available (more accurate) */
    $storedAir = (float)($invoice->air_amount ?? 0);
    if ($storedAir > 0) $totalAir = $storedAir;

    /* ── Total grand ── */
    $ipl   = (float)($invoice->ipl_amount    ?? 0);
    $denda = (float)($invoice->denda         ?? 0);
    $other = (float)($invoice->other_charges ?? 0);
    $grand = $ipl + $totalList + $totalAir + $denda + $other;

    /* ── Denda WE: 5% = 1 bln, 10% = 2 bln, 15% = 3+ bln ── */
    $dendaWe = 0; $dendaWeRate = 0;
    if (($invoice->status_bayar ?? 'Belum Lunas') !== 'Lunas' && ($totalList + $totalAir) > 0) {
        $dueDateCarbon = $invoice->jatuh_tempo
            ? \Carbon\Carbon::parse($invoice->jatuh_tempo)
            : ($invoice->inv_date ? \Carbon\Carbon::parse($invoice->inv_date)->addDays(14) : null);
        if ($dueDateCarbon && now()->gt($dueDateCarbon)) {
            $overdueDays = (int) now()->diffInDays($dueDateCarbon);
            if      ($overdueDays <= 30) $dendaWeRate = 5;
            elseif  ($overdueDays <= 60) $dendaWeRate = 10;
            else                          $dendaWeRate = 15;
            $dendaWe = (int) round(($totalList + $totalAir) * $dendaWeRate / 100);
        }
    }
    $grand += $dendaWe;

    /* ── Terbilang helper (simple) ── */
    $terbilang = function(int $n) use (&$terbilang): string {
        $s = ['', 'satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan',
              'sepuluh','sebelas'];
        if ($n < 12) return $s[$n];
        if ($n < 20) return $terbilang($n - 10) . ' belas';
        if ($n < 100) return $s[(int)($n/10)] . ' puluh' . ($n%10 ? ' ' . $terbilang($n%10) : '');
        if ($n < 200) return 'seratus' . ($n%100 ? ' ' . $terbilang($n%100) : '');
        if ($n < 1000) return $s[(int)($n/100)] . ' ratus' . ($n%100 ? ' ' . $terbilang($n%100) : '');
        if ($n < 2000) return 'seribu' . ($n%1000 ? ' ' . $terbilang($n%1000) : '');
        if ($n < 1000000) return $terbilang((int)($n/1000)) . ' ribu' . ($n%1000 ? ' ' . $terbilang($n%1000) : '');
        if ($n < 1000000000) return $terbilang((int)($n/1000000)) . ' juta' . ($n%1000000 ? ' ' . $terbilang($n%1000000) : '');
        return $terbilang((int)($n/1000000000)) . ' miliar' . ($n%1000000000 ? ' ' . $terbilang($n%1000000000) : '');
    };
    $terbilangStr = ucfirst($terbilang((int)round($grand)));

    $jatuhTempo = $invoice->jatuh_tempo
        ? $tgl($invoice->jatuh_tempo)
        : ($invoice->inv_date ? $tgl($invoice->inv_date->addDays(14)) : '—');

    /* ── Periode meter: 16 bulan-2 s/d 15 bulan-1 dari tanggal invoice ── */
    $baseDate     = \Carbon\Carbon::parse($invoice->inv_date ?? now());
    $periodStart  = $baseDate->copy()->subMonths(2)->setDay(16);
    $periodEnd    = $baseDate->copy()->subMonth()->setDay(15);
    $periodStr    = $periodStart->isoFormat('D MMMM YYYY') . ' - ' . $periodEnd->isoFormat('D MMMM YYYY');

    /* ── Nama Pemilik: dari invoice → fallback Tenant → User ── */
    $namaPemilik = $invoice->debtor_name ?: null;
    if (!$namaPemilik) {
        $tenant = \App\Models\Tenant::where('unit_number', $invoice->debtor_acct)->first();
        $namaPemilik = $tenant?->user?->name ?? null;
    }

    /* ── Daya terpasang: dari invoice → fallback HandoverUnit ── */
    $dayaDisplay = $invoice->daya_terpasang ?? null;
    if (!$dayaDisplay) {
        $ho = \App\Models\HandoverUnit::where('lot_no', $invoice->debtor_acct)->first();
        if ($ho?->daya_listrik) {
            $vaNum = (float) preg_replace('/[^0-9.]/', '', $ho->daya_listrik);
            if ($vaNum > 0) {
                // Simpan sebagai KVA (misal "2200 VA" → "2.20 KVA")
                $dayaDisplay = $vaNum >= 100
                    ? number_format($vaNum / 1000, 2)
                    : number_format($vaNum, 2);
            }
        }
    }

    $hasListrik = $totalList > 0 || $invoice->kwh_used > 0;
    $hasAir     = $totalAir  > 0 || $m3 > 0;
@endphp

{{-- ══════════════ HEADER ══════════════ --}}
<table class="header-table">
    <tr>
        <td class="logo-cell">
            <div class="logo-title">madison park</div>
            <div class="logo-sub">@ PODOMORO CITY</div>
        </td>
        <td class="info-cell">
            <table class="info-table">
                <tr>
                    <td class="info-label">Unit</td>
                    <td class="info-colon">:</td>
                    <td class="info-val">{{ $invoice->debtor_acct }}</td>
                </tr>
                <tr>
                    <td class="info-label">Virtual Acct</td>
                    <td class="info-colon">:</td>
                    <td>{{ $invoice->virtual_account ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Nama Pemilik</td>
                    <td class="info-colon">:</td>
                    <td class="info-val">{{ $namaPemilik ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Tanggal Invoice</td>
                    <td class="info-colon">:</td>
                    <td>{{ $invoice->inv_date ? $tgl($invoice->inv_date) : '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Tanggal Jatuh Tempo</td>
                    <td class="info-colon">:</td>
                    <td>{{ $jatuhTempo }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<hr class="sep">

{{-- ══════════════ TAGIHAN MAINTENANCE FEE ══════════════ --}}
<div class="no-inv-line">No Invoice : {{ $invoice->no_invoice ?? '' }}</div>
<div class="section-title">TAGIHAN MAINTENANCE FEE</div>

<table class="bill-table">
    <tr>
        <td class="lbl">Total Tunggakan Tagihan Maintenance Fee (Belum Termasuk Denda)</td>
        <td class="amt">{{ $ipl > 0 ? $fmt($ipl) : 'Rp 0' }}</td>
    </tr>
    <tr>
        <td class="lbl">Denda Keterlambatan Pembayaran atas Tagihan Maintenance Fee yang Sudah Dibayar</td>
        <td class="amt">Rp 0</td>
    </tr>
</table>

{{-- ══════════════ TAGIHAN LISTRIK DAN AIR ══════════════ --}}
@if($hasListrik || $hasAir)
<div class="no-inv-line" style="margin-top:6px;">
    No Invoice : {{ $invoice->no_invoice }}
</div>
<div class="section-title">TAGIHAN LISTRIK DAN AIR</div>

@php
    /* ── Tunggakan WE dari invoice bulan sebelumnya yang belum lunas ── */
    $tunggakanWe = (float)\App\Models\Invoice::where('debtor_acct', $invoice->debtor_acct)
        ->where('status_bayar', 'Belum Lunas')
        ->where('id', '!=', $invoice->id)
        ->selectRaw('COALESCE(SUM(
            COALESCE(listrik_amount,0) + COALESCE(pju_amount,0) +
            COALESCE(biaya_tambahan,0) + COALESCE(air_amount,0)
        ),0) as total')
        ->value('total') ?? 0;

@endphp

<table class="bill-table">

{{-- ── Listrik ── --}}
@if($hasListrik)
<tr>
    <td class="lbl" colspan="2" style="padding-top:4px;">
        <strong>Electricity</strong> - E-{{ $invoice->debtor_acct }}
        &nbsp;( {{ $periodStr }} )
    </td>
</tr>
<tr>
    <td class="lbl indent">Daya Listrik terpasang</td>
    <td class="sub-amt">{{ $dayaDisplay ? number_format((float)$dayaDisplay, 2) . ' KVA' : '—' }}</td>
</tr>
<tr>
    <td class="lbl indent">Meter Awal</td>
    <td class="sub-amt">{{ $invoice->kwh_prev !== null ? number_format((float)$invoice->kwh_prev, 0) . ' KWH' : '—' }}</td>
</tr>
<tr>
    <td class="lbl indent">Meter Akhir</td>
    <td class="sub-amt">{{ $invoice->kwh_curr !== null ? number_format((float)$invoice->kwh_curr, 0) . ' KWH' : '—' }}</td>
</tr>
<tr>
    <td class="lbl indent">
        Pemakaian
        @if($invoice->kwh_used !== null && $invoice->kwh_tariff)
            : {{ number_format((float)$invoice->kwh_used, 0) }} KWH &times; Rp {{ number_format((float)$invoice->kwh_tariff, 2, ',', '.') }}
        @elseif($invoice->kwh_used !== null)
            : {{ number_format((float)$invoice->kwh_used, 0) }} KWH
        @endif
    </td>
    <td class="sub-amt">{{ $kwhBase > 0 ? 'Rp ' . number_format($kwhBase, 2, ',', '.') : '—' }}</td>
</tr>
@if($pju > 0)
<tr>
    <td class="lbl indent">PJU</td>
    <td class="sub-amt">Rp {{ number_format($pju, 2, ',', '.') }}</td>
</tr>
@endif
@if($biayaTamb > 0)
<tr>
    <td class="lbl indent">Biaya Tambahan</td>
    <td class="sub-amt">Rp {{ number_format($biayaTamb, 2, ',', '.') }}</td>
</tr>
@endif
<tr>
    <td class="lbl" style="border-top:1px solid #bbb;"></td>
    <td class="sub-amt" style="border-top:1px solid #bbb; font-weight:bold;">{{ $fmt($totalList) }}</td>
</tr>
@endif

{{-- ── Air ── --}}
@if($hasAir)
<tr style="{{ $hasListrik ? 'border-top:1px solid #ddd;' : '' }}">
    <td class="lbl" colspan="2" style="padding-top:4px;">
        <strong>Water</strong> - W-{{ $invoice->debtor_acct }}
        &nbsp;( {{ $periodStr }} )
    </td>
</tr>
<tr>
    <td class="lbl indent">Meter Awal</td>
    <td class="sub-amt">{{ $invoice->meter_prev !== null ? number_format((float)$invoice->meter_prev, 0) . ' M3' : '—' }}</td>
</tr>
<tr>
    <td class="lbl indent">Meter Akhir</td>
    <td class="sub-amt">{{ $invoice->meter_curr !== null ? number_format((float)$invoice->meter_curr, 0) . ' M3' : '—' }}</td>
</tr>
<tr>
    <td class="lbl indent">Pemakaian</td>
    <td class="sub-amt">{{ number_format($m3, 0) }} M3</td>
</tr>
@if($wTariff == 0)
<tr>
    <td class="lbl indent">0 - 10 M3 &nbsp;: {{ number_format((int)min($m3, 10)) }} &times; Rp {{ number_format($r1, 2, ',', '.') }}</td>
    <td class="sub-amt">Rp {{ number_format($t1, 2, ',', '.') }}</td>
</tr>
<tr>
    <td class="lbl indent">11 - 20 M3 : {{ number_format((int)min(max($m3-10,0),10)) }} &times; Rp {{ number_format($r2, 2, ',', '.') }}</td>
    <td class="sub-amt">Rp {{ number_format($t2, 2, ',', '.') }}</td>
</tr>
<tr>
    <td class="lbl indent">&gt; 20 M3 &nbsp;&nbsp;: {{ number_format((int)max($m3-20,0)) }} &times; Rp {{ number_format($r3, 2, ',', '.') }}</td>
    <td class="sub-amt">Rp {{ number_format($t3, 2, ',', '.') }}</td>
</tr>
@endif
@if($bebanTetap > 0)
<tr>
    <td class="lbl indent">Beban Tetap</td>
    <td class="sub-amt">{{ $fmt($bebanTetap) }}</td>
</tr>
@endif
<tr>
    <td class="lbl" style="border-top:1px solid #bbb;"></td>
    <td class="sub-amt" style="border-top:1px solid #bbb; font-weight:bold;">{{ $fmt($totalAir) }}</td>
</tr>
<tr>
    <td class="lbl indent">Total Tagihan WE Bulan Ini</td>
    <td class="sub-amt">{{ $fmt($totalList + $totalAir) }}</td>
</tr>
<tr>
    <td class="lbl indent">Total Tunggakan WE (Tagihan Sebelumnya Belum Lunas)</td>
    <td class="sub-amt">{{ $tunggakanWe > 0 ? $fmt($tunggakanWe) : 'Rp 0' }}</td>
</tr>
<tr>
    <td class="lbl indent">Denda Keterlambatan Pembayaran atas Tagihan WE yang Sudah Dibayar</td>
    <td class="sub-amt">{{ $dendaWe > 0 ? $fmt($dendaWe) . ' (' . $dendaWeRate . '%)' : 'Rp 0' }}</td>
</tr>
@endif

</table>
@endif

{{-- ══════════════ GRAND TOTAL ══════════════ --}}
<table class="grand-table">
    <tr>
        <td style="width:70%;"></td>
        <td class="amt" style="font-size:13px;">{{ $fmt($grand) }}</td>
    </tr>
</table>
<div class="terbilang">## {{ $terbilangStr }} Rupiah ##</div>

<hr class="sep">

{{-- ══════════════ CATATAN ══════════════ --}}
<div class="notes-section">
    <p class="note-title">Catatan :</p>
    <p>1. Pembayaran dapat dilakukan melalui :</p>
    <p style="padding-left:14px;">a. Auto Collection (Pendebetan Via rekening BCA)</p>
    <p style="padding-left:14px;">b. Signature on File (SOF) yaitu Pendebetan Melalui Kartu Kredit</p>
    <p style="padding-left:14px;">c. Non cash Payment yaitu melalui transfer (Virtual Account BCA)</p>
    <p>2. Setiap keterlambatan pembayaran akan dikenakan denda mulai 5% - 15% untuk Listrik &amp; Air, dan 5% - 25% untuk IPL &amp; SF</p>
    <p>3. Pemutusan sementara listrik dan air dilakukan tanggal 20 setiap bulannya</p>
    <p>4. Apabila dalam 61 hari dari Tanggal Jatuh Tempo ( untuk Listrik &amp; Air ) belum melakukan pembayaran, maka Badan Pengelola akan melakukan pemutusan Listrik &amp; Air secara Permanen. Untuk penyambungan kembali dikenakan biaya sebesar Rp. 1.500.000,-</p>
    <p>5. Bilamana ada perubahan data seperti: Moving In, Moving Out, Ganti kepemilikan, Ganti Identitas, dll segera menghubungi Customer Service kami, untuk informasi lebih lanjut, silakan menghubungi Customer Service kami di No. Telp {{ config('app.cs_phone', '-') }}</p>
    <p style="margin-top:4px; font-style:italic;">Tanpa informasi tagihan ini, tenant tetap berkewajiban membayar semua tagihan setiap bulannya.</p>
</div>

</body>
</html>

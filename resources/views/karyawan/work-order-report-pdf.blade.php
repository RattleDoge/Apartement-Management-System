<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Work Order Report</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size:9px; color:#111; background:#fff; padding:20px 24px; }

    .header { text-align:center; margin-bottom:12px; }
    .header .co    { font-size:13px; font-weight:bold; letter-spacing:1px; }
    .header .title { font-size:11px; margin-top:3px; }
    .header .sub   { font-size:10px; color:#444; margin-top:2px; }
    .header hr { border:none; border-top:2px solid #000; margin:8px 0 3px; }
    .header hr.thin { border-top:1px solid #000; margin-top:2px; }

    .summary { display:table; width:100%; border-collapse:collapse; margin-bottom:12px; }
    .sum-box { display:table-cell; width:25%; border:1px solid #ccc; padding:6px 8px; text-align:center; }
    .sum-num { font-size:18px; font-weight:bold; color:#1e3a8a; }
    .sum-label { font-size:9px; color:#555; margin-top:1px; }

    .section-title { font-size:10px; font-weight:bold; background:#1e3a8a; color:#fff; padding:3px 7px; margin-bottom:5px; }

    table.wo { width:100%; border-collapse:collapse; }
    table.wo th { background:#dbeafe; color:#1e40af; padding:3px 5px; text-align:left; font-size:8.5px; border:1px solid #93c5fd; white-space:nowrap; }
    table.wo td { padding:3px 5px; font-size:8.5px; border:1px solid #e5e7eb; vertical-align:top; }
    table.wo tr:nth-child(even) td { background:#f9fafb; }

    .badge { display:inline-block; padding:1px 4px; border-radius:2px; font-size:8px; }
    .badge-green { background:#dcfce7; color:#166534; }
    .badge-gray  { background:#f3f4f6; color:#374151; }
    .badge-red   { background:#fee2e2; color:#991b1b; }

    .footer { margin-top:16px; font-size:9px; color:#666; text-align:right; border-top:1px solid #ccc; padding-top:5px; }

    .empty { text-align:center; padding:20px; color:#999; font-size:10px; }
</style>
</head>
<body>

<div class="header">
    <div class="co">AMS — Apartement Management System</div>
    <div class="title">LAPORAN WORK ORDER</div>
    <div class="sub">
        Madison Park (MAP)
        @if($periodeFrom && $periodeUntil)
            &nbsp;|&nbsp; Periode: {{ $periodeFrom }} s/d {{ $periodeUntil }}
        @elseif($periodeFrom)
            &nbsp;|&nbsp; Mulai: {{ $periodeFrom }}
        @elseif($periodeUntil)
            &nbsp;|&nbsp; s/d: {{ $periodeUntil }}
        @else
            &nbsp;|&nbsp; Semua Periode
        @endif
    </div>
    <hr>
    <hr class="thin">
</div>

@php
    $total   = $wos->count();
    $selesai = $wos->where('status_comp', 'Work Order Close')->count();
    $pending = $total - $selesai;
@endphp

<div class="summary">
    <div class="sum-box">
        <div class="sum-num">{{ $total }}</div>
        <div class="sum-label">Total WO</div>
    </div>
    <div class="sum-box">
        <div class="sum-num" style="color:#16a34a;">{{ $selesai }}</div>
        <div class="sum-label">Selesai</div>
    </div>
    <div class="sum-box">
        <div class="sum-num" style="color:#dc2626;">{{ $pending }}</div>
        <div class="sum-label">Pending</div>
    </div>
    <div class="sum-box">
        <div class="sum-num" style="font-size:13px;">
            @if($total > 0)
                {{ number_format(($selesai / $total) * 100, 0) }}%
            @else
                —
            @endif
        </div>
        <div class="sum-label">Completion Rate</div>
    </div>
</div>

<div class="section-title">DAFTAR WORK ORDER</div>

@if($wos->isEmpty())
    <div class="empty">Tidak ada data Work Order pada periode ini.</div>
@else
<table class="wo">
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th style="width:70px;">No. WO</th>
            <th style="width:52px;">Tanggal</th>
            <th style="width:42px;">Lot No</th>
            <th style="width:80px;">Nama</th>
            <th style="width:60px;">Jenis WO</th>
            <th style="width:70px;">Sub Jenis</th>
            <th>Deskripsi</th>
            <th style="width:75px;">Status</th>
            <th style="width:40px;">Durasi</th>
            <th style="width:60px;">Assign Staff</th>
            <th style="width:52px;">Selesai</th>
        </tr>
    </thead>
    <tbody>
    @foreach($wos as $i => $wo)
    <tr>
        <td style="text-align:center;">{{ $i + 1 }}</td>
        <td style="font-family:monospace; font-size:8px;">{{ $wo->no_wo }}</td>
        <td>{{ $wo->tanggal?->format('d/m/Y') }}</td>
        <td>{{ $wo->lot_no }}</td>
        <td>{{ $wo->name }}</td>
        <td>{{ $wo->jenis_wo }}</td>
        <td>{{ $wo->sub_jenis_wo }}</td>
        <td>{{ \Str::limit($wo->descs, 50) }}</td>
        <td>
            <span class="badge {{ $wo->status_comp === 'Work Order Close' ? 'badge-green' : ($wo->status_comp ? 'badge-red' : 'badge-gray') }}">
                {{ $wo->status_comp ?: 'Pending' }}
            </span>
        </td>
        <td style="text-align:center;">{{ $wo->durasi ?: '—' }}</td>
        <td>{{ $wo->assign_staff }}</td>
        <td>{{ $wo->work_closed?->format('d/m/Y') ?: '—' }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    Total: {{ $total }} Work Order &nbsp;|&nbsp; Dicetak oleh AMS — Madison Park (MAP) &nbsp;|&nbsp; {{ now()->isoFormat('DD MMMM YYYY, HH:mm') }}
</div>

</body>
</html>

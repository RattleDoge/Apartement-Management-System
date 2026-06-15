<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan — {{ $namaBulan }}</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size:11px; color:#111; background:#fff; padding:28px 32px; }

    .header { text-align:center; margin-bottom:14px; }
    .header .co { font-size:14px; font-weight:bold; letter-spacing:1px; }
    .header .title { font-size:12px; margin-top:3px; }
    .header .sub   { font-size:11px; color:#444; margin-top:2px; }
    .header hr { border:none; border-top:2px solid #000; margin:10px 0 4px; }
    .header hr.thin { border-top:1px solid #000; margin-top:2px; }

    .section { margin-top:16px; }
    .section-title { font-size:11px; font-weight:bold; background:#1e3a8a; color:#fff; padding:4px 8px; margin-bottom:6px; }

    .stats-grid { display:table; width:100%; border-collapse:collapse; }
    .stat-box { display:table-cell; width:25%; border:1px solid #ccc; padding:8px 10px; text-align:center; }
    .stat-num { font-size:20px; font-weight:bold; color:#1e3a8a; }
    .stat-label { font-size:10px; color:#555; margin-top:2px; }

    table { width:100%; border-collapse:collapse; margin-top:4px; }
    th { background:#dbeafe; color:#1e40af; padding:4px 6px; text-align:left; font-size:10px; border:1px solid #93c5fd; }
    td { padding:4px 6px; font-size:10px; border:1px solid #e5e7eb; }
    tr:nth-child(even) td { background:#f9fafb; }

    .footer { margin-top:24px; font-size:10px; color:#666; text-align:right; border-top:1px solid #ccc; padding-top:6px; }
    .row2 { display:table; width:100%; border-collapse:collapse; margin-top:16px; }
    .col2 { display:table-cell; width:50%; vertical-align:top; padding-right:8px; }
    .col2:last-child { padding-right:0; padding-left:8px; }
    .badge { display:inline-block; padding:1px 5px; border-radius:3px; font-size:10px; }
    .badge-green { background:#dcfce7; color:#166534; }
    .badge-red   { background:#fee2e2; color:#991b1b; }
    .badge-gray  { background:#f3f4f6; color:#374151; }
    .rating { color:#f59e0b; font-size:13px; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="co">AMS — Apartement Management System</div>
    <div class="title">LAPORAN BULANAN OPERASIONAL</div>
    <div class="sub">Madison Park (MAP) &nbsp;|&nbsp; Periode: {{ $namaBulan }}</div>
    <hr>
    <hr class="thin">
</div>

{{-- Ringkasan Work Order --}}
<div class="section">
    <div class="section-title">WORK ORDER</div>
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-num">{{ $woTotal }}</div>
            <div class="stat-label">Total WO</div>
        </div>
        <div class="stat-box">
            <div class="stat-num" style="color:#16a34a;">{{ $woSelesai }}</div>
            <div class="stat-label">Selesai</div>
        </div>
        <div class="stat-box">
            <div class="stat-num" style="color:#dc2626;">{{ $woPending }}</div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-box">
            <div class="stat-num" style="color:#d97706;">
                @if($avgRating)
                    {{ number_format($avgRating, 1) }} ★
                @else
                    —
                @endif
            </div>
            <div class="stat-label">Avg Rating</div>
        </div>
    </div>
</div>

{{-- WO by Jenis & Tenant Request --}}
<div class="row2">
    <div class="col2">
        <div class="section">
            <div class="section-title">WO PER KATEGORI</div>
            @if($woByJenis->count())
            <table>
                <thead><tr><th>Kategori</th><th style="text-align:center;width:50px">Jumlah</th></tr></thead>
                <tbody>
                @foreach($woByJenis as $row)
                <tr><td>{{ $row->jenis_wo }}</td><td style="text-align:center">{{ $row->total }}</td></tr>
                @endforeach
                </tbody>
            </table>
            @else
            <p style="color:#999;font-size:10px;padding:4px">Tidak ada data.</p>
            @endif
        </div>
    </div>
    <div class="col2">
        <div class="section">
            <div class="section-title">TENANT REQUEST</div>
            <div class="stats-grid" style="margin-bottom:8px;">
                <div class="stat-box">
                    <div class="stat-num">{{ $trTotal }}</div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num" style="color:#16a34a;">{{ $trSelesai }}</div>
                    <div class="stat-label">Selesai</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num" style="color:#dc2626;">{{ $trTotal - $trSelesai }}</div>
                    <div class="stat-label">Belum</div>
                </div>
            </div>
            @if($trByKategori->count())
            <table>
                <thead><tr><th>Kategori</th><th style="text-align:center;width:50px">Jumlah</th></tr></thead>
                <tbody>
                @foreach($trByKategori as $row)
                <tr><td>{{ $row->kategori }}</td><td style="text-align:center">{{ $row->total }}</td></tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>

{{-- Invoice --}}
<div class="section">
    <div class="section-title">INVOICE & KEUANGAN</div>
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-num" style="color:#16a34a;">{{ $invLunas }}</div>
            <div class="stat-label">Invoice Lunas</div>
        </div>
        <div class="stat-box">
            <div class="stat-num" style="color:#dc2626;">{{ $invBelum }}</div>
            <div class="stat-label">Invoice Belum Bayar</div>
        </div>
        <div class="stat-box">
            <div class="stat-num" style="font-size:13px;">Rp {{ number_format($invRevenue, 0, ',', '.') }}</div>
            <div class="stat-label">Revenue Bulan Ini</div>
        </div>
        <div class="stat-box">
            <div class="stat-num">{{ $totalUnit }}</div>
            <div class="stat-label">Total Unit</div>
        </div>
    </div>
</div>

{{-- Daftar WO --}}
@if($woList->count())
<div class="section" style="margin-top:16px;">
    <div class="section-title">DAFTAR WORK ORDER BULAN INI</div>
    <table>
        <thead>
            <tr>
                <th style="width:20px">#</th>
                <th>No. WO</th>
                <th>Tanggal</th>
                <th>Lot No</th>
                <th>Jenis</th>
                <th>Deskripsi</th>
                <th style="text-align:center">Status</th>
                <th style="text-align:center">Durasi</th>
            </tr>
        </thead>
        <tbody>
        @foreach($woList as $i => $wo)
        <tr>
            <td style="text-align:center">{{ $i + 1 }}</td>
            <td style="font-family:monospace">{{ $wo->no_wo }}</td>
            <td>{{ $wo->tanggal?->format('d/m/Y') }}</td>
            <td>{{ $wo->lot_no }}</td>
            <td>{{ $wo->jenis_wo }}</td>
            <td>{{ \Str::limit($wo->descs, 40) }}</td>
            <td style="text-align:center">
                <span class="badge {{ $wo->status_comp === 'Work Order Close' ? 'badge-green' : 'badge-gray' }}">
                    {{ $wo->status_comp ?: 'Pending' }}
                </span>
            </td>
            <td style="text-align:center">{{ $wo->durasi ?: '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="footer">
    Dicetak oleh AMS — Madison Park (MAP) &nbsp;|&nbsp; {{ now()->isoFormat('DD MMMM YYYY, HH:mm') }}
</div>

</body>
</html>

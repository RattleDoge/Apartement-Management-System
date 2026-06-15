<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>QR Scan — {{ $res->nomor }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; background: #f0f4f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.10); max-width: 420px; width: 100%; overflow: hidden; }
    .card-header { background: #1a5c2e; color: #fff; padding: 18px 24px; }
    .card-header h1 { font-size: 15px; font-weight: 700; letter-spacing: .5px; }
    .card-header p { font-size: 11px; opacity: .8; margin-top: 2px; }
    .card-body { padding: 20px 24px; }
    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 700; margin-bottom: 16px; }
    .status-siap   { background: #e0e7ff; color: #3730a3; }
    .status-active { background: #fef3c7; color: #92400e; }
    .status-selesai{ background: #d1fae5; color: #065f46; }
    .status-ditolak{ background: #fee2e2; color: #991b1b; }
    .info-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 16px; }
    .info-table td { padding: 5px 0; vertical-align: top; }
    .info-table td:first-child { color: #6b7280; width: 130px; font-size: 11px; }
    .info-table td:last-child { font-weight: 600; color: #111; }
    .divider { border: none; border-top: 1px solid #e5e7eb; margin: 16px 0; }
    .btn { display: block; width: 100%; padding: 12px; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; text-align: center; margin-bottom: 8px; }
    .btn-buka   { background: #1a5c2e; color: #fff; }
    .btn-buka:hover   { background: #154d26; }
    .btn-tutup  { background: #d97706; color: #fff; }
    .btn-tutup:hover  { background: #b45309; }
    .flash { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; border-radius: 8px; padding: 10px 14px; font-size: 12px; font-weight: 600; margin-bottom: 16px; }
    .note { font-size: 10px; color: #9ca3af; text-align: center; margin-top: 8px; }
</style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <h1>Reservasi Fasilitas</h1>
        <p>{{ $res->nomor }}</p>
    </div>
    <div class="card-body">

        @if(session('flash'))
        <div class="flash">✓ {{ session('flash') }}</div>
        @endif

        @php
            $badgeClass = match($res->status) {
                'Siap Pelaksanaan'    => 'status-siap',
                'Sedang Berlangsung'  => 'status-active',
                'Selesai'             => 'status-selesai',
                'Ditolak'             => 'status-ditolak',
                default               => '',
            };
        @endphp
        <span class="status-badge {{ $badgeClass }}">{{ $res->status }}</span>

        <table class="info-table">
            <tr><td>Fasilitas</td><td>{{ $res->nama_fasilitas }}</td></tr>
            <tr><td>Tanggal</td><td>{{ $res->tanggal_reservasi?->format('d/m/Y') }}</td></tr>
            <tr><td>Jam</td><td>{{ $res->jam_mulai ? substr($res->jam_mulai,0,5) : '-' }} – {{ $res->jam_selesai ? substr($res->jam_selesai,0,5) : '-' }}</td></tr>
            <tr><td>Unit</td><td>{{ $res->unit }}</td></tr>
            <tr><td>Tenant</td><td>{{ $res->tenant_name }}</td></tr>
            <tr><td>Jumlah Tamu</td><td>{{ $res->jumlah_tamu }} orang</td></tr>
            <tr><td>Keperluan</td><td>{{ $res->keperluan }}</td></tr>
            @if($res->is_berbayar)
            <tr><td>Biaya</td><td>Rp {{ number_format($res->biaya, 0, ',', '.') }}</td></tr>
            <tr><td>Status Bayar</td><td>{{ $res->status_bayar }}</td></tr>
            @else
            <tr><td>Biaya</td><td>Gratis</td></tr>
            @endif
        </table>

        <hr class="divider">

        @if($res->status === 'Siap Pelaksanaan')
        <form method="POST" action="{{ route('karyawan.qr.buka', $res->qr_token) }}"
              onsubmit="return confirm('Buka reservasi ini? Fasilitas akan mulai digunakan.')">
            @csrf
            <button type="submit" class="btn btn-buka">▶ Buka — Mulai Penggunaan</button>
        </form>
        <p class="note">Klik tombol setelah tenant hadir di lokasi fasilitas</p>

        @elseif($res->status === 'Sedang Berlangsung')
        <form method="POST" action="{{ route('karyawan.qr.tutup', $res->qr_token) }}"
              onsubmit="return confirm('Tutup reservasi? Penggunaan fasilitas dianggap selesai.')">
            @csrf
            <button type="submit" class="btn btn-tutup">■ Tutup — Selesai</button>
        </form>
        <p class="note">Dibuka oleh: {{ $res->sec_open_by }} pada {{ $res->sec_open_at?->format('d/m/Y H:i') }}</p>

        @elseif($res->status === 'Selesai')
        <div style="text-align:center; padding:12px; color:#065f46;">
            <div style="font-size:32px; margin-bottom:8px;">✓</div>
            <div style="font-weight:700; font-size:13px;">Reservasi Selesai</div>
            <div style="font-size:11px; color:#6b7280; margin-top:4px;">
                Ditutup oleh {{ $res->sec_close_by }} — {{ $res->sec_close_at?->format('d/m/Y H:i') }}
            </div>
        </div>

        @else
        <div style="text-align:center; padding:12px; color:#6b7280; font-size:12px;">
            QR ini tidak dapat digunakan saat ini.<br>Status: {{ $res->status }}
        </div>
        @endif

    </div>
</div>
</body>
</html>

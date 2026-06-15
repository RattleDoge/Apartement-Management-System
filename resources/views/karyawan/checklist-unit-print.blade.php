<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checklist Unit — {{ $checklist->lot_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; padding: 32px; }
        .header { text-align: center; border-bottom: 2px solid #1a5c2e; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; font-weight: bold; color: #1a5c2e; letter-spacing: 2px; }
        .header p { font-size: 11px; color: #555; margin-top: 2px; }
        .title { font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; color: #333; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.info td { padding: 6px 10px; font-size: 12px; vertical-align: top; }
        table.info td:first-child { width: 160px; font-weight: 600; color: #444; }
        table.info td:nth-child(2) { width: 12px; color: #888; }
        .defect-box { border: 1px solid #ccc; min-height: 60px; padding: 8px 10px; border-radius: 4px; font-size: 12px; line-height: 1.5; color: #333; white-space: pre-wrap; }
        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #1a5c2e; border-bottom: 1px solid #c8e6c9; padding-bottom: 4px; margin: 16px 0 8px; }
        .footer { margin-top: 40px; display: flex; justify-content: space-between; }
        .footer .sig { text-align: center; width: 180px; }
        .footer .sig .line { border-top: 1px solid #333; margin-top: 50px; padding-top: 4px; font-size: 11px; }
        @media print {
            body { padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom:16px;">
        <button onclick="window.print()"
                style="padding:6px 18px;background:#1a5c2e;color:#fff;border:none;border-radius:4px;font-size:12px;cursor:pointer;font-weight:bold;">
            🖨 Print
        </button>
        <button onclick="window.close()"
                style="margin-left:8px;padding:6px 18px;background:#eee;border:1px solid #ccc;border-radius:4px;font-size:12px;cursor:pointer;">
            Tutup
        </button>
    </div>

    <div class="header">
        <h1>AMS — MADISON PARK</h1>
        <p>Apartement Management System</p>
    </div>

    <div class="title">Checklist Unit Serah Terima</div>

    <div class="section-title">Informasi Unit</div>
    <table class="info">
        <tr>
            <td>Lot No</td><td>:</td>
            <td style="font-weight:bold;font-family:monospace;">{{ $checklist->lot_no }}</td>
        </tr>
        <tr>
            <td>Tenant Name</td><td>:</td>
            <td style="font-weight:bold;">{{ $checklist->tenant_name ?? '—' }}</td>
        </tr>
        <tr>
            <td>Checklist Date</td><td>:</td>
            <td>{{ $checklist->checklist_date->format('d F Y') }}</td>
        </tr>
    </table>

    <div class="section-title">Kondisi Unit</div>
    <table class="info">
        <tr>
            <td style="vertical-align:top;">Defect / Catatan</td><td style="vertical-align:top;">:</td>
            <td>
                <div class="defect-box">{{ $checklist->defect ?: '(Tidak ada catatan defect)' }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Meter Air</div>
    <table class="info">
        <tr>
            <td>No Meter Air</td><td>:</td>
            <td>{{ $checklist->no_mtr_water ?? '—' }}</td>
        </tr>
        <tr>
            <td>Current Read</td><td>:</td>
            <td>{{ $checklist->current_read ?? '—' }}</td>
        </tr>
        <tr>
            <td>First Water Invoice</td><td>:</td>
            <td>{{ $checklist->first_water_invoice ? $checklist->first_water_invoice->format('d F Y') : '—' }}</td>
        </tr>
    </table>

    <div class="footer">
        <div class="sig">
            <div class="line">Pengelola / Management</div>
        </div>
        <div class="sig">
            <div class="line">Tenant / Pemilik Unit</div>
        </div>
    </div>

    <p style="margin-top:32px;font-size:10px;color:#aaa;text-align:center;">
        Dicetak pada {{ now()->format('d F Y H:i') }} — AMS Madison Park
    </p>
</body>
</html>

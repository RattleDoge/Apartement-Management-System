<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Work Order {{ $wo->no_wo }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; background: #fff; }
    @page { size: A4 portrait; margin: 12mm 15mm; }
    @media print { body { margin: 0; } .no-print { display: none !important; } }
    @media screen { .page { max-width: 700px; margin: 0 auto; padding: 12px; } }
    @media print  { .page { width: 100%; padding: 0; } }

    /* Header */
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
    .logo-left { text-align: left; }
    .logo-left .brand { font-size: 18px; font-weight: 900; color: #1a5c2e; letter-spacing: 1px; }
    .logo-left .brand-sub { font-size: 9px; color: #555; }
    .logo-right { text-align: right; }
    .logo-right .brand-r { font-size: 13px; font-weight: 900; color: #1a5c2e; }
    .logo-right .brand-r-sub { font-size: 8px; color: #555; letter-spacing: 1px; }

    /* Title bar */
    .title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
    .title-row h1 { font-size: 15px; font-weight: 900; text-align: center; flex: 1; letter-spacing: 2px; }
    .wo-number-box { border: 1px solid #000; padding: 3px 8px; font-size: 11px; font-weight: bold; white-space: nowrap; }

    /* Tables */
    table { width: 100%; border-collapse: collapse; }
    td, th { border: 1px solid #000; padding: 2px 5px; vertical-align: top; }
    .info-table td { font-size: 11px; }
    .info-table td:first-child { width: 90px; font-weight: bold; }

    /* Description + Priority */
    .desc-priority { display: flex; gap: 0; margin-top: 0; }
    .desc-box { flex: 1; border: 1px solid #000; border-right: none; padding: 5px; min-height: 90px; font-size: 11px; }
    .priority-box { width: 100px; border: 1px solid #000; padding: 0; }
    .priority-box .p-row { border-bottom: 1px solid #000; padding: 2px 5px; font-size: 10px; display: flex; align-items: center; gap: 4px; }
    .priority-box .p-row:last-child { border-bottom: none; }
    .priority-box .p-row input[type=checkbox] { margin: 0; }

    /* Items table */
    .items-table th { background-color: #f0f0f0; font-size: 10px; text-align: center; }
    .items-table td { font-size: 10px; }
    .items-table td.num { text-align: center; }
    .items-table td.price { text-align: right; }

    /* Grand total */
    .grand-total td { font-weight: bold; font-size: 11px; }

    /* Footer table */
    .footer-table td { font-size: 10px; padding: 3px 5px; }
    .footer-table td:first-child { font-weight: bold; width: 100px; }

    /* Print button */
    .no-print { text-align: center; margin: 15px 0; }
    .btn-print { padding: 6px 24px; background: #1a5c2e; color: #fff; border: none; cursor: pointer; font-size: 12px; border-radius: 3px; }
    .btn-print:hover { background: #2a7a3e; }

    hr.divider { border: none; border-top: 1px solid #000; margin: 4px 0; }
</style>
</head>
<body>
<div class="page">

    {{-- Print button (hidden on print) --}}
    <div class="no-print" style="margin-bottom: 10px;">
        <button class="btn-print" onclick="window.print()">&#128424; Print / Save PDF</button>
        <a href="{{ route('karyawan.cs.work-order.pdf', $wo->id) }}"
           style="margin-left:8px; padding:6px 16px; background:#dc2626; color:#fff; text-decoration:none; font-size:12px; border-radius:3px; cursor:pointer;">
            &#11123; Download PDF
        </a>
        <button onclick="window.close()" style="margin-left:8px; padding:6px 16px; cursor:pointer;">Tutup</button>
    </div>

    {{-- ── Header ── --}}
    <div class="header">
        <div class="logo-left">
            <div class="brand">madison <span style="color:#c8a000;">park</span></div>
            <div class="brand-sub">Apartment &amp; Lifestyle</div>
        </div>
        <div class="logo-right">
            <div class="brand-r">⬛ INNER CITY</div>
            <div class="brand-r" style="margin-top:-2px;">MANAGEMENT</div>
            <div class="brand-r-sub">Property Management Services</div>
        </div>
    </div>

    <hr class="divider">

    {{-- ── Title ── --}}
    <div class="title-row" style="margin-top:6px;">
        <div style="width:120px;"></div>
        <h1>WORK ORDER</h1>
        <div class="wo-number-box">No. {{ $wo->no_wo }}</div>
    </div>

    {{-- ── Info Table ── --}}
    <table class="info-table" style="margin-top:6px;">
        <tr>
            <td style="width:90px;">Requested By</td>
            <td colspan="3">{{ $wo->lot_no ? $wo->lot_no . ' - ' : '' }}{{ $wo->name }}</td>
        </tr>
        <tr>
            <td>Date</td>
            <td style="width:180px;">{{ $wo->tanggal->format('d-m-Y H:i') }}</td>
            <td style="width:60px;">Ref. No</td>
            <td>{{ $wo->no_complain ?? '' }}</td>
        </tr>
        <tr>
            <td>Received By</td>
            <td>{{ $wo->input_by }}</td>
            <td>Date. {{ $wo->tanggal->format('d-m-Y') }}</td>
            <td>Time. {{ $wo->tanggal->format('H:i:s') }}</td>
        </tr>
        <tr>
            <td>Approved By</td>
            <td>PM.</td>
            <td>Chief.</td>
            <td>CA.</td>
        </tr>
        <tr>
            <td>in Charge</td>
            <td>Spv.</td>
            <td colspan="2">Crew. {{ $wo->assign_staff }}</td>
        </tr>
    </table>

    {{-- ── Work Description + Priority ── --}}
    <div class="desc-priority" style="margin-top:0;">
        <div class="desc-box">
            <div style="font-weight:bold; margin-bottom:4px;">Work Description:</div>
            <div>{{ $wo->descs }}</div>
            @if($wo->action_taken)
            <div style="margin-top:8px; font-style:italic;">.{{ $wo->action_taken }}</div>
            @endif
        </div>
        <div class="priority-box">
            <div class="p-row"><input type="checkbox" disabled> Priority</div>
            <div class="p-row"><input type="checkbox" disabled> Urgent</div>
            <div class="p-row" style="background:#e8f5e9;"><input type="checkbox" disabled checked> Routine</div>
            <div class="p-row"><input type="checkbox" disabled> Prev. Maint</div>
            <div class="p-row"><input type="checkbox" disabled> ShutDown</div>
            <div class="p-row"><input type="checkbox" disabled> New work</div>
        </div>
    </div>

    {{-- ── Items Table ── --}}
    @php
        $items      = $wo->item_service ?? [];
        $grandTotal = collect($items)->sum(fn($i) => ($i['harga'] ?? 0) * ($i['qty'] ?? 1));
    @endphp
    <table class="items-table" style="margin-top:0;">
        <thead>
            <tr>
                <th style="width:24px;">NO</th>
                <th>ITEMS</th>
                <th style="width:60px;">PART.NO</th>
                <th style="width:30px;">QTY</th>
                <th style="width:60px;">UNIT</th>
                <th style="width:60px;">PRICE</th>
                <th style="width:60px;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $idx => $item)
            @php $total = ($item['harga'] ?? 0) * ($item['qty'] ?? 1); @endphp
            <tr>
                <td class="num">{{ $idx + 1 }}.</td>
                <td>{{ $item['nama'] ?? '' }}</td>
                <td class="num"></td>
                <td class="num">{{ $item['qty'] ?? '' }}</td>
                <td class="num">{{ $item['satuan'] ?? '' }}</td>
                <td class="price">{{ number_format($item['harga'] ?? 0, 0, '.', ',') }}</td>
                <td class="price">{{ number_format($total, 0, '.', ',') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="height:50px;"></td></tr>
            @endforelse
            {{-- Empty rows to fill space --}}
            @for ($r = count($items); $r < 6; $r++)
            <tr><td class="num"></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
    </table>

    {{-- Grand Total --}}
    <table class="grand-total" style="margin-top:0;">
        <tr>
            <td colspan="5" style="border-top:none; border-left:none; border-bottom:1px solid #000; background:transparent; border-right:none;"></td>
            <td style="text-align:right; width:120px; border:1px solid #000; background:#f9f9f9;">Grand Total</td>
            <td style="text-align:right; width:60px; border:1px solid #000; background:#f9f9f9;">{{ number_format($grandTotal, 0, '.', ',') }}</td>
        </tr>
    </table>

    {{-- ── Footer ── --}}
    @php
        $ws = $wo->work_started;
        $wc = $wo->work_closed;
    @endphp
    <table class="footer-table" style="margin-top:4px;">
        <tr>
            <td>Work Started</td>
            <td>Date. {{ $ws ? $ws->format('d-m-Y') : '___________' }}</td>
            <td>Time. {{ $ws ? $ws->format('H:i:s') : '___________' }}</td>
            <td>By. {{ $wo->action_by ?? $wo->assign_staff ?? '' }}</td>
        </tr>
        <tr>
            <td>Work Complete</td>
            <td>Date. {{ $wc ? $wc->format('d-m-Y') : '___________' }}</td>
            <td>Time. {{ $wc ? $wc->format('H:i:s') : '___________' }}</td>
            <td>By. {{ $wo->action_by ?? $wo->assign_staff ?? '' }}</td>
        </tr>
        <tr>
            <td>Work Checked</td>
            <td>Spv.</td>
            <td>Time Used</td>
            <td>Posted</td>
        </tr>
        <tr>
            <td>Hand Over</td>
            <td>Date. {{ $wc ? $wc->format('d-m-Y') : '___________' }}</td>
            <td>Time. {{ $wc ? $wc->format('H:i:s') : '___________' }}</td>
            <td>Accepted</td>
        </tr>
    </table>

</div>
</body>
</html>

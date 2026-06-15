<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Work Order {{ $wo->no_wo }}</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size:11px; color:#000; background:#fff; padding:14px 18px; }

    /* Header: two-column via table */
    .header-table { width:100%; border-collapse:collapse; margin-bottom:6px; }
    .brand        { font-size:17px; font-weight:900; color:#1a5c2e; letter-spacing:1px; }
    .brand-sub    { font-size:9px; color:#555; }
    .brand-r      { font-size:12px; font-weight:900; color:#1a5c2e; text-align:right; }
    .brand-r-sub  { font-size:8px; color:#555; letter-spacing:1px; text-align:right; }

    hr.divider { border:none; border-top:1px solid #000; margin:4px 0; }

    /* Title */
    .title-table { width:100%; border-collapse:collapse; margin:6px 0; }
    .title-main  { font-size:15px; font-weight:900; letter-spacing:2px; text-align:center; }
    .wo-no-box   { border:1px solid #000; padding:3px 8px; font-size:11px; font-weight:bold; white-space:nowrap; text-align:right; }

    /* Info table */
    table.info-tbl { width:100%; border-collapse:collapse; margin-top:6px; }
    table.info-tbl td { border:1px solid #000; padding:2px 5px; font-size:11px; vertical-align:top; }
    table.info-tbl td.label { width:95px; font-weight:bold; }

    /* Desc + Priority via table */
    table.desc-tbl { width:100%; border-collapse:collapse; margin-top:0; }
    table.desc-tbl td { border:1px solid #000; vertical-align:top; padding:5px; font-size:11px; }
    td.priority-cell { width:105px; padding:0; }
    .p-row { border-bottom:1px solid #000; padding:3px 5px; font-size:10px; }
    .p-row:last-child { border-bottom:none; }

    /* Items table */
    table.items { width:100%; border-collapse:collapse; margin-top:0; }
    table.items th { background:#f0f0f0; font-size:10px; text-align:center; border:1px solid #000; padding:2px 4px; }
    table.items td { border:1px solid #000; padding:2px 4px; font-size:10px; vertical-align:top; }
    table.items td.num   { text-align:center; }
    table.items td.price { text-align:right; }

    table.grand { width:100%; border-collapse:collapse; margin-top:0; }
    table.grand td { font-weight:bold; font-size:11px; }

    table.footer-tbl { width:100%; border-collapse:collapse; margin-top:4px; }
    table.footer-tbl td { border:1px solid #000; font-size:10px; padding:3px 5px; }
    table.footer-tbl td.lbl { font-weight:bold; width:100px; }
</style>
</head>
<body>

{{-- Header --}}
<table class="header-table">
    <tr>
        <td style="width:50%;">
            <div class="brand">madison <span style="color:#c8a000;">park</span></div>
            <div class="brand-sub">Apartment &amp; Lifestyle</div>
        </td>
        <td style="width:50%; text-align:right;">
            <div class="brand-r">&#9632; INNER CITY MANAGEMENT</div>
            <div class="brand-r-sub">Property Management Services</div>
        </td>
    </tr>
</table>

<hr class="divider">

{{-- Title --}}
<table class="title-table">
    <tr>
        <td style="width:120px;"></td>
        <td class="title-main">WORK ORDER</td>
        <td style="width:140px;" class="wo-no-box">No. {{ $wo->no_wo }}</td>
    </tr>
</table>

{{-- Info --}}
<table class="info-tbl">
    <tr>
        <td class="label">Requested By</td>
        <td colspan="3">{{ $wo->lot_no ? $wo->lot_no . ' - ' : '' }}{{ $wo->name }}</td>
    </tr>
    <tr>
        <td class="label">Date</td>
        <td style="width:180px;">{{ $wo->tanggal->format('d-m-Y H:i') }}</td>
        <td style="width:65px;">Ref. No</td>
        <td>{{ $wo->no_complain ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">Received By</td>
        <td>{{ $wo->input_by }}</td>
        <td>Date. {{ $wo->tanggal->format('d-m-Y') }}</td>
        <td>Time. {{ $wo->tanggal->format('H:i:s') }}</td>
    </tr>
    <tr>
        <td class="label">Approved By</td>
        <td>PM.</td>
        <td>Chief.</td>
        <td>CA.</td>
    </tr>
    <tr>
        <td class="label">In Charge</td>
        <td>Spv.</td>
        <td colspan="2">Crew. {{ $wo->assign_staff }}</td>
    </tr>
</table>

{{-- Description + Priority --}}
<table class="desc-tbl">
    <tr>
        <td>
            <div style="font-weight:bold; margin-bottom:4px;">Work Description:</div>
            <div>{{ $wo->descs }}</div>
            @if($wo->action_taken)
            <div style="margin-top:8px; font-style:italic;">{{ $wo->action_taken }}</div>
            @endif
        </td>
        <td class="priority-cell">
            <div class="p-row">[ ] Priority</div>
            <div class="p-row">[ ] Urgent</div>
            <div class="p-row" style="background:#e8f5e9;">[x] Routine</div>
            <div class="p-row">[ ] Prev. Maint</div>
            <div class="p-row">[ ] ShutDown</div>
            <div class="p-row">[ ] New Work</div>
        </td>
    </tr>
</table>

{{-- Items --}}
@php
    $items      = $wo->item_service ?? [];
    $grandTotal = collect($items)->sum(fn($i) => ($i['harga'] ?? 0) * ($i['qty'] ?? 1));
@endphp
<table class="items">
    <thead>
        <tr>
            <th style="width:22px;">NO</th>
            <th>ITEMS</th>
            <th style="width:62px;">PART.NO</th>
            <th style="width:30px;">QTY</th>
            <th style="width:58px;">UNIT</th>
            <th style="width:62px;">PRICE</th>
            <th style="width:62px;">TOTAL</th>
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
        <tr><td colspan="7" style="height:48px;"></td></tr>
        @endforelse
        @for ($r = count($items); $r < 6; $r++)
        <tr><td class="num"></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor
    </tbody>
</table>

{{-- Grand Total --}}
<table class="grand">
    <tr>
        <td style="border:none; width:100%;"></td>
        <td style="text-align:right; width:124px; border:1px solid #000; background:#f9f9f9;">Grand Total</td>
        <td style="text-align:right; width:62px;  border:1px solid #000; background:#f9f9f9;">{{ number_format($grandTotal, 0, '.', ',') }}</td>
    </tr>
</table>

{{-- Footer --}}
@php $ws = $wo->work_started; $wc = $wo->work_closed; @endphp
<table class="footer-tbl">
    <tr>
        <td class="lbl">Work Started</td>
        <td>Date. {{ $ws ? $ws->format('d-m-Y') : '___________' }}</td>
        <td>Time. {{ $ws ? $ws->format('H:i:s') : '___________' }}</td>
        <td>By. {{ $wo->action_by ?? $wo->assign_staff ?? '' }}</td>
    </tr>
    <tr>
        <td class="lbl">Work Complete</td>
        <td>Date. {{ $wc ? $wc->format('d-m-Y') : '___________' }}</td>
        <td>Time. {{ $wc ? $wc->format('H:i:s') : '___________' }}</td>
        <td>By. {{ $wo->action_by ?? $wo->assign_staff ?? '' }}</td>
    </tr>
    <tr>
        <td class="lbl">Work Checked</td>
        <td>Spv.</td>
        <td>Time Used</td>
        <td>Posted</td>
    </tr>
    <tr>
        <td class="lbl">Hand Over</td>
        <td>Date. {{ $wc ? $wc->format('d-m-Y') : '___________' }}</td>
        <td>Time. {{ $wc ? $wc->format('H:i:s') : '___________' }}</td>
        <td>Accepted</td>
    </tr>
</table>

</body>
</html>

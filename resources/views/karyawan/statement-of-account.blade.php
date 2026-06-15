<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Statement of Account — {{ $debtor->debtor_acct }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            background: #f3f4f6;
            padding: 20px;
        }
        .page-wrapper {
            max-width: 794px;
            margin: 0 auto;
            background: #fff;
            padding: 22px 30px;
            box-shadow: 0 1px 6px rgba(0,0,0,.12);
        }

        .page-top { width: 100%; border-collapse: collapse; }
        .page-top td { vertical-align: top; padding: 0; }

        .company   { font-size: 13px; font-weight: bold; text-align: center; }
        .doc-title { font-size: 12px; font-weight: bold; text-align: center; margin-top: 2px; }
        .as-at     { font-size: 11px; text-align: center; margin-top: 1px; }

        hr.div { border: none; border-top: 1px solid #000; margin: 7px 0; }

        /* Info section — two columns via table (DomPDF-safe) */
        .info-outer { width: 100%; border-collapse: collapse; }
        .info-outer td { vertical-align: top; padding: 0; }
        .info-inner { border-collapse: collapse; }
        .info-inner td { padding: 1px 0; font-size: 10.5px; vertical-align: top; white-space: nowrap; }
        .info-inner td.lbl  { min-width: 130px; color: #222; }
        .info-inner td.sep  { padding: 1px 4px; }
        .info-inner td.val  { }

        /* Transaction table */
        .trx { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .trx thead tr { border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .trx th {
            padding: 3px 5px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
        }
        .trx th.left { text-align: left; }
        .trx td {
            padding: 2px 5px;
            font-size: 10px;
            border-bottom: 1px solid #ebebeb;
        }
        .trx td.num  { text-align: right; }
        .trx td.ctr  { text-align: center; }
        .trx tfoot tr {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-weight: bold;
        }
        .trx tfoot td { padding: 3px 5px; font-size: 10px; }

        @media print {
            body { padding: 0; background: #fff; }
            .page-wrapper { box-shadow: none; padding: 10px 16px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="page-wrapper">

    {{-- Print buttons (hidden on print/PDF) --}}
    <div class="no-print" style="text-align:right; margin-bottom: 10px;">
        <button onclick="window.print()"
                style="padding:5px 16px; background:#1a5c2e; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:11px; font-weight:bold;">
            🖨 Print
        </button>
        <button onclick="window.close()"
                style="padding:5px 12px; background:#6b7280; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:11px; margin-left:6px;">
            Tutup
        </button>
    </div>

    {{-- ── Page Top: title (center) + user/date (right) ── --}}
    <table class="page-top">
        <tr>
            <td style="width:80px;"></td>
            <td>
                <div class="company">Badan Pengelola - Madison Park</div>
                <div class="doc-title">Statement Of Account</div>
                <div class="as-at">As At {{ now()->format('d/m/Y') }}</div>
            </td>
            <td style="width:100px; text-align:right; font-size:10px; line-height:1.6;">
                {{ auth()->user()->name ?? '' }}<br>
                {{ now()->format('d/m/Y') }}
            </td>
        </tr>
    </table>

    <hr class="div">

    {{-- ── Debtor Info: two-column layout ── --}}
    <table class="info-outer">
        <tr>
            {{-- Left column --}}
            <td style="width:50%;">
                <table class="info-inner">
                    <tr>
                        <td class="lbl">Debtor A/C</td>
                        <td class="sep">:</td>
                        <td class="val"><strong>{{ $debtor->debtor_acct }}</strong></td>
                    </tr>
                    <tr>
                        <td class="lbl">Name</td>
                        <td class="sep">:</td>
                        <td class="val"><strong>{{ $debtor->debtor_name }}</strong></td>
                    </tr>
                    <tr>
                        <td class="lbl">Lot No.</td>
                        <td class="sep">:</td>
                        <td class="val">{{ $debtor->debtor_acct }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Deposit Utilities</td>
                        <td class="sep">:</td>
                        <td class="val">0</td>
                    </tr>
                    <tr>
                        <td class="lbl">Deposit Maintenance</td>
                        <td class="sep">:</td>
                        <td class="val">0</td>
                    </tr>
                </table>
            </td>

            {{-- Right column --}}
            <td style="width:50%; vertical-align:top;">
                <table class="info-inner">
                    <tr>
                        <td class="lbl" style="min-width:110px;">VA BCA</td>
                        <td class="sep">:</td>
                        <td class="val">{{ $debtor->virtual_account ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">UBP Mandiri</td>
                        <td class="sep">:</td>
                        <td class="val"></td>
                    </tr>
                    <tr>
                        <td class="lbl">VA WE</td>
                        <td class="sep">:</td>
                        <td class="val"></td>
                    </tr>
                    <tr>
                        <td class="lbl">VA IPL</td>
                        <td class="sep">:</td>
                        <td class="val"></td>
                    </tr>
                    <tr>
                        <td class="lbl">Deposit Fitout</td>
                        <td class="sep">:</td>
                        <td class="val">0</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr class="div">

    {{-- ── Transaction Table ── --}}
    <table class="trx">
        <thead>
            <tr>
                <th class="left">Inv. Date</th>
                <th class="left">Invoice No.</th>
                <th class="left">Trx. Type</th>
                <th>Curr.</th>
                <th>OR Date</th>
                <th class="left">OR No</th>
                <th>Activity</th>
                <th style="text-align:right;">Debit</th>
                <th style="text-align:right;">Credit</th>
                <th style="text-align:right;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @php
                $balance     = 0;
                $totalDebit  = 0;
                $totalCredit = 0;
            @endphp

            @forelse($invoices as $inv)
            @php
                $debit   = (float) $inv->amount;
                $isPaid  = $inv->status_bayar === 'Lunas';
                $credit  = $isPaid ? $debit : 0;
                $balance = $balance + $debit - $credit;
                $totalDebit  += $debit;
                $totalCredit += $credit;
                $activity = $isPaid ? 'VA' : '';
            @endphp
            <tr>
                <td>{{ $inv->inv_date?->format('d/m/Y') }}</td>
                <td>{{ $inv->no_invoice }}</td>
                <td>{{ $inv->kategori }}</td>
                <td class="ctr">IDR</td>
                <td class="ctr">{{ $isPaid ? $inv->tgl_bayar?->format('d/m/Y') : '' }}</td>
                <td>{{ $isPaid ? ($inv->paid_by ?? '') : '' }}</td>
                <td class="ctr">{{ $activity }}</td>
                <td class="num">{{ $debit > 0 ? number_format($debit, 2, ',', '.') : '' }}</td>
                <td class="num">{{ $credit > 0 ? number_format($credit, 2, ',', '.') : '' }}</td>
                <td class="num">{{ number_format($balance, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align:center; padding:14px; color:#666;">
                    Tidak ada transaksi.
                </td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" style="text-align:right; padding-right:8px;">Total :</td>
                <td class="num">{{ number_format($totalDebit, 2, ',', '.') }}</td>
                <td class="num">{{ number_format($totalCredit, 2, ',', '.') }}</td>
                <td class="num">{{ number_format($balance, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

</div>
</body>
</html>

<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination, WithFileUploads;

    // ── Filters ──────────────────────────────────────────
    public int    $fBulan    = 0;
    public int    $fTahun    = 0;
    public string $fKategori = '';
    public string $fDebtorAcct   = '';
    public string $fDebtorName   = '';
    public string $fStatus       = '';
    public bool   $fBuktiBayar   = false;

    // ── Upload state ──────────────────────────────────────
    public bool   $showUploadPanel = false;
    public        $uploadFile      = null;
    public array  $previewRows     = [];
    public bool   $showPreview     = false;
    public string $uploadError     = '';
    public string $importMsg       = '';
    public int    $importedCount   = 0;
    public int    $skippedCount    = 0;

    // ── Detail panel ─────────────────────────────────────
    public ?int $detailId = null;

    public function mount(): void
    {
        $this->fBulan = now()->month;
        $this->fTahun = now()->year;
    }

    public function updated(string $prop): void
    {
        if (!str_starts_with($prop, 'upload') && !str_starts_with($prop, 'preview')
            && !str_starts_with($prop, 'import') && $prop !== 'showUploadPanel'
            && $prop !== 'detailId') {
            $this->resetPage();
        }
    }

    // ── Upload flow ───────────────────────────────────────
    public function openUpload(): void
    {
        $this->showUploadPanel = true;
        $this->uploadFile      = null;
        $this->previewRows     = [];
        $this->showPreview     = false;
        $this->uploadError     = '';
        $this->importMsg       = '';
    }

    public function closeUpload(): void
    {
        $this->showUploadPanel = false;
        $this->previewRows     = [];
        $this->showPreview     = false;
    }

    public function parseFile(): void
    {
        $this->uploadError = '';
        $this->importMsg   = '';

        $this->validate([
            'uploadFile' => 'required|file|mimes:csv,txt|max:20480',
        ], [
            'uploadFile.required' => 'Pilih file terlebih dahulu.',
            'uploadFile.mimes'    => 'File harus berformat CSV (.csv) atau TXT (.txt).',
        ]);

        $path = $this->uploadFile->getRealPath();
        $ext  = strtolower($this->uploadFile->getClientOriginalExtension());

        try {
            $rows = $this->readFile($path, $ext);

            if (empty($rows)) {
                $this->uploadError = 'File tidak mengandung data yang valid.';
                return;
            }

            // Process rows for preview (show computed values)
            $this->previewRows = [];
            foreach (array_slice($rows, 0, 10) as $row) {
                $data = Invoice::rowToData($row, auth()->user()->name, 'PREVIEW');
                if ($data) {
                    $this->previewRows[] = array_merge($row, [
                        '_kwh_used'   => number_format($data['kwh_used'] ?? 0, 2),
                        '_listrik'    => number_format($data['listrik_amount'], 0, ',', '.'),
                        '_meter_m3'   => number_format($data['meter_m3'] ?? 0, 2),
                        '_air'        => number_format($data['air_amount'], 0, ',', '.'),
                        '_total'      => number_format($data['amount'], 0, ',', '.'),
                    ]);
                }
            }

            if (empty($this->previewRows)) {
                $this->uploadError = 'Tidak ada baris data yang valid. Periksa format kolom.';
                return;
            }

            $this->showPreview = true;
            // Store full rows count info
            session(['_fa_upload_rows' => $rows, '_fa_upload_path' => $path, '_fa_upload_ext' => $ext]);

        } catch (\Exception $e) {
            $this->uploadError = 'Gagal membaca file: ' . $e->getMessage();
        }
    }

    public function confirmImport(): void
    {
        $rows = session('_fa_upload_rows', []);
        if (empty($rows)) {
            $this->uploadError = 'Sesi upload habis. Silakan pilih file ulang.';
            return;
        }

        $batchId    = 'BATCH-' . now()->format('YmdHis') . '-' . auth()->id();
        $uploadedBy = auth()->user()->name;
        $imported   = 0;
        $skipped    = 0;

        DB::transaction(function () use ($rows, $batchId, $uploadedBy, &$imported, &$skipped) {
            foreach ($rows as $row) {
                $data = Invoice::rowToData($row, $uploadedBy, $batchId);
                if (!$data) { $skipped++; continue; }

                // Skip duplicates
                if (Invoice::where('no_invoice', $data['no_invoice'])->exists()) {
                    $skipped++;
                    continue;
                }

                Invoice::create($data);
                $imported++;
            }
        });

        $this->importedCount = $imported;
        $this->skippedCount  = $skipped;
        $this->importMsg     = "Import selesai: {$imported} record diimport, {$skipped} dilewati (duplikat/invalid).";
        $this->showPreview   = false;
        $this->showUploadPanel = false;
        session()->forget(['_fa_upload_rows', '_fa_upload_path', '_fa_upload_ext']);
        $this->resetPage();
    }

    // ── Mark paid ─────────────────────────────────────────
    public function markLunas(int $id): void
    {
        Invoice::findOrFail($id)->update([
            'status_bayar' => 'Lunas',
            'tgl_bayar'    => now(),
            'paid_by'      => auth()->user()->name,
        ]);
    }

    public function markBelumLunas(int $id): void
    {
        Invoice::findOrFail($id)->update([
            'status_bayar' => 'Belum Lunas',
            'tgl_bayar'    => null,
            'paid_by'      => null,
        ]);
    }

    public function konfirmasiLunasBuktiBayar(int $id): void
    {
        Invoice::findOrFail($id)->update([
            'status_bayar' => 'Lunas',
            'tgl_bayar'    => now(),
            'paid_by'      => auth()->user()->name . ' (via bukti bayar tenant)',
        ]);
        $this->detailId = null;
    }

    public function openDetail(int $id): void
    {
        $this->detailId = $id;
    }

    public function closeDetail(): void
    {
        $this->detailId = null;
    }

    // ── Template download ─────────────────────────────────
    public function downloadTemplate()
    {
        return redirect()->route('karyawan.fa.invoice-template');
    }

    // ── Import Meter (simple format) ─────────────────────
    public bool   $showMeterPanel  = false;
    public        $meterFile       = null;
    public int    $miBulan         = 0;
    public int    $miTahun         = 0;
    public string $miTarifListrik  = '1352';
    public string $miPjuPersen     = '2.4';
    public string $miIplAmount     = '0';
    public string $miBebanTetap    = '0';
    public array  $miRows          = [];
    public bool   $miParsed        = false;
    public string $miError         = '';

    public function openMeterImport(): void
    {
        $this->showMeterPanel = true;
        $this->meterFile      = null;
        $this->miRows         = [];
        $this->miParsed       = false;
        $this->miError        = '';
        $this->miBulan        = now()->month;
        $this->miTahun        = now()->year;
    }

    public function closeMeterPanel(): void
    {
        $this->showMeterPanel = false;
        $this->miRows         = [];
        $this->miParsed       = false;
        $this->miError        = '';
    }

    public function parseMeterFile(): void
    {
        $this->validate(['meterFile' => 'required|file|mimes:csv,txt|max:20480'],
            ['meterFile.required' => 'Pilih file terlebih dahulu.']);

        $this->miError  = '';
        $this->miParsed = false;
        $this->miRows   = [];

        $path = $this->meterFile->getRealPath();
        $ext  = strtolower($this->meterFile->getClientOriginalExtension());

        // Deteksi format UTI/ICMS: tanpa header, kolom dipisah spasi, baris dimulai dengan E atau W
        $lines      = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $firstToken = strtoupper(preg_split('/\s+/', trim($lines[0] ?? ''))[0] ?? '');
        $isUti      = in_array($firstToken, ['E', 'W']);

        if ($isUti) {
            // Format UTI: "E  MP/19/AC  20/05/2026  100" (spasi, tanpa header)
            $rawRows = [];
            foreach ($lines as $line) {
                $line  = trim($line);
                $parts = preg_split('/\s+/', $line, 4);
                if (count($parts) >= 3 && in_array(strtoupper($parts[0]), ['E', 'W'])) {
                    $rawRows[] = [
                        'type'      => strtoupper($parts[0]),
                        'lot_no'    => strtoupper($parts[1]),
                        'read_date' => $parts[2] ?? '',
                        'curr_read' => isset($parts[3]) ? (float) $parts[3] : 0,
                    ];
                }
            }
        } else {
            // Format CSV/TXT dengan header
            $rawRows = $this->readFile($path, $ext);
        }

        if (empty($rawRows)) { $this->miError = 'File kosong atau format tidak dikenali.'; return; }

        // Normalize header names (case-insensitive, spaces/underscores)
        $norm = function (array $row): array {
            $out = [];
            foreach ($row as $k => $v) {
                $key = strtolower(str_replace([' ', '_', '-'], '', $k));
                $out[$key] = $v;
            }
            return [
                'type'      => strtoupper(trim($out['type'] ?? '')),
                'lot_no'    => strtoupper(trim($out['lotno'] ?? $out['lot'] ?? '')),
                'curr_read' => (float) ($out['currread'] ?? $out['curr'] ?? 0),
                'read_date' => trim($out['readdate'] ?? $out['read'] ?? ''),
            ];
        };

        // Group E/W by LOT_NO
        $grouped = [];
        $readDate = '';
        foreach ($rawRows as $row) {
            $r = $norm($row);
            $lot = $r['lot_no'];
            if (!$lot) continue;
            if (!$readDate) $readDate = $r['read_date'];
            if (!isset($grouped[$lot])) $grouped[$lot] = ['E' => null, 'W' => null, 'read_date' => $r['read_date']];
            if ($r['type'] === 'E') $grouped[$lot]['E'] = $r['curr_read'];
            if ($r['type'] === 'W') $grouped[$lot]['W'] = $r['curr_read'];
        }

        $globalTarif = (float) $this->miTarifListrik;
        $beban       = (float) $this->miBebanTetap;
        $ipl         = (float) $this->miIplAmount;
        $pjuPersen   = (float) $this->miPjuPersen;

        foreach ($grouped as $lotNo => $data) {
            $lastInv = Invoice::where('debtor_acct', $lotNo)->orderByDesc('inv_date')->first();
            $handover = \App\Models\HandoverUnit::where('lot_no', $lotNo)->first();

            // Skip unit yang billing-nya dinonaktifkan (meteran dicabut)
            if ($handover && $handover->billing_aktif === false) {
                continue;
            }

            // Tarif per unit: lookup daya dari HandoverUnit → tarif PLN otomatis
            // Fallback: kwh_tariff dari invoice terakhir → global input
            $daya      = $handover?->daya_listrik ?? null;
            $dayaVa    = $this->normalizeDayaToVa($daya);
            $unitTarif = $dayaVa
                ? $this->tarifPLN($dayaVa)
                : ((float) ($lastInv?->kwh_tariff ?? 0) ?: $globalTarif);

            $prevKwh = (float) ($lastInv?->kwh_curr ?? 0);
            $prevM3  = (float) ($lastInv?->meter_curr ?? 0);
            $currKwh = $data['E'];
            $currM3  = $data['W'];

            $kwhUsed = $currKwh !== null ? max(0, $currKwh - $prevKwh) : null;
            $listrik  = $kwhUsed !== null ? round($kwhUsed * $unitTarif, 2) : 0;

            // PJU dihitung per unit: persentase dari biaya listrik
            $pju = $listrik > 0 ? round($listrik * $pjuPersen / 100, 2) : 0;

            $meterM3    = $currM3 !== null ? max(0, $currM3 - $prevM3) : null;
            $airAmount  = 0;
            if ($meterM3 !== null) {
                $t1 = min($meterM3, 10);
                $t2 = min(max($meterM3 - 10, 0), 10);
                $t3 = max($meterM3 - 20, 0);
                $airAmount = round($t1 * 12550 + $t2 * 17500 + $t3 * 21500 + $beban, 2);
            }

            $total = round($ipl + $listrik + $pju + $airAmount, 2);

            // Parse read_date
            $invDate = null;
            foreach (['d/m/Y H:i:s', 'd/m/Y', 'Y-m-d', 'd-m-Y'] as $fmt) {
                try {
                    $d = \Carbon\Carbon::createFromFormat($fmt, trim($data['read_date']));
                    if ($d) { $invDate = $d->format('Y-m-d'); break; }
                } catch (\Exception) {}
            }

            $exists = Invoice::where('debtor_acct', $lotNo)
                ->where('bulan', $this->miBulan)->where('tahun', $this->miTahun)->exists();

            $tenantName = $lastInv?->debtor_name
                ?: (\App\Models\Tenant::where('unit_number', $lotNo)->with('user')->first()?->user?->name ?? '');

            $this->miRows[] = [
                'debtor_acct' => $lotNo,
                'debtor_name' => $tenantName,
                'handphone'   => $lastInv?->handphone ?? '',
                'email'       => $lastInv?->email ?? '',
                'va'          => $lastInv?->virtual_account ?? '',
                'inv_date'    => $invDate,
                'prev_kwh'    => $currKwh !== null ? $prevKwh : null,
                'curr_kwh'    => $currKwh,
                'kwh_used'    => $kwhUsed,
                'unit_tarif'  => $unitTarif,
                'daya_va'     => $dayaVa,
                'listrik'     => $listrik,
                'prev_m3'     => $currM3 !== null ? $prevM3 : null,
                'curr_m3'     => $currM3,
                'm3_used'     => $meterM3,
                'air'         => $airAmount,
                'ipl'         => $ipl,
                'pju'         => $pju,
                'total'       => $total,
                'exists'      => $exists,
            ];
        }

        if (empty($this->miRows)) { $this->miError = 'Tidak ada baris data yang valid.'; return; }
        $this->miParsed = true;
    }

    public function confirmMeterImport(): void
    {
        $bulan   = $this->miBulan ?: now()->month;
        $tahun   = $this->miTahun ?: now()->year;
        $batchId = 'METER-' . now()->format('YmdHis');
        $created = 0; $skipped = 0;

        foreach ($this->miRows as $row) {
            if ($row['exists']) { $skipped++; continue; }

            $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
            $noInv = 'INV/' . $row['debtor_acct'] . '/' . $roman[$bulan - 1] . '/' . $tahun;

            Invoice::create([
                'no_invoice'      => $noInv,
                'inv_date'        => $row['inv_date'] ?? now()->format('Y-m-d'),
                'jatuh_tempo'     => $row['inv_date']
                    ? \Carbon\Carbon::parse($row['inv_date'])->addDays(14)->format('Y-m-d')
                    : null,
                'bulan'           => $bulan,
                'tahun'           => $tahun,
                'debtor_acct'     => $row['debtor_acct'],
                'debtor_name'     => $row['debtor_name'],
                'kategori'        => 'IPL+Listrik+Air',
                'ipl_amount'      => $row['ipl'],
                'kwh_prev'        => $row['prev_kwh'],
                'kwh_curr'        => $row['curr_kwh'],
                'kwh_used'        => $row['kwh_used'],
                'kwh_tariff'      => $row['unit_tarif'] ?: null,
                'daya_terpasang'  => $row['daya_va'] ? number_format($row['daya_va'] / 1000, 2) : null,
                'listrik_amount'  => $row['listrik'],
                'pju_amount'      => $row['pju'] ?: null,
                'beban_tetap'     => null,
                'meter_prev'      => $row['prev_m3'],
                'meter_curr'      => $row['curr_m3'],
                'meter_m3'        => $row['m3_used'],
                'water_tariff'    => null,
                'air_amount'      => $row['air'],
                'denda'           => 0,
                'other_charges'   => 0,
                'amount'          => $row['total'],
                'handphone'       => $row['handphone'],
                'email'           => $row['email'],
                'virtual_account' => $row['va'],
                'status_bayar'    => 'Belum Lunas',
                'uploaded_by'     => auth()->user()->name,
                'upload_batch'    => $batchId,
            ]);
            $created++;
        }

        $this->showMeterPanel = false;
        $this->miRows         = [];
        $this->miParsed       = false;
        $this->importMsg      = "{$created} invoice dibuat" . ($skipped ? ", {$skipped} dilewati (sudah ada)." : ".");
    }

    // ── Edit Invoice ──────────────────────────────────────
    public bool   $showEditModal   = false;
    public ?int   $editingId       = null;
    public string $eNoInvoice      = '';
    public string $eInvDate        = '';
    public string $eJatuhTempo     = '';
    public string $eDebtorName     = '';
    public string $eKategori       = '';
    public string $eDescription    = '';
    public string $eHandphone      = '';
    public string $eEmail          = '';
    public string $eVa             = '';
    public string $eIplAmount      = '';
    public string $eKwhPrev        = '';
    public string $eKwhCurr        = '';
    public string $eKwhTariff      = '';
    public string $ePjuAmount      = '';
    public string $eBiayaTambahan  = '';
    public string $eMeterPrev      = '';
    public string $eMeterCurr      = '';
    public string $eWaterTariff    = '';
    public string $eBebanTetap     = '';
    public string $eDenda          = '';
    public string $eOtherCharges   = '';
    public string $eStatusBayar    = 'Belum Lunas';
    public string $eTglBayar       = '';
    public string $ePaidBy         = '';

    public function openEditInvoice(int $id): void
    {
        $inv = Invoice::findOrFail($id);
        $this->editingId      = $id;
        $this->eNoInvoice     = $inv->no_invoice ?? '';
        $this->eInvDate       = $inv->inv_date?->format('Y-m-d') ?? '';
        $this->eJatuhTempo    = $inv->jatuh_tempo?->format('Y-m-d') ?? '';
        $this->eDebtorName    = $inv->debtor_name ?? '';
        $this->eKategori      = $inv->kategori ?? '';
        $this->eDescription   = $inv->description ?? '';
        $this->eHandphone     = $inv->handphone ?? '';
        $this->eEmail         = $inv->email ?? '';
        $this->eVa            = $inv->virtual_account ?? '';
        $this->eIplAmount     = (string) ($inv->ipl_amount ?? 0);
        $this->eKwhPrev       = (string) ($inv->kwh_prev ?? '');
        $this->eKwhCurr       = (string) ($inv->kwh_curr ?? '');
        $this->eKwhTariff     = (string) ($inv->kwh_tariff ?? '');
        $this->ePjuAmount     = (string) ($inv->pju_amount ?? '');
        $this->eBiayaTambahan = (string) ($inv->biaya_tambahan ?? '');
        $this->eMeterPrev     = (string) ($inv->meter_prev ?? '');
        $this->eMeterCurr     = (string) ($inv->meter_curr ?? '');
        $this->eWaterTariff   = (string) ($inv->water_tariff ?? '');
        $this->eBebanTetap    = (string) ($inv->beban_tetap ?? '');
        $this->eDenda         = (string) ($inv->denda ?? 0);
        $this->eOtherCharges  = (string) ($inv->other_charges ?? 0);
        $this->eStatusBayar   = $inv->status_bayar ?? 'Belum Lunas';
        $this->eTglBayar      = $inv->tgl_bayar?->format('Y-m-d') ?? '';
        $this->ePaidBy        = $inv->paid_by ?? '';
        $this->showEditModal  = true;
    }

    public function saveEditInvoice(): void
    {
        if (!$this->editingId) return;

        $kwhUsed = max(0, (float) $this->eKwhCurr - (float) $this->eKwhPrev);
        $listrik  = round($kwhUsed * (float) $this->eKwhTariff, 2);
        $meterM3  = max(0, (float) $this->eMeterCurr - (float) $this->eMeterPrev);
        $air      = round($meterM3 * (float) $this->eWaterTariff + (float) $this->eBebanTetap, 2);
        $total    = round(
            (float) $this->eIplAmount + $listrik + (float) $this->ePjuAmount
            + (float) $this->eBiayaTambahan + $air + (float) $this->eDenda + (float) $this->eOtherCharges,
            2
        );

        Invoice::findOrFail($this->editingId)->update([
            'no_invoice'      => $this->eNoInvoice,
            'inv_date'        => $this->eInvDate       ?: null,
            'jatuh_tempo'     => $this->eJatuhTempo    ?: null,
            'debtor_name'     => $this->eDebtorName,
            'kategori'        => $this->eKategori,
            'description'     => $this->eDescription,
            'handphone'       => $this->eHandphone,
            'email'           => $this->eEmail,
            'virtual_account' => $this->eVa,
            'ipl_amount'      => (float) $this->eIplAmount,
            'kwh_prev'        => $this->eKwhPrev !== '' ? (float) $this->eKwhPrev : null,
            'kwh_curr'        => $this->eKwhCurr !== '' ? (float) $this->eKwhCurr : null,
            'kwh_used'        => $kwhUsed ?: null,
            'kwh_tariff'      => $this->eKwhTariff !== '' ? (float) $this->eKwhTariff : null,
            'listrik_amount'  => $listrik,
            'pju_amount'      => $this->ePjuAmount !== '' ? (float) $this->ePjuAmount : null,
            'biaya_tambahan'  => $this->eBiayaTambahan !== '' ? (float) $this->eBiayaTambahan : null,
            'meter_prev'      => $this->eMeterPrev !== '' ? (float) $this->eMeterPrev : null,
            'meter_curr'      => $this->eMeterCurr !== '' ? (float) $this->eMeterCurr : null,
            'meter_m3'        => $meterM3 ?: null,
            'water_tariff'    => $this->eWaterTariff !== '' ? (float) $this->eWaterTariff : null,
            'air_amount'      => $air,
            'beban_tetap'     => $this->eBebanTetap !== '' ? (float) $this->eBebanTetap : null,
            'denda'           => (float) $this->eDenda,
            'other_charges'   => (float) $this->eOtherCharges,
            'amount'          => $total,
            'status_bayar'    => $this->eStatusBayar,
            'tgl_bayar'       => $this->eTglBayar ?: null,
            'paid_by'         => $this->ePaidBy   ?: null,
        ]);

        $this->showEditModal = false;
        $this->editingId     = null;
        $this->importMsg     = 'Invoice berhasil diperbarui.';
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingId     = null;
    }

    // ── Helpers ───────────────────────────────────────────
    // Konversi string daya (misal "2200", "2.2", "1300VA") ke nilai VA
    private function normalizeDayaToVa(?string $daya): ?int
    {
        if (!$daya) return null;
        $num = (float) preg_replace('/[^0-9.]/', '', $daya);
        if ($num <= 0) return null;
        // Jika < 100 → anggap KVA (misal "2.2" = 2200 VA)
        return (int) round($num < 100 ? $num * 1000 : $num);
    }

    // Tarif PLN 2024 non-subsidi berdasarkan daya (VA)
    private function tarifPLN(int $va): float
    {
        return match(true) {
            $va <= 900  => 1352.00,
            $va <= 2200 => 1444.70,
            default     => 1699.53,
        };
    }

    private function readFile(string $path, string $ext): array
    {
        $rows = [];
        if ($ext === 'csv') {
            $handle = fopen($path, 'r');
            // Strip UTF-8 BOM
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);
            $headers = fgetcsv($handle);
            if (!$headers) { fclose($handle); return []; }
            $headers = array_map('trim', $headers);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= count($headers)) {
                    $rows[] = array_combine($headers, array_map('trim', $row));
                }
            }
            fclose($handle);
        } else {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (empty($lines)) return [];
            $first  = $lines[0];
            $delim  = str_contains($first, "\t") ? "\t" : (str_contains($first, '|') ? '|' : ',');
            $headers = array_map('trim', str_getcsv($first, $delim));
            for ($i = 1; $i < count($lines); $i++) {
                $cols = array_map('trim', str_getcsv($lines[$i], $delim));
                if (count($cols) >= count($headers)) {
                    $rows[] = array_combine($headers, $cols);
                }
            }
        }
        return $rows;
    }

    public function with(): array
    {
        $q = Invoice::query()
            ->when($this->fBulan > 0,    fn($q) => $q->where('bulan', $this->fBulan))
            ->when($this->fTahun > 0,    fn($q) => $q->where('tahun', $this->fTahun))
            ->when($this->fKategori,     fn($q) => $q->where('kategori', $this->fKategori))
            ->when($this->fDebtorAcct,   fn($q) => $q->where('debtor_acct', 'like', "%{$this->fDebtorAcct}%"))
            ->when($this->fDebtorName,   fn($q) => $q->where('debtor_name', 'like', "%{$this->fDebtorName}%"))
            ->when($this->fStatus,       fn($q) => $q->where('status_bayar', $this->fStatus))
            ->when($this->fBuktiBayar,   fn($q) => $q->whereNotNull('bukti_bayar')->where('status_bayar', '!=', 'Lunas'))
            ->orderBy('debtor_acct');

        $invoices = $q->paginate(10);
        $totalAmount = (clone $q)->sum('amount');

        // Inject nama pemilik dari Tenant → User untuk invoice yang debtor_name-nya kosong
        $emptyAccts = $invoices->getCollection()->filter(fn($inv) => !$inv->debtor_name)->pluck('debtor_acct');
        if ($emptyAccts->isNotEmpty()) {
            $tenantNames = \App\Models\Tenant::with('user')
                ->whereIn('unit_number', $emptyAccts)
                ->get()->keyBy('unit_number')
                ->map(fn($t) => $t->user?->name ?? '');
            $invoices->getCollection()->transform(function ($inv) use ($tenantNames) {
                if (!$inv->debtor_name) {
                    $inv->debtor_name = $tenantNames->get($inv->debtor_acct, '');
                }
                return $inv;
            });
        }

        $detail = $this->detailId ? Invoice::find($this->detailId) : null;
        if ($detail && !$detail->debtor_name) {
            $t = \App\Models\Tenant::where('unit_number', $detail->debtor_acct)->with('user')->first();
            $detail->debtor_name = $t?->user?->name ?? '';
        }

        $tahunOptions = range(now()->year, now()->year - 4);

        $dept  = optional(auth()->user()->karyawan)->departemen ?? '';
        $isFin = in_array($dept, ['FA', 'AM']);

        $pendingBuktiBayar = Invoice::whereNotNull('bukti_bayar')
            ->where('status_bayar', '!=', 'Lunas')
            ->count();

        return compact('invoices', 'totalAmount', 'detail', 'tahunOptions', 'isFin', 'pendingBuktiBayar');
    }
}
?>

<div>
    {{-- ── Page Header ── --}}
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mx-5 mt-3 mb-3">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <div class="flex items-center gap-3">
                <span>INVOICES</span>
                @if($importMsg)
                <span class="text-xs font-normal bg-white/20 px-2 py-0.5 rounded">{{ $importMsg }}</span>
                @endif
            </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('karyawan.debtor') }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;font-size:11px;color:#4b5563;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;text-decoration:none;white-space:nowrap;">
                <svg style="width:13px;height:13px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Statement of Account
            </a>
            @if($isFin)
            <a href="{{ route('karyawan.fa.invoice-template') }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;font-size:11px;color:#4b5563;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;text-decoration:none;white-space:nowrap;">
                <svg style="width:13px;height:13px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Template CSV
            </a>
            <button wire:click="openUpload"
                    style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;font-size:11px;color:#fff;background:#2563eb;border:1px solid #2563eb;border-radius:4px;font-weight:600;white-space:nowrap;cursor:pointer;">
                <svg style="width:13px;height:13px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                </svg>
                Upload Invoice
            </button>
            <button wire:click="openMeterImport"
                    style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;font-size:11px;color:#fff;background:#16a34a;border:1px solid #16a34a;border-radius:4px;font-weight:600;white-space:nowrap;cursor:pointer;">
                <svg style="width:13px;height:13px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                </svg>
                Import Meter
            </button>
            @endif
        </div>
    </div>
    </div>

    {{-- ── Filter Bar ── --}}
    <div class="px-5 py-2.5 border-b border-gray-200 bg-[#f5faf5] flex items-center gap-3 flex-wrap text-xs">
        <div class="flex items-center gap-1.5">
            <label class="text-gray-600 font-medium">Bulan</label>
            <select wire:model.live="fBulan"
                    class="border border-gray-300 rounded px-2 py-1 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                <option value="0">Semua</option>
                @foreach(\App\Models\Invoice::bulanOptions() as $num => $name)
                <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-1.5">
            <label class="text-gray-600 font-medium">Tahun</label>
            <select wire:model.live="fTahun"
                    class="border border-gray-300 rounded px-2 py-1 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                <option value="0">Semua</option>
                @foreach($tahunOptions as $y)
                <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-1.5">
            <label class="text-gray-600 font-medium">Pilih Kategori</label>
            <select wire:model.live="fKategori"
                    class="border border-gray-300 rounded px-2 py-1 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                <option value="">Semua</option>
                @foreach(\App\Models\Invoice::kategoriOptions() as $opt)
                <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-1.5">
            <label class="text-gray-600 font-medium">Status</label>
            <select wire:model.live="fStatus"
                    class="border border-gray-300 rounded px-2 py-1 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                <option value="">Semua</option>
                <option value="Belum Lunas">Belum Lunas</option>
                <option value="Lunas">Lunas</option>
            </select>
        </div>
        <button wire:click="$toggle('fBuktiBayar')"
                class="flex items-center gap-1.5 px-3 py-1 rounded text-[10px] font-semibold border transition-colors"
                style="{{ $fBuktiBayar ? 'background:#f59e0b; color:#fff; border-color:#f59e0b;' : 'background:#fff; color:#b45309; border-color:#fcd34d;' }}">
            Bukti Bayar Pending
            @if($pendingBuktiBayar > 0)
            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full text-[9px] font-bold"
                  style="{{ $fBuktiBayar ? 'background:#fff; color:#d97706;' : 'background:#f59e0b; color:#fff;' }}">
                {{ $pendingBuktiBayar }}
            </span>
            @endif
        </button>
        <div class="ml-auto flex items-center gap-2 text-[11px] text-gray-500">
            <span>{{ $invoices->total() }} record</span>
            <span>·</span>
            <span>Total: <strong class="text-gray-700">Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong></span>
        </div>
    </div>

    {{-- ── Table ── --}}
    <div class="overflow-x-auto">
        <table class="min-w-full text-[11px] border-collapse">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-2 py-1.5 w-6 text-center"></th>
                    <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold tracking-wide whitespace-nowrap">DEBTOR ACCT</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold tracking-wide whitespace-nowrap">DEBTOR NAME</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold tracking-wide whitespace-nowrap">INV. DATE</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold tracking-wide whitespace-nowrap">NO. INVOICE</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-right font-semibold tracking-wide whitespace-nowrap">AMOUNT</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold tracking-wide">DESCRIPTION</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold tracking-wide whitespace-nowrap">HANDPHONE</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold tracking-wide">EMAIL</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold tracking-wide">VA</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-center font-semibold tracking-wide whitespace-nowrap">STATUS</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-center font-semibold tracking-wide">DETAIL</th>
                </tr>
                {{-- Per-column search --}}
                <tr class="bg-white">
                    <td class="border border-gray-200 px-1 py-0.5"></td>
                    <td class="border border-gray-200 px-1 py-0.5">
                        <input wire:model.live.debounce.400ms="fDebtorAcct" placeholder="Cari..."
                               class="w-full text-[10px] border-0 outline-none px-1 py-0.5">
                    </td>
                    <td class="border border-gray-200 px-1 py-0.5">
                        <input wire:model.live.debounce.400ms="fDebtorName" placeholder="Cari..."
                               class="w-full text-[10px] border-0 outline-none px-1 py-0.5">
                    </td>
                    <td class="border border-gray-200" colspan="9"></td>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $i => $inv)
                <tr class="border-b border-gray-200 hover:bg-blue-50 cursor-pointer transition-colors"
                    wire:click="openDetail({{ $inv->id }})">
                    <td class="border border-gray-200 px-2 py-1 text-center text-gray-400">
                        {{ $invoices->firstItem() + $i }}
                    </td>
                    <td class="border border-gray-200 px-3 py-1 font-mono text-[#1a6b9a] font-semibold">{{ $inv->debtor_acct }}</td>
                    <td class="border border-gray-200 px-3 py-1 text-gray-700">{{ $inv->debtor_name }}</td>
                    <td class="border border-gray-200 px-3 py-1 text-gray-600 whitespace-nowrap">{{ $inv->inv_date?->format('d/m/Y') }}</td>
                    <td class="border border-gray-200 px-3 py-1 font-mono text-gray-700 whitespace-nowrap">{{ $inv->no_invoice }}</td>
                    <td class="border border-gray-200 px-3 py-1 text-right font-semibold text-gray-800">
                        {{ number_format($inv->amount, 2, ',', '.') }}
                    </td>
                    <td class="border border-gray-200 px-3 py-1 text-gray-600 max-w-[160px] truncate">{{ $inv->description }}</td>
                    <td class="border border-gray-200 px-3 py-1 text-gray-600">{{ $inv->handphone }}</td>
                    <td class="border border-gray-200 px-3 py-1 text-gray-600 max-w-[120px] truncate">{{ $inv->email }}</td>
                    <td class="border border-gray-200 px-3 py-1 font-mono text-gray-600">{{ $inv->virtual_account }}</td>
                    <td class="border border-gray-200 px-3 py-1 text-center" wire:click.stop>
                        @if($inv->status_bayar === 'Lunas')
                        <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold bg-green-100 text-green-800 border border-green-200">
                            LUNAS
                        </span>
                        @else
                        <button wire:click="markLunas({{ $inv->id }})"
                                class="inline-block px-2 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-700 border border-red-200 hover:bg-red-200 transition-colors">
                            BELUM LUNAS
                        </button>
                        @endif
                    </td>
                    <td class="border border-gray-200 px-3 py-1 text-center">
                        <button wire:click="openDetail({{ $inv->id }})"
                                class="text-[#1a5c2e] hover:underline text-[10px] font-semibold">
                            Lihat
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="border border-gray-200 px-4 py-10 text-center text-gray-400 text-xs">
                        Tidak ada data invoice untuk filter yang dipilih.
                        @if(!Invoice::exists())
                        <br><span class="text-[11px]">Klik <strong>Upload Invoice</strong> untuk mengimpor data pertama.</span>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
            {{-- Total row --}}
            <tfoot>
                <tr class="bg-gray-100 font-semibold border-t-2 border-gray-300">
                    <td colspan="5" class="border border-gray-300 px-3 py-1.5 text-right text-xs text-gray-600">TOTAL</td>
                    <td class="border border-gray-300 px-3 py-1.5 text-right text-xs text-gray-800">
                        {{ number_format($totalAmount, 2, ',', '.') }}
                    </td>
                    <td colspan="6" class="border border-gray-300"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- ── Bottom Bar (pagination + summary) ── --}}
    @php
        $cur  = $invoices->currentPage();
        $last = $invoices->lastPage();
        $nums = collect();
        for ($p = 1; $p <= $last; $p++) {
            if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
        }
        $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
        $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
        $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
    @endphp
    <div class="flex items-center gap-2 px-3 py-1.5 border-t border-gray-300 bg-gray-50 text-[11px] text-gray-500"
         style="background-color: #f0f0f0;">
        {{-- Pagination controls --}}
        @if($invoices->onFirstPage())
            <span class="{{ $pDis }}">|‹</span>
            <span class="{{ $pDis }}">‹</span>
        @else
            <button wire:click="setPage(1)" class="{{ $pBtn }}">|‹</button>
            <button wire:click="previousPage" class="{{ $pBtn }}">‹</button>
        @endif
        @php $pg_prev = null; @endphp
        @foreach($nums as $pg)
            @if($pg_prev !== null && $pg - $pg_prev > 1)
                <span class="{{ $pDis }}">…</span>
            @endif
            @if($pg == $cur)
                <span class="{{ $pAct }}">{{ $pg }}</span>
            @else
                <button wire:click="setPage({{ $pg }})" class="{{ $pBtn }}">{{ $pg }}</button>
            @endif
            @php $pg_prev = $pg; @endphp
        @endforeach
        @if($invoices->hasMorePages())
            <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
            <button wire:click="setPage({{ $last }})" class="{{ $pBtn }}">›|</button>
        @else
            <span class="{{ $pDis }}">›</span>
            <span class="{{ $pDis }}">›|</span>
        @endif

        <div class="ml-4 flex items-center gap-1.5 text-[11px]">
            <span class="font-medium text-gray-700">
                {{ $invoices->firstItem() ?? 0 }}–{{ $invoices->lastItem() ?? 0 }}
            </span>
            <span>of</span>
            <span class="font-medium text-gray-700">{{ $invoices->total() }}</span>
        </div>

        <div class="ml-auto flex items-center gap-2">
            <select wire:model.live="perPage"
                    class="border border-gray-400 text-[11px] px-1 py-0.5 bg-white">
                @foreach([10, 25, 50, 100] as $pp)
                <option>{{ $pp }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- ══════════════ Upload Panel ══════════════ --}}
    @if($showUploadPanel)
    <div class="fixed inset-0 z-50 flex">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeUpload"></div>
        <div class="relative ml-auto w-full max-w-2xl bg-white h-full overflow-y-auto shadow-2xl flex flex-col">

            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0 z-10">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">Upload Invoice</h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">Import data invoice dari file CSV atau TXT</p>
                </div>
                <button wire:click="closeUpload" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-6 flex flex-col gap-4 flex-1">

                {{-- Format info --}}
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <p class="text-xs font-semibold text-blue-800 mb-2">Format Kolom (CSV / TXT)</p>
                    <div class="text-[10px] text-blue-700 font-mono leading-relaxed break-all">
                        NO_INVOICE, INV_DATE (DD/MM/YYYY), DEBTOR_ACCT, DEBTOR_NAME, KATEGORI, DESCRIPTION,
                        IPL_AMOUNT, KWH_PREV, KWH_CURR, DAYA_TERPASANG, KWH_TARIFF,
                        METER_PREV, METER_CURR, WATER_TARIFF, DENDA, OTHER_CHARGES,
                        HANDPHONE, EMAIL, VIRTUAL_ACCOUNT
                    </div>
                    <p class="text-[10px] text-blue-600 mt-2">
                        KWH_TARIFF berdasarkan daya terpasang. Contoh: 1300 VA → 1444.70, 2200 VA → 1444.70, 3500–5500 VA → 1699.53
                    </p>
                    <p class="text-[10px] text-blue-600">
                        LISTRIK = (KWH_CURR − KWH_PREV) × KWH_TARIFF &nbsp;|&nbsp;
                        AIR = (METER_CURR − METER_PREV) × WATER_TARIFF &nbsp;|&nbsp;
                        TOTAL = IPL + LISTRIK + AIR + DENDA + OTHER
                    </p>
                </div>

                {{-- File input --}}
                @if(!$showPreview)
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-2">
                        Pilih File <span class="text-red-500">*</span>
                        <span class="font-normal text-gray-400">(CSV atau TXT, maks 20 MB)</span>
                    </label>
                    <input type="file" wire:model="uploadFile" accept=".csv,.txt"
                           class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-[#e8f5e9] file:text-[#1a5c2e] file:text-xs file:font-semibold hover:file:bg-[#c8e6c9] file:cursor-pointer">

                    @if($uploadError)
                    <p class="text-xs text-red-600 mt-2 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ $uploadError }}</p>
                    @endif

                    <button wire:click="parseFile"
                            wire:loading.attr="disabled"
                            class="mt-4 w-full py-2.5 bg-[#1a5c2e] text-white text-sm font-semibold rounded-xl hover:bg-[#154d26] transition-colors disabled:opacity-50">
                        <span wire:loading.remove>Parse & Preview Data</span>
                        <span wire:loading>Memproses file...</span>
                    </button>
                </div>
                @endif

                {{-- Preview Table --}}
                @if($showPreview && !empty($previewRows))
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-gray-700">
                            Preview (10 baris pertama)
                        </p>
                        <button wire:click="$set('showPreview', false)" class="text-xs text-gray-400 hover:text-gray-600">
                            ← Pilih ulang
                        </button>
                    </div>

                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table class="min-w-full text-[10px]">
                            <thead class="bg-gray-100">
                                <tr>
                                    @foreach(['DEBTOR ACCT', 'DEBTOR NAME', 'KATEGORI', 'IPL', 'kWh', 'Listrik', 'm³', 'Air', 'DENDA', 'TOTAL'] as $h)
                                    <th class="px-2 py-1.5 text-left font-semibold text-gray-600 whitespace-nowrap">{{ $h }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previewRows as $prow)
                                @php
                                    $g = fn($k) => $prow[$k] ?? $prow[strtolower($k)] ?? $prow[strtoupper($k)] ?? '—';
                                @endphp
                                <tr class="border-t border-gray-100 hover:bg-gray-50">
                                    <td class="px-2 py-1 font-mono text-[#1a6b9a]">{{ $g('DEBTOR_ACCT') }}</td>
                                    <td class="px-2 py-1 text-gray-700">{{ $g('DEBTOR_NAME') }}</td>
                                    <td class="px-2 py-1 text-gray-600">{{ $g('KATEGORI') }}</td>
                                    <td class="px-2 py-1 text-right text-gray-700">{{ number_format((float)str_replace(',','',$g('IPL_AMOUNT')), 0, ',', '.') }}</td>
                                    <td class="px-2 py-1 text-right text-blue-700">{{ $prow['_kwh_used'] ?? '0' }}</td>
                                    <td class="px-2 py-1 text-right text-blue-700">{{ $prow['_listrik'] ?? '0' }}</td>
                                    <td class="px-2 py-1 text-right text-cyan-700">{{ $prow['_meter_m3'] ?? '0' }}</td>
                                    <td class="px-2 py-1 text-right text-cyan-700">{{ $prow['_air'] ?? '0' }}</td>
                                    <td class="px-2 py-1 text-right text-red-600">{{ number_format((float)str_replace(',','',$g('DENDA')), 0, ',', '.') }}</td>
                                    <td class="px-2 py-1 text-right font-bold text-gray-800">{{ $prow['_total'] ?? '0' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-xl text-xs text-yellow-800">
                        <strong>Perhatian:</strong> Record dengan NO_INVOICE yang sudah ada di database akan dilewati (skip). Cek data sebelum konfirmasi.
                    </div>

                    <button wire:click="confirmImport"
                            wire:loading.attr="disabled"
                            class="mt-4 w-full py-3 bg-green-600 text-white text-sm font-bold rounded-xl hover:bg-green-700 transition-colors disabled:opacity-50">
                        <span wire:loading.remove>Konfirmasi Import</span>
                        <span wire:loading>Mengimport data...</span>
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════ Detail Panel ══════════════ --}}
    @if($detailId && $detail)
    <div class="fixed inset-0 z-50 flex">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeDetail"></div>
        <div class="relative ml-auto w-full max-w-lg bg-white h-full overflow-y-auto shadow-2xl">

            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">Detail Invoice</h3>
                    <p class="text-[11px] font-mono text-[#1a6b9a] mt-0.5">{{ $detail->no_invoice }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('karyawan.fa.invoice-pdf', $detail->id) }}" target="_blank"
                       class="flex items-center gap-1 px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-semibold rounded transition-colors">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        PDF
                    </a>
                    @if($isFin)
                    <button wire:click="openEditInvoice({{ $detail->id }})"
                            class="flex items-center gap-1 px-2.5 py-1.5 text-[10px] font-semibold rounded transition-colors"
                            style="background:#f59e0b; color:#fff;">
                        <svg style="width:11px;height:11px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </button>
                    @endif
                    <button wire:click="closeDetail" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-5">
                {{-- Status --}}
                <div class="flex items-center justify-between">
                    <span class="inline-block px-3 py-1.5 rounded-full text-xs font-bold border
                        {{ $detail->status_bayar === 'Lunas' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-700 border-red-200' }}">
                        {{ $detail->status_bayar }}
                    </span>
                    @if($detail->status_bayar === 'Lunas')
                    <button wire:click="markBelumLunas({{ $detail->id }}); closeDetail()"
                            class="text-[11px] text-gray-400 hover:text-red-500">Batalkan Lunas</button>
                    @else
                    <button wire:click="markLunas({{ $detail->id }}); closeDetail()"
                            class="px-3 py-1 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700">
                        Tandai Lunas
                    </button>
                    @endif
                </div>

                {{-- Basic Info --}}
                <div class="grid grid-cols-2 gap-x-6 gap-y-3">
                    @foreach([
                        ['Unit',         $detail->debtor_acct],
                        ['Nama',         $detail->debtor_name],
                        ['Tanggal',      $detail->inv_date?->format('d/m/Y')],
                        ['Kategori',     $detail->kategori],
                        ['Handphone',    $detail->handphone ?: '—'],
                        ['Email',        $detail->email ?: '—'],
                        ['VA',           $detail->virtual_account ?: '—'],
                    ] as [$label, $val])
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-xs font-medium text-gray-800 mt-0.5">{{ $val }}</p>
                    </div>
                    @endforeach
                </div>

                @if($detail->description)
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Keterangan</p>
                    <p class="text-xs text-gray-700 bg-gray-50 rounded-xl p-3">{{ $detail->description }}</p>
                </div>
                @endif

                {{-- Rincian Biaya --}}
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold mb-3">Rincian Tagihan</p>
                    <div class="border border-gray-200 rounded-xl overflow-hidden">

                        {{-- IPL --}}
                        @if($detail->ipl_amount > 0)
                        <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                            <div>
                                <p class="text-xs font-semibold text-gray-700">Iuran Pengelolaan Lingkungan (IPL)</p>
                            </div>
                            <p class="text-sm font-bold text-gray-800">Rp {{ number_format($detail->ipl_amount, 0, ',', '.') }}</p>
                        </div>
                        @endif

                        {{-- Listrik --}}
                        @if($detail->listrik_amount > 0 || $detail->kwh_used)
                        <div class="px-4 py-2.5 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold text-gray-700">Listrik</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                        Meter: {{ $detail->kwh_prev }} → {{ $detail->kwh_curr }} kWh
                                        ({{ $detail->kwh_used }} kWh)
                                        @if($detail->daya_terpasang)
                                        · Daya {{ $detail->daya_terpasang }}
                                        @endif
                                        · Tarif Rp {{ number_format($detail->kwh_tariff, 2, ',', '.') }}/kWh
                                    </p>
                                    <p class="text-[10px] text-blue-600 mt-0.5 font-medium">
                                        {{ $detail->kwh_used }} kWh × Rp {{ number_format($detail->kwh_tariff, 2, ',', '.') }}
                                    </p>
                                </div>
                                <p class="text-sm font-bold text-blue-700">Rp {{ number_format($detail->listrik_amount, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        @endif

                        {{-- Air --}}
                        @if($detail->air_amount > 0 || $detail->meter_m3)
                        <div class="px-4 py-2.5 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold text-gray-700">Air</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                        Meter: {{ $detail->meter_prev }} → {{ $detail->meter_curr }} m³
                                        ({{ $detail->meter_m3 }} m³)
                                        · Tarif Rp {{ number_format($detail->water_tariff, 0, ',', '.') }}/m³
                                    </p>
                                    <p class="text-[10px] text-cyan-600 mt-0.5 font-medium">
                                        {{ $detail->meter_m3 }} m³ × Rp {{ number_format($detail->water_tariff, 0, ',', '.') }}
                                    </p>
                                </div>
                                <p class="text-sm font-bold text-cyan-700">Rp {{ number_format($detail->air_amount, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        @endif

                        {{-- Denda / Other --}}
                        @if($detail->denda > 0)
                        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100">
                            <p class="text-xs font-semibold text-red-600">Denda</p>
                            <p class="text-sm font-bold text-red-600">Rp {{ number_format($detail->denda, 0, ',', '.') }}</p>
                        </div>
                        @endif
                        @if($detail->other_charges > 0)
                        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100">
                            <p class="text-xs font-semibold text-gray-600">Lain-lain</p>
                            <p class="text-sm font-bold text-gray-600">Rp {{ number_format($detail->other_charges, 0, ',', '.') }}</p>
                        </div>
                        @endif

                        {{-- Total --}}
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-800">
                            <p class="text-sm font-bold text-white">TOTAL TAGIHAN</p>
                            <p class="text-base font-black text-white">Rp {{ number_format($detail->amount, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                @if($detail->tgl_bayar)
                <div class="text-xs text-green-700 bg-green-50 border border-green-200 rounded-xl px-3 py-2">
                    Dibayar pada {{ $detail->tgl_bayar->format('d/m/Y') }}
                    @if($detail->paid_by) oleh {{ $detail->paid_by }} @endif
                </div>
                @endif

                {{-- Bukti Bayar dari Tenant --}}
                @if($detail->bukti_bayar && $detail->status_bayar !== 'Lunas')
                <div class="border border-amber-200 rounded-xl p-4 bg-amber-50">
                    <p class="text-xs font-bold text-amber-800 mb-2">Bukti Pembayaran dari Tenant</p>
                    <img src="{{ asset('storage/' . $detail->bukti_bayar) }}"
                         alt="Bukti Bayar"
                         class="rounded-lg border border-amber-200 max-h-48 object-contain w-full mb-2">
                    <p class="text-[10px] text-amber-700">
                        Diunggah: {{ $detail->tgl_bukti_bayar?->format('d/m/Y H:i') }}
                    </p>
                    @if($isFin)
                    <div class="mt-3 flex gap-2">
                        <button wire:click="konfirmasiLunasBuktiBayar({{ $detail->id }})"
                                class="flex-1 py-2 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700 transition-colors">
                            ✓ Konfirmasi Lunas
                        </button>
                        <button wire:click="markBelumLunas({{ $detail->id }})"
                                class="px-3 py-2 border border-red-300 text-red-600 text-xs rounded-lg hover:bg-red-50">
                            Tolak
                        </button>
                    </div>
                    @endif
                </div>
                @elseif($detail->bukti_bayar && $detail->status_bayar === 'Lunas')
                <div class="text-[10px] text-gray-400 flex items-center gap-1.5">
                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Bukti bayar telah dikonfirmasi
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════ Import Meter Panel ══════════════ --}}
    @if($showMeterPanel)
    <div class="fixed inset-0 z-[60] flex items-center justify-center" style="background:rgba(0,0,0,0.5);">
        <div class="bg-white rounded-lg shadow-2xl w-full overflow-y-auto" style="max-width:780px; max-height:92vh;">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3 rounded-t-lg"
                 style="background:linear-gradient(135deg,#14532d,#16a34a,#22c55e); border-bottom:1px solid #15803d;">
                <span class="font-bold text-sm text-white uppercase tracking-wide">Import Meter (ICMS Format)</span>
                <button wire:click="closeMeterPanel" class="text-white/70 hover:text-white text-lg leading-none">✕</button>
            </div>

            <div class="p-5 space-y-4">

                {{-- Global settings --}}
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Bulan</label>
                        <select wire:model="miBulan" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs text-gray-700">
                            @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $bn)
                                <option value="{{ $i+1 }}">{{ $bn }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Tahun</label>
                        <input type="number" wire:model="miTahun" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs text-gray-700">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Tarif Listrik (Rp/kWh)</label>
                        <input type="number" wire:model="miTarifListrik" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs text-gray-700">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">IPL Amount (Rp)</label>
                        <input type="number" wire:model="miIplAmount" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs text-gray-700">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">PJU (% dari Listrik)</label>
                        <div class="relative">
                            <input type="number" step="0.01" wire:model="miPjuPersen"
                                   class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs text-gray-700 pr-7">
                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[11px] text-gray-400 pointer-events-none">%</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Beban Tetap Air (Rp)</label>
                        <input type="number" wire:model="miBebanTetap" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs text-gray-700">
                    </div>
                </div>

                {{-- File upload --}}
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">File CSV/TXT (Type, Lot No, Read Date, Curr Read)</label>
                    <div class="flex gap-2 items-center">
                        <input type="file" wire:model="meterFile" accept=".csv,.txt"
                               class="flex-1 text-xs border border-gray-200 rounded px-2 py-1.5 text-gray-700">
                        <button wire:click="parseMeterFile"
                                wire:loading.attr="disabled"
                                style="padding:6px 14px;font-size:11px;font-weight:600;color:#fff;background:#2563eb;border:none;border-radius:4px;cursor:pointer;white-space:nowrap;">
                            <span wire:loading.remove wire:target="parseMeterFile">Parse File</span>
                            <span wire:loading wire:target="parseMeterFile">Memproses...</span>
                        </button>
                    </div>
                    @error('meterFile')<p class="text-[10px] text-red-500 mt-1">{{ $message }}</p>@enderror
                    @if($miError)<p class="text-[10px] text-red-500 mt-1">{{ $miError }}</p>@endif
                </div>

                {{-- Preview table --}}
                @if($miParsed && count($miRows))
                <div>
                    <p class="text-[11px] font-semibold text-gray-600 mb-1">Preview — {{ count($miRows) }} unit</p>
                    <div class="overflow-x-auto rounded border border-gray-200">
                        <table class="w-full text-[10px] border-collapse">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600 uppercase">
                                    <th class="border border-gray-200 px-2 py-1.5 text-left font-semibold">Lot No</th>
                                    <th class="border border-gray-200 px-2 py-1.5 text-left font-semibold">Name</th>
                                    <th class="border border-gray-200 px-2 py-1.5 text-right font-semibold">Prev kWh</th>
                                    <th class="border border-gray-200 px-2 py-1.5 text-right font-semibold">Curr kWh</th>
                                    <th class="border border-gray-200 px-2 py-1.5 text-right font-semibold">Used</th>
                                    <th class="border border-gray-200 px-2 py-1.5 text-right font-semibold">Listrik</th>
                                    <th class="border border-gray-200 px-2 py-1.5 text-right font-semibold">Prev m³</th>
                                    <th class="border border-gray-200 px-2 py-1.5 text-right font-semibold">Curr m³</th>
                                    <th class="border border-gray-200 px-2 py-1.5 text-right font-semibold">Used</th>
                                    <th class="border border-gray-200 px-2 py-1.5 text-right font-semibold">Air</th>
                                    <th class="border border-gray-200 px-2 py-1.5 text-right font-semibold">Total</th>
                                    <th class="border border-gray-200 px-2 py-1.5 text-center font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($miRows as $r)
                                <tr class="{{ $r['exists'] ? 'bg-yellow-50' : 'hover:bg-gray-50' }}">
                                    <td class="border border-gray-200 px-2 py-1 font-mono">{{ $r['debtor_acct'] }}</td>
                                    <td class="border border-gray-200 px-2 py-1 max-w-[100px] truncate">{{ $r['debtor_name'] }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-right">{{ $r['prev_kwh'] !== null ? number_format($r['prev_kwh'],0) : '-' }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-right">{{ $r['curr_kwh'] !== null ? number_format($r['curr_kwh'],0) : '-' }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-right">{{ $r['kwh_used'] !== null ? number_format($r['kwh_used'],0) : '-' }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-right">{{ number_format($r['listrik'],0) }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-right">{{ $r['prev_m3'] !== null ? number_format($r['prev_m3'],1) : '-' }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-right">{{ $r['curr_m3'] !== null ? number_format($r['curr_m3'],1) : '-' }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-right">{{ $r['m3_used'] !== null ? number_format($r['m3_used'],1) : '-' }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-right">{{ number_format($r['air'],0) }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-right font-semibold">{{ number_format($r['total'],0) }}</td>
                                    <td class="border border-gray-200 px-2 py-1 text-center">
                                        @if($r['exists'])
                                            <span style="color:#b45309;font-size:9px;font-weight:600;">Sudah Ada</span>
                                        @else
                                            <span style="color:#15803d;font-size:9px;font-weight:600;">Baru</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-[10px] text-amber-600 mt-1">* Baris bertanda "Sudah Ada" akan dilewati saat konfirmasi.</p>
                </div>
                @endif

            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-2 px-5 py-3 border-t border-gray-100 bg-gray-50 rounded-b-lg">
                <button wire:click="closeMeterPanel"
                        class="px-8 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm rounded font-semibold">
                    Batal
                </button>
                @if($miParsed && count($miRows))
                <button wire:click="confirmMeterImport"
                        wire:loading.attr="disabled"
                        style="padding:6px 24px;font-size:13px;font-weight:700;color:#fff;background:#16a34a;border:none;border-radius:6px;cursor:pointer;">
                    <span wire:loading.remove wire:target="confirmMeterImport">Konfirmasi Import</span>
                    <span wire:loading wire:target="confirmMeterImport">Menyimpan...</span>
                </button>
                @endif
            </div>

        </div>
    </div>
    @endif

    {{-- ══════════════ Edit Invoice Modal ══════════════ --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-[60] flex items-center justify-center" style="background:rgba(0,0,0,0.5);">
        <div class="bg-white rounded-lg shadow-2xl w-full overflow-y-auto" style="max-width:600px; max-height:90vh;">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3 sticky top-0 z-10"
                 style="background:linear-gradient(135deg,#1e3a8a,#2563eb,#3b82f6); border-bottom:1px solid #1d4ed8;">
                <span class="font-bold text-sm text-white uppercase tracking-wide">Edit Invoice</span>
                <button wire:click="closeEditModal" class="text-white/70 hover:text-white text-lg leading-none">✕</button>
            </div>

            <div class="px-6 py-4 space-y-3" style="font-size:12px;">

                {{-- Identitas --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-0.5">No. Invoice</label>
                        <input wire:model="eNoInvoice" type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-0.5">Kategori</label>
                        <select wire:model="eKategori" class="w-full border border-gray-300 rounded px-2 py-1 text-xs bg-white focus:outline-none focus:border-blue-400">
                            @foreach(\App\Models\Invoice::kategoriOptions() as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-0.5">Inv. Date</label>
                        <input wire:model="eInvDate" type="date" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-0.5">Jatuh Tempo</label>
                        <input wire:model="eJatuhTempo" type="date" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-500 mb-0.5">Debtor Name</label>
                        <input wire:model="eDebtorName" type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-500 mb-0.5">Description</label>
                        <input wire:model="eDescription" type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                    </div>
                </div>

                {{-- Kontak --}}
                <div class="grid grid-cols-3 gap-3 pt-1 border-t border-gray-100">
                    <div>
                        <label class="block text-xs text-gray-500 mb-0.5">Handphone</label>
                        <input wire:model="eHandphone" type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-0.5">Email</label>
                        <input wire:model="eEmail" type="email" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-0.5">Virtual Account</label>
                        <input wire:model="eVa" type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                    </div>
                </div>

                {{-- Tagihan --}}
                <div class="pt-1 border-t border-gray-100">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase mb-2">Rincian Tagihan</p>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">IPL (Rp)</label>
                            <input wire:model="eIplAmount" type="number" step="0.01" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Meter Listrik Awal</label>
                            <input wire:model="eKwhPrev" type="number" step="0.001" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Meter Listrik Akhir</label>
                            <input wire:model="eKwhCurr" type="number" step="0.001" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Tarif Listrik (Rp/kWh)</label>
                            <input wire:model="eKwhTariff" type="number" step="0.01" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">PJU (Rp)</label>
                            <input wire:model="ePjuAmount" type="number" step="0.01" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Biaya Tambahan (Rp)</label>
                            <input wire:model="eBiayaTambahan" type="number" step="0.01" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Meter Air Awal</label>
                            <input wire:model="eMeterPrev" type="number" step="0.001" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Meter Air Akhir</label>
                            <input wire:model="eMeterCurr" type="number" step="0.001" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Tarif Air (Rp/m³)</label>
                            <input wire:model="eWaterTariff" type="number" step="0.01" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Beban Tetap Air (Rp)</label>
                            <input wire:model="eBebanTetap" type="number" step="0.01" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Denda (Rp)</label>
                            <input wire:model="eDenda" type="number" step="0.01" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Other Charges (Rp)</label>
                            <input wire:model="eOtherCharges" type="number" step="0.01" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                        </div>
                    </div>
                </div>

                {{-- Status Pembayaran --}}
                <div class="grid grid-cols-3 gap-3 pt-1 border-t border-gray-100">
                    <div>
                        <label class="block text-xs text-gray-500 mb-0.5">Status Bayar</label>
                        <select wire:model="eStatusBayar" class="w-full border border-gray-300 rounded px-2 py-1 text-xs bg-white focus:outline-none focus:border-blue-400">
                            <option value="Belum Lunas">Belum Lunas</option>
                            <option value="Lunas">Lunas</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-0.5">Tgl Bayar</label>
                        <input wire:model="eTglBayar" type="date" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-0.5">Paid By / No. Bukti</label>
                        <input wire:model="ePaidBy" type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-blue-400">
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex justify-center gap-4 pt-3 border-t border-gray-200">
                    <button wire:click="saveEditInvoice" wire:loading.attr="disabled"
                            class="px-8 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded font-semibold disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveEditInvoice">Simpan</span>
                        <span wire:loading wire:target="saveEditInvoice">Menyimpan...</span>
                    </button>
                    <button wire:click="closeEditModal"
                            class="px-8 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm rounded font-semibold">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

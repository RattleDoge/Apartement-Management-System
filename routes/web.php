<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('users', 'admin.users.index')->name('users.index');
    Route::view('settings', 'admin.settings')->name('settings');
});

// Tenant portal routes
Route::middleware(['auth', 'tenant'])->prefix('tenant')->name('tenant.')->group(function () {
    Volt::route('/',                    'tenant.dashboard')->name('dashboard');
    Volt::route('request',              'tenant.tenant-request')->name('request');
    Volt::route('in-out-permit',        'tenant.in-out-permit')->name('in-out-permit');
    Volt::route('facility-reservation', 'tenant.facility-reservation')->name('facility-reservation');
    Volt::route('cek-invoice',          'tenant.cek-invoice')->name('cek-invoice');
    Volt::route('profil-unit',          'tenant.profil-unit')->name('profil-unit');
    Volt::route('tracking-wo',          'tenant.tracking-wo')->name('tracking-wo');
    Volt::route('riwayat-bayar',        'tenant.riwayat-bayar')->name('riwayat-bayar');
    Volt::route('jadwal-fasilitas',     'tenant.jadwal-fasilitas')->name('jadwal-fasilitas');
    Volt::route('dokumen-penting',      'tenant.dokumen-penting')->name('dokumen-penting');
    Volt::route('faq',                  'tenant.faq')->name('faq');
});

// Notification routes
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Volt::route('/',          'notifications.index')->name('index');
    Route::post('mark-all-read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    })->name('mark-all-read');
    Route::post('{id}/read', function (string $id) {
        $notif = auth()->user()->notifications()->findOrFail($id);
        $notif->markAsRead();
        return back();
    })->name('read');
});

// Karyawan menu routes
Route::middleware(['auth'])->prefix('karyawan')->name('karyawan.')->group(function () {

    // ── AM & CS only ────────────────────────────────────────────────────────
    Route::middleware('dept:AM,CS')->group(function () {
        Volt::route('setup',          'karyawan.setup')->name('setup');
        Volt::route('table-staff',    'karyawan.table-staff')->name('table-staff');
        Volt::route('fasilitas',      'karyawan.fasilitas')->name('fasilitas');
        Volt::route('emergency',      'karyawan.emergency')->name('emergency');
        Volt::route('serah-terima',   'karyawan.serah-terima-unit')->name('serah-terima');
        Volt::route('checklist-unit', 'karyawan.checklist-unit')->name('checklist-unit');
        Route::get('checklist-unit/{id}/print', function (int $id) {
            $checklist = \App\Models\UnitChecklist::findOrFail($id);
            return view('karyawan.checklist-unit-print', compact('checklist'));
        })->name('checklist-unit.print');
        Volt::route('broadcast-pesan', 'karyawan.broadcast-pesan')->name('broadcast-pesan');
        Volt::route('kelola-faq',      'karyawan.kelola-faq')->name('kelola-faq');
        Volt::route('kelola-dokumen',  'karyawan.kelola-dokumen')->name('kelola-dokumen');
        Volt::route('laporan-bulanan', 'karyawan.laporan-bulanan')->name('laporan-bulanan');
        Volt::route('approval-center', 'karyawan.approval-center')->name('approval-center');
    });

    // ── AM, CS & FIN — Debtor / Statement of Account ────────────────────────
    Route::middleware('dept:AM,CS,FA')->group(function () {
        Volt::route('debtor', 'karyawan.debtor-list')->name('debtor');
        Route::get('debtor/{debtorAcct}/statement', function (string $debtorAcct) {
            $debtor = \App\Models\Invoice::select(
                    'debtor_acct',
                    \Illuminate\Support\Facades\DB::raw('MAX(debtor_name) as debtor_name'),
                    \Illuminate\Support\Facades\DB::raw('MAX(virtual_account) as virtual_account')
                )
                ->where('debtor_acct', $debtorAcct)
                ->groupBy('debtor_acct')
                ->first();

            if (!$debtor) abort(404);

            if (!$debtor->debtor_name) {
                $debtor->debtor_name = \App\Models\Tenant::where('unit_number', $debtorAcct)
                    ->with('user')->first()?->user?->name ?? '';
            }

            $invoices = \App\Models\Invoice::where('debtor_acct', $debtorAcct)
                ->orderBy('inv_date')
                ->get();

            return view('karyawan.statement-of-account', compact('debtor', 'invoices'));
        })->where('debtorAcct', '.+')->name('debtor.statement');

        Route::get('debtor/{debtorAcct}/statement/pdf', function (string $debtorAcct) {
            $debtor = \App\Models\Invoice::select(
                    'debtor_acct',
                    \Illuminate\Support\Facades\DB::raw('MAX(debtor_name) as debtor_name'),
                    \Illuminate\Support\Facades\DB::raw('MAX(virtual_account) as virtual_account')
                )
                ->where('debtor_acct', $debtorAcct)
                ->groupBy('debtor_acct')
                ->first();

            if (!$debtor) abort(404);

            if (!$debtor->debtor_name) {
                $debtor->debtor_name = \App\Models\Tenant::where('unit_number', $debtorAcct)
                    ->with('user')->first()?->user?->name ?? '';
            }

            $invoices = \App\Models\Invoice::where('debtor_acct', $debtorAcct)
                ->orderBy('inv_date')
                ->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('karyawan.statement-of-account', compact('debtor', 'invoices'))
                ->setPaper('a4', 'portrait');

            $safeAcct = str_replace(['/', '\\'], '-', $debtorAcct);
            return $pdf->download('SOA-' . $safeAcct . '-' . now()->format('Ymd') . '.pdf');
        })->where('debtorAcct', '.+')->name('debtor.statement.pdf');
    });

    // ── Laporan Bulanan PDF ───────────────────────────────────────────────────
    Route::middleware('dept:AM,CS,FA')->get('laporan-bulanan/pdf', function (\Illuminate\Http\Request $request) {
        $y = (int) ($request->query('tahun', now()->year));
        $m = (int) ($request->query('bulan', now()->month));

        $data = [
            'tahun'       => $y,
            'bulan'       => $m,
            'namaBulan'   => \Carbon\Carbon::create($y, $m, 1)->isoFormat('MMMM YYYY'),
            'woTotal'     => \App\Models\WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->count(),
            'woSelesai'   => \App\Models\WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->where('status_comp', 'Work Order Close')->count(),
            'woPending'   => \App\Models\WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->where('status_comp', '!=', 'Work Order Close')->count(),
            'woByJenis'   => \App\Models\WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->selectRaw('jenis_wo, COUNT(*) as total')->groupBy('jenis_wo')->orderByDesc('total')->get(),
            'trTotal'     => \App\Models\TenantRequest::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->count(),
            'trSelesai'   => \App\Models\TenantRequest::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->where('is_selesai', true)->count(),
            'trByKategori'=> \App\Models\TenantRequest::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->selectRaw('kategori, COUNT(*) as total')->groupBy('kategori')->orderByDesc('total')->get(),
            'invLunas'    => \App\Models\Invoice::where('status_bayar', 'Lunas')->whereYear('inv_date', $y)->whereMonth('inv_date', $m)->count(),
            'invBelum'    => \App\Models\Invoice::where('status_bayar', '!=', 'Lunas')->whereYear('inv_date', $y)->whereMonth('inv_date', $m)->count(),
            'invRevenue'  => \App\Models\Invoice::where('status_bayar', 'Lunas')->whereYear('tgl_bayar', $y)->whereMonth('tgl_bayar', $m)->sum('amount'),
            'avgRating'   => \App\Models\WoFeedback::avg('rating'),
            'totalUnit'   => \App\Models\HandoverUnit::count(),
            'woList'      => \App\Models\WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->orderBy('tanggal')->get(),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('karyawan.laporan-bulanan-pdf', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download('Laporan-' . \Carbon\Carbon::create($y, $m)->isoFormat('MMMM-YYYY') . '.pdf');
    })->name('laporan-bulanan.pdf');

    // ── Status Billing (AM & ENG) ─────────────────────────────────────────────
    Route::middleware('dept:AM,ENG')->group(function () {
        Volt::route('billing-status', 'karyawan.billing-status')->name('billing-status');
    });

    // ── ENG only ─────────────────────────────────────────────────────────────
    Route::middleware('dept:ENG')->group(function () {
        Volt::route('wo-saya',                'karyawan.wo-saya')->name('wo-saya');
        Volt::route('preventive-maintenance', 'karyawan.preventive-maintenance')->name('preventive-maintenance');
        Volt::route('laporan-harian',         'karyawan.laporan-harian')->name('laporan-harian');
    });

    // ── Customer Services — WO & Laporan (AM, CS, ENG, FA, HKP, SEC) ──────────
    Route::middleware('dept:AM,CS,ENG,FA,HKP,SEC')->prefix('cs')->name('cs.')->group(function () {
        Volt::route('work-order',              'karyawan.work-order')->name('work-order');
        Route::get('work-order/{id}/print',    function (int $id) {
            $wo = \App\Models\WorkOrder::findOrFail($id);
            return view('karyawan.work-order-print', compact('wo'));
        })->name('work-order.print');
        Route::get('work-order/{id}/pdf', function (int $id) {
            $wo = \App\Models\WorkOrder::findOrFail($id);
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('karyawan.work-order-pdf', compact('wo'))
                ->setPaper('a4', 'portrait');
            return $pdf->download('WO-' . $wo->no_wo . '.pdf');
        })->name('work-order.pdf');
        Volt::route('tenant-request-belum',    'karyawan.tenant-request-belum')->name('tenant-request-belum');
        Volt::route('tenant-request-selesai',  'karyawan.tenant-request-selesai')->name('tenant-request-selesai');
        Volt::route('work-order-close',        'karyawan.work-order-close')->name('work-order-close');
        Volt::route('work-order-report',       'karyawan.work-order-report')->name('work-order-report');
        Route::get('work-order-report/pdf', function (\Illuminate\Http\Request $request) {
            $from  = $request->query('from');
            $until = $request->query('until');
            $query = \App\Models\WorkOrder::query()->orderBy('tanggal');
            if ($from)  { $query->whereDate('tanggal', '>=', $from); }
            if ($until) { $query->whereDate('tanggal', '<=', $until); }
            $wos = $query->get();

            $periodeFrom  = $from  ? \Carbon\Carbon::parse($from)->isoFormat('D MMMM YYYY')  : null;
            $periodeUntil = $until ? \Carbon\Carbon::parse($until)->isoFormat('D MMMM YYYY') : null;

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('karyawan.work-order-report-pdf', [
                'wos'          => $wos,
                'periodeFrom'  => $periodeFrom,
                'periodeUntil' => $periodeUntil,
            ])->setPaper('a4', 'landscape');

            $filename = 'WO-Report-' . ($from ?? 'all') . '-to-' . ($until ?? 'all') . '.pdf';
            return $pdf->download($filename);
        })->name('work-order-report.pdf');
        Route::get('work-order-report/download', function (\Illuminate\Http\Request $request) {
            $from  = $request->query('from');
            $until = $request->query('until');
            $query = \App\Models\WorkOrder::query()->orderBy('tanggal');
            if ($from)  { $query->whereDate('tanggal', '>=', $from); }
            if ($until) { $query->whereDate('tanggal', '<=', $until); }
            $wos = $query->get();
            $filename = 'WO-Report-' . ($from ?? 'all') . '-to-' . ($until ?? 'all') . '.csv';
            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];
            $callback = function () use ($wos) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
                fputcsv($out, [
                    'NO', 'EX/IN', 'NO COMPLAIN', 'NO WO', 'JENIS WO', 'SUB JENIS WO',
                    'TANGGAL', 'ESTIMATED CLOSE', 'LOT NO', 'NAMA', 'DESKRIPSI',
                    'REQUEST BY', 'REQUEST VIA', 'STATUS', 'DURASI (mnt)', 'DURASI (bln)',
                    'ASSIGN DEP', 'ASSIGN STAFF', 'INPUT BY',
                    'BALAS REQUEST', 'BALAS BY', 'BALAS AT',
                    'WORK STARTED', 'WORK CLOSED', 'ACTION BY', 'ACTION TAKEN',
                ]);
                foreach ($wos as $i => $wo) {
                    fputcsv($out, [
                        $i + 1,
                        $wo->ex_in,
                        $wo->no_complain,
                        $wo->no_wo,
                        $wo->jenis_wo,
                        $wo->sub_jenis_wo,
                        $wo->tanggal?->format('d/m/Y H:i'),
                        $wo->estimated_close?->format('d/m/Y'),
                        $wo->lot_no,
                        $wo->name,
                        $wo->descs,
                        $wo->request_by,
                        $wo->request_via,
                        $wo->status_comp,
                        $wo->durasi,
                        $wo->durasi_bln,
                        $wo->assign_dep,
                        $wo->assign_staff,
                        $wo->input_by,
                        $wo->balas_request,
                        $wo->balas_by,
                        $wo->balas_at?->format('d/m/Y H:i'),
                        $wo->work_started?->format('d/m/Y H:i'),
                        $wo->work_closed?->format('d/m/Y H:i'),
                        $wo->action_by,
                        $wo->action_taken,
                    ]);
                }
                fclose($out);
            };
            return response()->stream($callback, 200, $headers);
        })->name('work-order-report.download');
        Volt::route('grafik',                  'karyawan.cs-grafik')->name('grafik');
    });

    // ── Price List / Item Master (AM, CS, ENG, FA — HKP/SEC tidak) ──────────
    Route::middleware('dept:AM,CS,ENG,FA')->prefix('cs')->name('cs.')->group(function () {
        Volt::route('item-master', 'karyawan.item-master')->name('item-master');
    });

    // ── In Out Permit (AM, CS, ENG, FA, SEC) ─────────────────────────────────
    Route::middleware('dept:AM,CS,ENG,FA,SEC')->prefix('cs')->name('cs.')->group(function () {
        Volt::route('in-out-permit', 'karyawan.in-out-permit')->name('in-out-permit');
    });

    // ── Facility Reservation (AM, CS, ENG, HKP, SEC) ─────────────────────────
    Route::middleware('dept:AM,CS,ENG,HKP,SEC')->prefix('cs')->name('cs.')->group(function () {
        Volt::route('facility-reservation', 'karyawan.facility-reservation')->name('facility-reservation');
    });

    // ── Greeting & Banner — AM & CS only ─────────────────────────────────────
    Route::middleware('dept:AM,CS')->prefix('greeting')->name('greeting.')->group(function () {
        Volt::route('dashboard', 'karyawan.greeting.dashboard-greeting')->name('dashboard');
        Volt::route('template',  'karyawan.greeting.template')->name('template');
        Volt::route('banner',    'karyawan.greeting.banner')->name('banner');
    });

    // ── QR Scan — Security (& AM, CS) ────────────────────────────────────────
    Route::middleware('dept:AM,CS,SEC')->group(function () {
    Route::get('qr-scan/{token}', function (string $token) {
        $res = \App\Models\FacilityReservation::where('qr_token', $token)->firstOrFail();
        return view('karyawan.qr-scan', compact('res'));
    })->name('qr.scan');

    Route::post('qr-scan/{token}/buka', function (string $token) {
        $res = \App\Models\FacilityReservation::where('qr_token', $token)->firstOrFail();
        if ($res->status === 'Siap Pelaksanaan') {
            $res->update([
                'status'      => 'Sedang Berlangsung',
                'sec_open_by' => auth()->user()?->name ?? 'Security',
                'sec_open_at' => now(),
            ]);
        }
        return redirect()->route('karyawan.qr.scan', $token)->with('flash', 'Reservasi dibuka. Fasilitas sedang berlangsung.');
    })->name('qr.buka');

    Route::post('qr-scan/{token}/tutup', function (string $token) {
        $res = \App\Models\FacilityReservation::where('qr_token', $token)->firstOrFail();
        if ($res->status === 'Sedang Berlangsung') {
            $res->update([
                'status'        => 'Selesai',
                'sec_close_by'  => auth()->user()?->name ?? 'Security',
                'sec_close_at'  => now(),
            ]);
        }
        return redirect()->route('karyawan.qr.scan', $token)->with('flash', 'Reservasi ditutup. Selesai.');
    })->name('qr.tutup');
    }); // end dept:AM,CS,SEC

    // ── Finance submenu — AM, FA & ENG ───────────────────────────────────────
    Route::middleware('dept:AM,FA,ENG')->prefix('fa')->name('fa.')->group(function () {
        Volt::route('daya-unit', 'fa.daya-unit')->name('daya-unit');
    });

    Route::middleware('dept:AM,FA')->prefix('fa')->name('fa.')->group(function () {
        Volt::route('invoice',              'fa.invoice-list')->name('invoice-list');
        Volt::route('wo-approval',         'fa.wo-approval')->name('wo-approval');
        Volt::route('permit-approval',     'fa.permit-approval')->name('permit-approval');
        Volt::route('facility-approval',   'fa.facility-approval')->name('facility-approval');

        // Invoice CSV template download
        Route::get('invoice-template', function () {
            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="invoice-template.csv"',
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];
            $callback = function () {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, \App\Models\Invoice::uploadTemplateHeaders());
                fputcsv($out, [
                    'INV/2026/001', '01/06/2026', '15/06/2026', 'A-101', 'JOHN DOE', 'IPL+Listrik+Air', 'Tagihan Juni 2026',
                    '500000', '1000.500', '1050.750', '2200 VA', '1444.70',
                    '3200', '10000', '0',
                    '100.000', '115.500', '8500',
                    '0', '0', '08123456789', 'john@email.com', '8800001010101',
                ]);
                fclose($out);
            };
            return response()->stream($callback, 200, $headers);
        })->name('invoice-template');

        // Invoice PDF download — admin/FA view
        Route::get('invoice/{id}/pdf', function (int $id) {
            $invoice = \App\Models\Invoice::findOrFail($id);
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('karyawan.invoice-pdf', compact('invoice'))
                ->setPaper('a4', 'portrait');
            $safeAcct = str_replace(['/', '\\'], '-', $invoice->debtor_acct);
            return $pdf->download('Invoice-' . $safeAcct . '-' . ($invoice->bulan ?? '') . '-' . ($invoice->tahun ?? '') . '.pdf');
        })->name('invoice-pdf');
    });
});

// ── Tenant Invoice PDF ────────────────────────────────────────────────────────
Route::middleware(['auth', 'tenant'])->prefix('tenant')->name('tenant.')->group(function () {
    Route::get('invoice/{id}/pdf', function (int $id) {
        $invoice = \App\Models\Invoice::findOrFail($id);
        // Pastikan invoice milik tenant yang login
        $unitNumber = auth()->user()->tenant?->unit_number ?? '';
        if ($invoice->debtor_acct !== $unitNumber) abort(403);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('karyawan.invoice-pdf', compact('invoice'))
            ->setPaper('a4', 'portrait');
        $safeAcct = str_replace(['/', '\\'], '-', $invoice->debtor_acct);
        return $pdf->download('Invoice-' . $safeAcct . '-' . ($invoice->bulan ?? '') . '-' . ($invoice->tahun ?? '') . '.pdf');
    })->name('invoice-pdf');
});

require __DIR__.'/auth.php';

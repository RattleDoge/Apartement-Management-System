<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\FacilityReservation;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination, WithFileUploads;

    // UI state
    public ?string $panelMode  = null; // null | 'input' | 'edit' | 'detail'
    public int     $selectedId = 0;
    public int     $perPage    = 20;

    // Per-column filters
    public string $fNomor      = '';
    public string $fUnit       = '';
    public string $fTenant     = '';
    public string $fFasilitas  = '';
    public string $fTanggal    = '';
    public string $fStatusBayar= '';
    public string $fStatus     = '';

    // Form props (Input / Edit)
    public string $formUnit          = '';
    public string $formTenantName    = '';
    public string $formNamaFasilitas = '';
    public string $formTanggal       = '';
    public string $formJamMulai      = '';
    public string $formJamSelesai    = '';
    public string $formKeperluan     = '';
    public int    $formJumlahTamu    = 0;
    public bool   $formIsBerbayar    = false;
    public string $formBiaya         = '0';
    public        $formBuktiBayar    = null;
    public string $formRequestBy     = '';
    public string $formRequestVia    = 'aplikasi';
    public string $formCatatan       = '';
    public        $formFoto          = null;

    // SEC close note (shown inline for selected ongoing reservation)
    public string $secCloseCatatan = '';

    // Scan QR modal
    public bool   $showScanModal = false;
    public string $scanToken     = '';
    public ?array $scanData      = null;
    public string $scanError     = '';

    // Feedback
    public string $successMsg = '';
    public string $errorMsg   = '';

    // ─── Lifecycle ────────────────────────────────────────────────────────────

    public function updated(string $prop): void
    {
        if (str_starts_with($prop, 'f') && !str_starts_with($prop, 'form')) {
            $this->resetPage();
        }
    }

    // Auto-fill nama tenant dari lot no
    public function updatedFormUnit(): void
    {
        $lot = strtoupper(trim($this->formUnit));
        if ($lot === '') { $this->formTenantName = ''; return; }

        $tenant = \App\Models\Tenant::with('user')
            ->whereRaw('UPPER(unit_number) = ?', [$lot])
            ->first();

        if ($tenant?->user) {
            $fullName = trim(($tenant->user->first_name ?? '') . ' ' . ($tenant->user->last_name ?? ''));
            $this->formTenantName = strtoupper($fullName ?: ($tenant->user->name ?? ''));
        }
    }

    // Auto-fill biaya when facility changes
    public function updatedFormNamaFasilitas(string $value): void
    {
        $defaults = FacilityReservation::fasilitasBiayaDefault();
        if (isset($defaults[$value])) {
            $this->formIsBerbayar = $defaults[$value]['is_berbayar'];
            $this->formBiaya      = (string) $defaults[$value]['biaya'];
        }
    }

    // ─── Data ─────────────────────────────────────────────────────────────────

    public function with(): array
    {
        $q = FacilityReservation::query()->orderByDesc('id');

        if ($this->fNomor)       $q->where('nomor',            'like', "%{$this->fNomor}%");
        if ($this->fUnit)        $q->where('unit',             'like', "%{$this->fUnit}%");
        if ($this->fTenant)      $q->where('tenant_name',      'like', "%{$this->fTenant}%");
        if ($this->fFasilitas)   $q->where('nama_fasilitas',   'like', "%{$this->fFasilitas}%");
        if ($this->fTanggal)     $q->whereDate('tanggal_reservasi', $this->fTanggal);
        if ($this->fStatusBayar) $q->where('status_bayar',     'like', "%{$this->fStatusBayar}%");
        if ($this->fStatus)      $q->where('status',           'like', "%{$this->fStatus}%");

        $selected = $this->selectedId
            ? FacilityReservation::find($this->selectedId)
            : null;

        $userDept  = optional(auth()->user()->karyawan)->departemen ?? '';
        $isManager = !in_array($userDept, ['FA', 'HKP', 'ENG', 'SEC']);

        return [
            'reservations' => $q->paginate($this->perPage),
            'selected'     => $selected,
            'userDept'     => $userDept,
            'isManager'    => $isManager,
        ];
    }

    // ─── Panel actions ────────────────────────────────────────────────────────

    public function openInput(): void
    {
        $this->resetFormProps();
        $this->formTanggal   = now()->format('Y-m-d');
        $this->formJamMulai  = '08:00';
        $this->formJamSelesai= '17:00';
        $this->panelMode  = 'input';
        $this->successMsg = '';
        $this->errorMsg   = '';
        $this->resetValidation();
    }

    public function openEdit(): void
    {
        if (!$this->selectedId) return;
        $r = FacilityReservation::findOrFail($this->selectedId);

        $this->formUnit          = $r->unit ?? '';
        $this->formTenantName    = $r->tenant_name ?? '';
        $this->formNamaFasilitas = $r->nama_fasilitas ?? '';
        $this->formTanggal       = $r->tanggal_reservasi?->format('Y-m-d') ?? '';
        $this->formJamMulai      = $r->jam_mulai ? substr($r->jam_mulai, 0, 5) : '';
        $this->formJamSelesai    = $r->jam_selesai ? substr($r->jam_selesai, 0, 5) : '';
        $this->formKeperluan     = $r->keperluan ?? '';
        $this->formJumlahTamu    = $r->jumlah_tamu ?? 0;
        $this->formIsBerbayar    = (bool) $r->is_berbayar;
        $this->formBiaya         = (string) ($r->biaya ?? 0);
        $this->formBuktiBayar    = null;
        $this->formRequestBy     = $r->request_by ?? '';
        $this->formRequestVia    = $r->request_via ?? 'aplikasi';
        $this->formCatatan       = $r->catatan ?? '';
        $this->formFoto          = null;

        $this->panelMode  = 'edit';
        $this->successMsg = '';
        $this->errorMsg   = '';
        $this->resetValidation();
    }

    public function openDetail(int $id): void
    {
        $this->selectedId = $id;
        $this->panelMode  = 'detail';
        $this->secCloseCatatan = '';
        $this->successMsg = '';
        $this->errorMsg   = '';
    }

    public function closePanel(): void
    {
        $this->panelMode  = null;
        $this->successMsg = '';
        $this->errorMsg   = '';
        $this->resetFormProps();
        $this->resetValidation();
    }

    public function selectRow(int $id): void
    {
        if ($this->selectedId === $id && $this->panelMode === 'detail') {
            $this->panelMode  = null;
            $this->selectedId = 0;
        } else {
            $this->openDetail($id);
        }
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    public function saveInput(): void
    {
        $this->validate([
            'formUnit'          => 'required',
            'formNamaFasilitas' => 'required',
            'formTanggal'       => 'required|date',
            'formJamMulai'      => 'required',
            'formKeperluan'     => 'required',
            'formFoto'          => 'nullable|image|max:4096',
            'formBuktiBayar'    => 'nullable|image|max:4096',
        ]);

        $nomor = $this->generateNomor();
        $foto  = null;
        $bukti = null;

        if ($this->formFoto)      $foto  = $this->formFoto->store('fasilitas-foto', 'public');
        if ($this->formBuktiBayar) $bukti = $this->formBuktiBayar->store('fasilitas-bukti', 'public');

        // Round Robin: assign CS officer automatically
        $rr = $this->assignRoundRobin();

        FacilityReservation::create([
            'nomor'            => $nomor,
            'unit'             => $this->formUnit,
            'tenant_name'      => $this->formTenantName ? strtoupper($this->formTenantName) : null,
            'nama_fasilitas'   => $this->formNamaFasilitas,
            'tanggal_reservasi'=> $this->formTanggal,
            'jam_mulai'        => $this->formJamMulai ?: null,
            'jam_selesai'      => $this->formJamSelesai ?: null,
            'keperluan'        => $this->formKeperluan,
            'jumlah_tamu'      => $this->formJumlahTamu,
            'is_berbayar'      => $this->formIsBerbayar,
            'biaya'            => $this->formIsBerbayar ? (float) $this->formBiaya : 0,
            'status_bayar'     => $this->formIsBerbayar ? 'Belum Bayar' : 'Bebas Biaya',
            'bukti_bayar'      => $bukti,
            'status'           => 'Pesan Diterima',
            'request_by'       => $this->formRequestBy ?: null,
            'request_via'      => $this->formRequestVia ?: null,
            'catatan'          => $this->formCatatan ?: null,
            'foto'             => $foto,
            'input_by'         => auth()->user()?->name,
            'rr_index'         => $rr['rr_index'],
            'rr_officer'       => $rr['rr_officer'],
        ]);

        $this->successMsg = "Reservasi {$nomor} berhasil dibuat.";
        $this->panelMode  = null;
        $this->resetFormProps();
    }

    public function saveEdit(): void
    {
        $this->validate([
            'formUnit'          => 'required',
            'formNamaFasilitas' => 'required',
            'formTanggal'       => 'required|date',
            'formJamMulai'      => 'required',
            'formKeperluan'     => 'required',
            'formFoto'          => 'nullable|image|max:4096',
            'formBuktiBayar'    => 'nullable|image|max:4096',
        ]);

        $r    = FacilityReservation::findOrFail($this->selectedId);
        $data = [
            'unit'             => $this->formUnit,
            'tenant_name'      => $this->formTenantName ? strtoupper($this->formTenantName) : null,
            'nama_fasilitas'   => $this->formNamaFasilitas,
            'tanggal_reservasi'=> $this->formTanggal,
            'jam_mulai'        => $this->formJamMulai ?: null,
            'jam_selesai'      => $this->formJamSelesai ?: null,
            'keperluan'        => $this->formKeperluan,
            'jumlah_tamu'      => $this->formJumlahTamu,
            'is_berbayar'      => $this->formIsBerbayar,
            'biaya'            => $this->formIsBerbayar ? (float) $this->formBiaya : 0,
            'request_by'       => $this->formRequestBy ?: null,
            'request_via'      => $this->formRequestVia ?: null,
            'catatan'          => $this->formCatatan ?: null,
        ];
        if ($this->formFoto)      $data['foto']      = $this->formFoto->store('fasilitas-foto', 'public');
        if ($this->formBuktiBayar) $data['bukti_bayar'] = $this->formBuktiBayar->store('fasilitas-bukti', 'public');

        $r->update($data);
        $this->successMsg = "Reservasi {$r->nomor} berhasil diupdate.";
        $this->panelMode  = 'detail'; // return to detail view
        $this->resetFormProps();
    }

    // ─── Approval & dept checks ───────────────────────────────────────────────

    public function approveCS(int $id): void
    {
        $r = FacilityReservation::findOrFail($id);
        if ($r->status !== 'Pesan Diterima') return;
        $r->update([
            'status' => 'Disetujui CS',
            'cs_by'  => auth()->user()?->name ?? 'System',
            'cs_at'  => now(),
        ]);
        if ($this->panelMode === 'detail') $this->selectedId = $id; // refresh detail
    }

    public function approveFin(int $id): void
    {
        $r = FacilityReservation::findOrFail($id);
        if ($r->status !== 'Disetujui CS' || !$r->is_berbayar) return;
        $r->update([
            'fin_by'      => auth()->user()?->name ?? 'System',
            'fin_at'      => now(),
            'status_bayar'=> 'Sudah Bayar',
        ]);
        $this->checkAndAdvance($r);
    }

    public function checkHK(int $id): void
    {
        $r = FacilityReservation::findOrFail($id);
        if ($r->status !== 'Disetujui CS') return;
        $r->update([
            'hk_by' => auth()->user()?->name ?? 'System',
            'hk_at' => now(),
        ]);
        $this->checkAndAdvance($r);
    }

    public function checkENG(int $id): void
    {
        $r = FacilityReservation::findOrFail($id);
        if ($r->status !== 'Disetujui CS') return;
        $r->update([
            'eng_by' => auth()->user()?->name ?? 'System',
            'eng_at' => now(),
        ]);
        $this->checkAndAdvance($r);
    }

    public function secOpen(int $id): void
    {
        $r = FacilityReservation::findOrFail($id);
        if ($r->status !== 'Siap Pelaksanaan') return;
        $r->update([
            'status'      => 'Sedang Berlangsung',
            'sec_open_by' => auth()->user()?->name ?? 'System',
            'sec_open_at' => now(),
        ]);
    }

    public function secClose(int $id): void
    {
        $r = FacilityReservation::findOrFail($id);
        if ($r->status !== 'Sedang Berlangsung') return;
        $r->update([
            'status'           => 'Selesai',
            'sec_close_by'     => auth()->user()?->name ?? 'System',
            'sec_close_at'     => now(),
            'sec_close_catatan'=> $this->secCloseCatatan ?: null,
        ]);
        $this->secCloseCatatan = '';
    }

    // ─── QR Scan (inline modal) ───────────────────────────────────────────────

    public function openScanModal(): void
    {
        $this->scanToken = '';
        $this->scanData  = null;
        $this->scanError = '';
        $this->showScanModal = true;
    }

    public function lookupQr(): void
    {
        $this->scanData  = null;
        $this->scanError = '';
        $token = trim($this->scanToken);
        if (!$token) { $this->scanError = 'Token tidak boleh kosong.'; return; }

        $res = FacilityReservation::where('qr_token', $token)->first();
        if (!$res) { $this->scanError = 'QR tidak valid atau reservasi tidak ditemukan.'; return; }

        $this->scanData = [
            'id'        => $res->id,
            'nomor'     => $res->nomor,
            'fasilitas' => $res->nama_fasilitas,
            'tanggal'   => $res->tanggal_reservasi?->format('d/m/Y'),
            'jam'       => substr($res->jam_mulai ?? '', 0, 5) . ' – ' . substr($res->jam_selesai ?? '', 0, 5),
            'tenant'    => $res->tenant_name,
            'unit'      => $res->unit,
            'status'    => $res->status,
            'token'     => $token,
        ];
    }

    public function qrBuka(): void
    {
        if (!$this->scanData) return;
        $res = FacilityReservation::where('qr_token', $this->scanData['token'])->first();
        if ($res && $res->status === 'Siap Pelaksanaan') {
            $res->update([
                'status'      => 'Sedang Berlangsung',
                'sec_open_by' => auth()->user()?->name ?? 'Security',
                'sec_open_at' => now(),
            ]);
            $this->successMsg = "Fasilitas {$res->nomor} berhasil dibuka. Sedang berlangsung.";
        }
        $this->showScanModal = false;
        $this->resetPage();
    }

    public function qrTutup(): void
    {
        if (!$this->scanData) return;
        $res = FacilityReservation::where('qr_token', $this->scanData['token'])->first();
        if ($res && $res->status === 'Sedang Berlangsung') {
            $res->update([
                'status'        => 'Selesai',
                'sec_close_by'  => auth()->user()?->name ?? 'Security',
                'sec_close_at'  => now(),
            ]);
            $this->successMsg = "Fasilitas {$res->nomor} ditutup. Status: Selesai.";
        }
        $this->showScanModal = false;
        $this->resetPage();
    }

    public function reject(int $id): void
    {
        $r = FacilityReservation::findOrFail($id);
        if ($r->isFinalized()) return;
        $r->update(['status' => 'Ditolak']);
    }

    // Auto-advance to Siap Pelaksanaan when all required checks are satisfied
    private function checkAndAdvance(FacilityReservation $r): void
    {
        $r->refresh();
        if ($r->status !== 'Disetujui CS') return;
        if ($r->is_berbayar && !$r->fin_by) return;
        if (!$r->hk_by || !$r->eng_by) return;
        $r->update(['status' => 'Siap Pelaksanaan']);
        $r->generateQrToken();
    }

    // ─── Round Robin helpers ───────────────────────────────────────────────────

    // Returns ordered list of CS officers for Round Robin rotation
    private function getRoundRobinOfficers(): array
    {
        $officers = \App\Models\Karyawan::where('departemen', 'CS')
            ->with('user')
            ->orderBy('id')
            ->get()
            ->map(fn($k) => $k->user?->name)
            ->filter()
            ->values()
            ->toArray();

        return $officers ?: ['CS Officer A', 'CS Officer B', 'CS Officer C'];
    }

    // Assign next officer in Round Robin rotation
    private function assignRoundRobin(): array
    {
        $officers  = $this->getRoundRobinOfficers();
        $total     = count($officers);
        $last      = FacilityReservation::whereNotNull('rr_index')->orderByDesc('id')->first();
        $lastIndex = $last ? (int) $last->rr_index : -1;
        $nextIndex = ($lastIndex + 1) % $total;

        return [
            'rr_index'   => $nextIndex,
            'rr_officer' => $officers[$nextIndex],
        ];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resetFormProps(): void
    {
        $this->formUnit          = '';
        $this->formTenantName    = '';
        $this->formNamaFasilitas = '';
        $this->formTanggal       = '';
        $this->formJamMulai      = '';
        $this->formJamSelesai    = '';
        $this->formKeperluan     = '';
        $this->formJumlahTamu    = 0;
        $this->formIsBerbayar    = false;
        $this->formBiaya         = '0';
        $this->formBuktiBayar    = null;
        $this->formRequestBy     = '';
        $this->formRequestVia    = 'aplikasi';
        $this->formCatatan       = '';
        $this->formFoto          = null;
    }

    private function generateNomor(): string
    {
        $romans = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $roman  = $romans[now()->month - 1];
        $year   = now()->year;

        $last = FacilityReservation::orderByDesc('id')->first();
        $seq  = 1;
        if ($last && preg_match('/FRS(\d+)/', $last->nomor, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return "FRS{$seq}/{$roman}/{$year}-MAP";
    }
} ?>

<div class="flex gap-2 h-full p-2">

    {{-- ── Main list panel ────────────────────────────────────────────────── --}}
    <div class="flex flex-col flex-1 min-w-0">

        {{-- Title bar + Round Robin badge + button --}}
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-2">
                <div class="text-white text-sm font-bold px-3 py-1.5 rounded-md" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                    Reservasi Fasilitas Umum
                </div>
                <span class="bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded">Round Robin</span>
                <span class="text-[10px] text-gray-500">Penugasan petugas CS bergiliran otomatis</span>
            </div>
            <button wire:click="openInput"
                class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1 cursor-pointer">
                + Reservasi
            </button>
        </div>

        {{-- Success / Error messages --}}
        @if($successMsg)
            <div class="bg-green-100 border border-green-400 text-green-700 text-xs px-3 py-1 mb-1 flex justify-between">
                <span>{{ $successMsg }}</span>
                <button wire:click="$set('successMsg','')" class="font-bold ml-2">✕</button>
            </div>
        @endif

        {{-- Table --}}
        <div class="overflow-auto flex-1 border border-gray-300">
            <table class="w-full text-xs border-collapse min-w-max">
                <thead>
                    {{-- Column headers --}}
                    <tr class="text-white text-center sticky top-0 z-10" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                        <th class="border border-blue-400 px-1 py-1.5">NO</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">NOMOR</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">UNIT</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">TENANT</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">FASILITAS</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">TANGGAL</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">JAM</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">TAMU</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">BIAYA</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">STATUS BAYAR</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap" title="Petugas CS (Round Robin)">PETUGAS CS</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">STATUS</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">ACTION</th>
                    </tr>
                    {{-- Per-column filter row --}}
                    <tr class="bg-white sticky top-[29px] z-10">
                        <th class="border border-gray-300 p-0"></th>
                        <th class="border border-gray-300 p-0">
                            <input wire:model.live.debounce.300ms="fNomor"
                                class="w-full text-xs px-1 py-0.5 border-0 focus:outline-none min-w-[100px]" />
                        </th>
                        <th class="border border-gray-300 p-0">
                            <input wire:model.live.debounce.300ms="fUnit"
                                class="w-full text-xs px-1 py-0.5 border-0 focus:outline-none min-w-[60px]" />
                        </th>
                        <th class="border border-gray-300 p-0">
                            <input wire:model.live.debounce.300ms="fTenant"
                                class="w-full text-xs px-1 py-0.5 border-0 focus:outline-none min-w-[80px]" />
                        </th>
                        <th class="border border-gray-300 p-0">
                            <select wire:model.live="fFasilitas"
                                class="w-full text-xs px-1 py-0.5 border-0 bg-white focus:outline-none min-w-[80px]">
                                <option value=""></option>
                                @foreach(\App\Models\FacilityReservation::fasilitasOptions() as $f)
                                    <option value="{{ $f }}">{{ $f }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="border border-gray-300 p-0">
                            <input type="date" wire:model.live="fTanggal"
                                class="w-full text-xs px-1 py-0.5 border-0 focus:outline-none" />
                        </th>
                        <th class="border border-gray-300 p-0"></th>
                        <th class="border border-gray-300 p-0"></th>
                        <th class="border border-gray-300 p-0"></th>
                        <th class="border border-gray-300 p-0">
                            <select wire:model.live="fStatusBayar"
                                class="w-full text-xs px-1 py-0.5 border-0 bg-white focus:outline-none">
                                <option value=""></option>
                                <option value="Bebas Biaya">Bebas Biaya</option>
                                <option value="Belum Bayar">Belum Bayar</option>
                                <option value="Sudah Bayar">Sudah Bayar</option>
                            </select>
                        </th>
                        <th class="border border-gray-300 p-0"></th>
                        <th class="border border-gray-300 p-0">
                            <select wire:model.live="fStatus"
                                class="w-full text-xs px-1 py-0.5 border-0 bg-white focus:outline-none">
                                <option value=""></option>
                                @foreach(\App\Models\FacilityReservation::statusOptions() as $s)
                                    <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="border border-gray-300 p-0"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reservations as $i => $r)
                        @php
                            $statusColor = match($r->status) {
                                'Disetujui CS'        => 'text-blue-600',
                                'Siap Pelaksanaan'    => 'text-green-600 font-semibold',
                                'Sedang Berlangsung'  => 'text-orange-500 font-semibold',
                                'Selesai'             => 'text-gray-500',
                                'Ditolak'             => 'text-red-500',
                                default               => 'text-amber-600',
                            };
                            $bayarColor = match($r->status_bayar) {
                                'Sudah Bayar' => 'text-green-600',
                                'Belum Bayar' => 'text-red-500',
                                default       => 'text-gray-400',
                            };
                            $rowBg = $selectedId === $r->id
                                ? 'bg-blue-100'
                                : ($i % 2 === 0 ? 'bg-white hover:bg-gray-50' : 'bg-gray-50 hover:bg-gray-100');
                        @endphp
                        <tr wire:click="selectRow({{ $r->id }})"
                            class="cursor-pointer {{ $rowBg }}">
                            <td class="border border-gray-200 px-1 py-0.5 text-center text-gray-500">
                                {{ $reservations->firstItem() + $i }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 whitespace-nowrap font-mono text-[11px]">
                                {{ $r->nomor }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 whitespace-nowrap font-semibold">
                                {{ $r->unit }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 max-w-[120px] truncate"
                                title="{{ $r->tenant_name }}">
                                {{ $r->tenant_name }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 whitespace-nowrap font-medium">
                                {{ $r->nama_fasilitas }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 whitespace-nowrap">
                                {{ $r->tanggal_reservasi?->format('d/m/Y') }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 text-center whitespace-nowrap text-[11px]">
                                {{ $r->jam_mulai ? substr($r->jam_mulai, 0, 5) : '' }}
                                @if($r->jam_selesai) – {{ substr($r->jam_selesai, 0, 5) }} @endif
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 text-center">
                                {{ $r->jumlah_tamu ?: '-' }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 text-right whitespace-nowrap">
                                @if($r->is_berbayar)
                                    Rp {{ number_format($r->biaya, 0, ',', '.') }}
                                @else
                                    <span class="text-gray-400">Gratis</span>
                                @endif
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 text-center whitespace-nowrap {{ $bayarColor }}">
                                {{ $r->status_bayar }}
                            </td>

                            {{-- PETUGAS CS (Round Robin assignment) --}}
                            <td class="border border-gray-200 px-1 py-0.5 text-center">
                                @if($r->rr_officer)
                                    @php
                                        $rrColors = ['bg-purple-100 text-purple-700','bg-blue-100 text-blue-700','bg-orange-100 text-orange-700','bg-teal-100 text-teal-700'];
                                        $rrColor  = $rrColors[($r->rr_index ?? 0) % count($rrColors)];
                                    @endphp
                                    <span class="text-[10px] font-semibold px-1 py-0.5 rounded {{ $rrColor }} whitespace-nowrap">
                                        {{ $r->rr_officer }}
                                    </span>
                                    <div class="text-[9px] text-gray-400">slot #{{ ($r->rr_index ?? 0) + 1 }}</div>
                                @else
                                    <span class="text-gray-300 text-[10px]">—</span>
                                @endif
                            </td>

                            {{-- STATUS + lampu approval dept --}}
                            <td class="border border-gray-200 px-1 py-1">
                                @php
                                    $isDitolak = $r->status === 'Ditolak';
                                    $depts = [
                                        ['CS',  $r->cs_by,       'CS: '.($r->cs_by ?? 'belum')],
                                        ['HKP', $r->hk_by,       'HKP: '.($r->hk_by ?? 'belum')],
                                        ['ENG', $r->eng_by,      'ENG: '.($r->eng_by ?? 'belum')],
                                        ['SEC', $r->sec_open_by, 'SEC: '.($r->sec_open_by ?? 'belum')],
                                    ];
                                    if ($r->is_berbayar) {
                                        array_splice($depts, 1, 0, [['FA', $r->fin_by, 'FA: '.($r->fin_by ?? 'belum')]]);
                                    }
                                @endphp
                                {{-- Lampu dept --}}
                                <div style="display:flex;gap:4px;justify-content:center;margin-bottom:3px;">
                                    @foreach($depts as [$dLabel, $dBy, $dTitle])
                                    <div style="display:flex;flex-direction:column;align-items:center;gap:1px;" title="{{ $dTitle }}">
                                        <span style="display:inline-block;width:9px;height:9px;border-radius:50%;box-shadow:inset 0 1px 2px rgba(0,0,0,.2);background:{{ $isDitolak ? '#ef4444' : ($dBy ? '#22c55e' : '#d1d5db') }};"></span>
                                        <span style="font-size:7px;color:{{ $dBy ? '#15803d' : '#9ca3af' }};">{{ $dLabel }}</span>
                                    </div>
                                    @endforeach
                                </div>
                                {{-- Status text --}}
                                <div class="text-center text-[10px] whitespace-nowrap font-medium {{ $statusColor }}">
                                    {{ $r->status }}
                                </div>
                            </td>

                            {{-- ACTION: dept-aware buttons --}}
                            <td class="border border-gray-200 px-1 py-0.5" wire:click.stop>
                                @php
                                    $canCS  = $isManager || $userDept === 'CS';
                                    $canFin = $isManager || $userDept === 'FA';
                                    $canHK  = $isManager || $userDept === 'HKP';
                                    $canENG = $isManager || $userDept === 'ENG';
                                    $canSec = $isManager || $userDept === 'SEC';
                                @endphp
                                <div class="flex flex-wrap gap-0.5 justify-center min-w-[60px]">

                                @if($r->status === 'Pesan Diterima' && $canCS)
                                    <button wire:click="approveCS({{ $r->id }})"
                                        class="bg-teal-500 hover:bg-teal-600 text-white text-[10px] px-1.5 py-0.5 cursor-pointer whitespace-nowrap">
                                        ✓ CS
                                    </button>
                                    <button wire:click="reject({{ $r->id }})"
                                        class="bg-red-400 hover:bg-red-500 text-white text-[10px] px-1.5 py-0.5 cursor-pointer">
                                        Tolak
                                    </button>

                                @elseif($r->status === 'Disetujui CS')
                                    @if($canFin && $r->is_berbayar && !$r->fin_by)
                                    <button wire:click="approveFin({{ $r->id }})"
                                        class="bg-blue-500 hover:bg-blue-600 text-white text-[10px] px-1.5 py-0.5 cursor-pointer whitespace-nowrap">
                                        ✓ FIN
                                    </button>
                                    @endif
                                    @if($canHK && !$r->hk_by)
                                    <button wire:click="checkHK({{ $r->id }})"
                                        class="bg-orange-400 hover:bg-orange-500 text-white text-[10px] px-1.5 py-0.5 cursor-pointer whitespace-nowrap">
                                        ✓ HK
                                    </button>
                                    @endif
                                    @if($canENG && !$r->eng_by)
                                    <button wire:click="checkENG({{ $r->id }})"
                                        class="bg-purple-500 hover:bg-purple-600 text-white text-[10px] px-1.5 py-0.5 cursor-pointer whitespace-nowrap">
                                        ✓ ENG
                                    </button>
                                    @endif
                                    @if($canCS)
                                    <button wire:click="reject({{ $r->id }})"
                                        class="bg-red-400 hover:bg-red-500 text-white text-[10px] px-1.5 py-0.5 cursor-pointer">
                                        Tolak
                                    </button>
                                    @endif

                                @elseif($r->status === 'Siap Pelaksanaan' && $canSec)
                                    <button wire:click="secOpen({{ $r->id }})"
                                        class="bg-green-600 hover:bg-green-700 text-white text-[10px] px-2 py-0.5 cursor-pointer whitespace-nowrap">
                                        ▶ Buka
                                    </button>

                                @elseif($r->status === 'Sedang Berlangsung' && $canSec)
                                    <button wire:click="secClose({{ $r->id }})"
                                        class="bg-gray-600 hover:bg-gray-700 text-white text-[10px] px-2 py-0.5 cursor-pointer whitespace-nowrap">
                                        ■ Tutup
                                    </button>
                                @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-gray-400 py-4 text-xs">
                                Tidak ada data reservasi
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Bottom toolbar + pagination --}}
        <div class="flex items-center justify-between mt-1 px-1 flex-wrap gap-1">
            <div class="flex items-center gap-3 text-xs">
                <button wire:click="openEdit"
                    class="{{ $selectedId ? 'text-blue-600 hover:underline cursor-pointer' : 'text-gray-300 cursor-default' }}">
                    ✏ Edit
                </button>
                <button wire:click="openScanModal"
                    class="flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white font-semibold px-3 py-1 rounded cursor-pointer transition-colors">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <path stroke-linecap="round" d="M14 14h2m0 0h3m-3 0v3m0 0v3m3-6h1"/>
                    </svg>
                    Scan QR
                </button>
            </div>

            {{-- Reference-style pagination --}}
            @php
                $cur  = $reservations->currentPage();
                $last = $reservations->lastPage();
                $nums = collect();
                for ($p = 1; $p <= $last; $p++) {
                    if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
                }
                $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
                $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
                $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
            @endphp
            <div class="flex items-center gap-1 text-xs text-gray-600 flex-wrap">
                @if($reservations->onFirstPage())
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
                @if($reservations->hasMorePages())
                    <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
                    <button wire:click="setPage({{ $last }})" class="{{ $pBtn }}">›|</button>
                @else
                    <span class="{{ $pDis }}">›</span>
                    <span class="{{ $pDis }}">›|</span>
                @endif

                <select wire:model.live="perPage"
                    class="border border-gray-300 text-xs px-1 py-0.5 bg-white ml-1">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>

                <span class="ml-1 text-gray-500">
                    View {{ $reservations->firstItem() ?? 0 }}–{{ $reservations->lastItem() ?? 0 }}
                    of {{ $reservations->total() }}
                </span>
            </div>
        </div>

        {{-- Dept legend + RR info --}}
        <div class="mt-1 text-[10px] text-gray-500 border-t border-gray-200 pt-1 flex items-start gap-4 flex-wrap">
            <span class="font-semibold">Alur Approval (berbayar):</span>
            <span class="text-teal-600 ml-1 font-bold">CS</span> (data hunian) →
            <span class="text-blue-600 font-bold">FIN</span> (konfirmasi bayar) →
            <span class="text-orange-500 font-bold">HK</span> (kebersihan) +
            <span class="text-purple-600 font-bold">ENG</span> (utilitas: AC/lampu/stop kontak) →
            <span class="text-green-600 font-bold">SEC</span> (keamanan + penutupan)
            &nbsp;|&nbsp;
            <span class="font-semibold">Gratis:</span> CS → HK + ENG → SEC
        </div>
        <div class="text-[10px] text-gray-500 border-t border-gray-200 pt-1">
            <span class="bg-purple-600 text-white font-bold px-1.5 py-0.5 rounded text-[9px] mr-1">Round Robin</span>
            Setiap reservasi baru otomatis ditugaskan ke petugas CS berikutnya secara bergiliran
            @php
                $rrOfficers = \App\Models\Karyawan::where('departemen','CS')->with('user')->orderBy('id')->get()->map(fn($k)=>$k->user?->name)->filter()->values()->toArray() ?: ['CS Officer A','CS Officer B','CS Officer C'];
            @endphp
            — Rotasi: {{ implode(' → ', $rrOfficers) }} → kembali ke awal
        </div>
    </div>

    {{-- ── Right panel (Input / Edit / Detail) ───────────────────────────── --}}
    @if($panelMode === 'input' || $panelMode === 'edit')

        {{-- Input / Edit form panel --}}
        <div class="w-80 border border-gray-300 bg-gray-50 flex flex-col text-xs shrink-0">
            <div class="flex items-center justify-between text-white px-3 py-1.5" style="background: linear-gradient(135deg, #1e3a8a, #2563eb);">
                <span class="font-bold">
                    {{ $panelMode === 'input' ? 'Reservasi Baru' : 'Edit Reservasi' }}
                </span>
                <button wire:click="closePanel" class="hover:text-gray-200 font-bold">✕</button>
            </div>

            <div class="p-3 overflow-y-auto flex-1 flex flex-col gap-2">

                @if($errorMsg)
                    <p class="text-red-500 text-xs bg-red-50 border border-red-200 px-2 py-1">{{ $errorMsg }}</p>
                @endif

                {{-- Unit --}}
                <div>
                    <label class="font-semibold text-gray-600">Unit / Lot No <span class="text-red-500">*</span></label>
                    <input wire:model.live.debounce.500ms="formUnit" type="text"
                        placeholder="contoh: MP/5/BV" style="text-transform:uppercase;"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400" />
                    @error('formUnit') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                {{-- Tenant Name (auto-fill dari lot no) --}}
                <div>
                    <label class="font-semibold text-gray-600">Nama Tenant
                        <span class="text-[10px] text-gray-400 font-normal">(otomatis dari lot no)</span>
                    </label>
                    <input wire:model="formTenantName" type="text"
                        class="w-full border border-gray-200 px-2 py-1 mt-0.5 bg-gray-50 focus:outline-none focus:border-blue-400"
                        placeholder="Otomatis terisi saat lot no dikenali" />
                </div>

                {{-- Fasilitas --}}
                <div>
                    <label class="font-semibold text-gray-600">Fasilitas <span class="text-red-500">*</span></label>
                    <select wire:model.live="formNamaFasilitas"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 bg-white focus:outline-none focus:border-blue-400">
                        <option value="">-- Pilih Fasilitas --</option>
                        @foreach(\App\Models\FacilityReservation::fasilitasOptions() as $f)
                            <option value="{{ $f }}">{{ $f }}</option>
                        @endforeach
                    </select>
                    @error('formNamaFasilitas') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                {{-- Tanggal --}}
                <div>
                    <label class="font-semibold text-gray-600">Tanggal Reservasi <span class="text-red-500">*</span></label>
                    <input wire:model="formTanggal" type="date"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400" />
                    @error('formTanggal') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                {{-- Jam Mulai & Selesai --}}
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="font-semibold text-gray-600">Jam Mulai <span class="text-red-500">*</span></label>
                        <input wire:model="formJamMulai" type="time"
                            class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400" />
                        @error('formJamMulai') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex-1">
                        <label class="font-semibold text-gray-600">Jam Selesai</label>
                        <input wire:model="formJamSelesai" type="time"
                            class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400" />
                    </div>
                </div>

                {{-- Keperluan --}}
                <div>
                    <label class="font-semibold text-gray-600">Keperluan / Tujuan <span class="text-red-500">*</span></label>
                    <textarea wire:model="formKeperluan" rows="2"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400 resize-y"
                        placeholder="Contoh: Acara ulang tahun, Meeting, dll..."></textarea>
                    @error('formKeperluan') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                {{-- Jumlah Tamu --}}
                <div>
                    <label class="font-semibold text-gray-600">Jumlah Tamu</label>
                    <input wire:model="formJumlahTamu" type="number" min="0"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400" />
                </div>

                {{-- Berbayar toggle --}}
                <div class="flex items-center gap-2 mt-1">
                    <input wire:model.live="formIsBerbayar" type="checkbox" id="chk_berbayar"
                        class="w-4 h-4 cursor-pointer" />
                    <label for="chk_berbayar" class="font-semibold text-gray-700 cursor-pointer">
                        Reservasi Berbayar
                    </label>
                </div>

                {{-- Biaya + Bukti (only when berbayar) --}}
                @if($formIsBerbayar)
                    <div>
                        <label class="font-semibold text-gray-600">Biaya (Rp)</label>
                        <input wire:model="formBiaya" type="number" min="0"
                            class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400" />
                    </div>
                    <div>
                        <label class="font-semibold text-gray-600">Bukti Pembayaran</label>
                        <input wire:model="formBuktiBayar" type="file" accept="image/*"
                            class="w-full border border-gray-300 px-2 py-1 mt-0.5 text-[11px] bg-white focus:outline-none" />
                        <div wire:loading wire:target="formBuktiBayar"
                            class="text-blue-500 text-[10px] mt-0.5">Mengupload...</div>
                        @error('formBuktiBayar') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                @endif

                {{-- Request By --}}
                <div>
                    <label class="font-semibold text-gray-600">Request By</label>
                    <input wire:model="formRequestBy" type="text"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400" />
                </div>

                {{-- Request Via --}}
                <div>
                    <label class="font-semibold text-gray-600">Request Via</label>
                    <select wire:model="formRequestVia"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 bg-white focus:outline-none focus:border-blue-400">
                        <option value=""></option>
                        @foreach(\App\Models\FacilityReservation::requestViaOptions() as $v)
                            <option value="{{ $v }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="font-semibold text-gray-600">Catatan</label>
                    <textarea wire:model="formCatatan" rows="2"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400 resize-y"
                        placeholder="Catatan tambahan (opsional)"></textarea>
                </div>

                {{-- Foto lampiran --}}
                <div>
                    <label class="font-semibold text-gray-600">Lampiran Foto</label>
                    <input wire:model="formFoto" type="file" accept="image/*"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 text-[11px] bg-white focus:outline-none" />
                    <div wire:loading wire:target="formFoto"
                        class="text-blue-500 text-[10px] mt-0.5">Mengupload...</div>
                    @error('formFoto') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                {{-- Submit --}}
                <button wire:click="{{ $panelMode === 'input' ? 'saveInput' : 'saveEdit' }}"
                    wire:loading.attr="disabled"
                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold py-1.5 mt-1 cursor-pointer">
                    <span wire:loading.remove wire:target="{{ $panelMode === 'input' ? 'saveInput' : 'saveEdit' }}">
                        {{ $panelMode === 'input' ? 'Simpan Reservasi' : 'Update' }}
                    </span>
                    <span wire:loading wire:target="{{ $panelMode === 'input' ? 'saveInput' : 'saveEdit' }}">
                        Menyimpan...
                    </span>
                </button>

            </div>
        </div>

    @elseif($panelMode === 'detail' && $selected)

        {{-- Detail / Approval panel --}}
        <div class="w-80 border border-gray-300 bg-gray-50 flex flex-col text-xs shrink-0">
            <div class="flex items-center justify-between text-white px-3 py-1.5" style="background: linear-gradient(135deg, #1e3a8a, #2563eb);">
                <span class="font-bold truncate">{{ $selected->nomor }}</span>
                <button wire:click="closePanel" class="hover:text-gray-200 font-bold ml-2">✕</button>
            </div>

            <div class="p-3 overflow-y-auto flex-1 flex flex-col gap-3">

                {{-- Basic info --}}
                <div class="bg-white border border-gray-200 p-2 rounded text-xs space-y-0.5">
                    <div><span class="text-gray-500">Fasilitas:</span>
                        <span class="font-semibold ml-1">{{ $selected->nama_fasilitas }}</span></div>
                    <div><span class="text-gray-500">Unit:</span>
                        <span class="font-semibold ml-1">{{ $selected->unit }}</span>
                        @if($selected->tenant_name)
                            <span class="text-gray-500 ml-1">/ {{ $selected->tenant_name }}</span>
                        @endif
                    </div>
                    <div><span class="text-gray-500">Tanggal:</span>
                        <span class="ml-1">{{ $selected->tanggal_reservasi?->format('d M Y') }}</span>
                        <span class="ml-1 text-gray-500">
                            {{ $selected->jam_mulai ? substr($selected->jam_mulai,0,5) : '' }}
                            @if($selected->jam_selesai) – {{ substr($selected->jam_selesai,0,5) }} @endif
                        </span>
                    </div>
                    <div><span class="text-gray-500">Keperluan:</span>
                        <span class="ml-1">{{ $selected->keperluan }}</span></div>
                    <div><span class="text-gray-500">Tamu:</span>
                        <span class="ml-1">{{ $selected->jumlah_tamu }} orang</span></div>
                    @if($selected->is_berbayar)
                        <div><span class="text-gray-500">Biaya:</span>
                            <span class="ml-1 font-semibold">Rp {{ number_format($selected->biaya, 0, ',', '.') }}</span>
                            <span class="ml-1 {{ $selected->status_bayar === 'Sudah Bayar' ? 'text-green-600' : 'text-red-500' }}">
                                ({{ $selected->status_bayar }})
                            </span>
                        </div>
                        @if($selected->bukti_bayar)
                            <div>
                                <a href="{{ asset('storage/' . $selected->bukti_bayar) }}" target="_blank"
                                    class="text-blue-500 underline">Lihat Bukti Bayar</a>
                            </div>
                        @endif
                    @else
                        <div><span class="text-green-500 font-semibold">Bebas Biaya</span></div>
                    @endif
                    @if($selected->rr_officer)
                        <div class="mt-1 pt-1 border-t border-gray-100">
                            <span class="text-gray-500">Petugas CS (Round Robin):</span>
                            <span class="ml-1 font-semibold text-purple-700">{{ $selected->rr_officer }}</span>
                            <span class="text-gray-400 text-[10px] ml-1">(slot #{{ ($selected->rr_index ?? 0) + 1 }})</span>
                        </div>
                    @endif
                    @if($selected->foto)
                        <div>
                            <a href="{{ Storage::url($selected->foto) }}" target="_blank"
                                class="text-blue-500 underline">Lampiran Foto</a>
                        </div>
                    @endif
                    @if($selected->catatan)
                        <div class="text-orange-600 italic">{{ $selected->catatan }}</div>
                    @endif
                </div>

                {{-- Approval checklist --}}
                <div class="space-y-1">
                    <p class="font-bold text-gray-700 border-b border-gray-300 pb-0.5">Alur Approval</p>

                    {{-- CS --}}
                    <div class="flex items-start gap-2 p-1.5 rounded
                        {{ $selected->cs_by ? 'bg-green-50 border border-green-200' : 'bg-white border border-gray-200' }}">
                        <span class="text-lg leading-none mt-0.5">{{ $selected->cs_by ? '✅' : '⬜' }}</span>
                        <div class="flex-1">
                            <p class="font-semibold text-teal-700">CS — Verifikasi Data Hunian</p>
                            @if($selected->cs_by)
                                <p class="text-gray-500 text-[10px]">{{ $selected->cs_by }}
                                    · {{ $selected->cs_at?->format('d/m/Y H:i') }}</p>
                            @elseif($selected->status === 'Pesan Diterima')
                                <button wire:click="approveCS({{ $selected->id }})"
                                    class="mt-1 bg-teal-500 hover:bg-teal-600 text-white text-[10px] px-2 py-0.5 cursor-pointer">
                                    Approve CS
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Finance (paid only) --}}
                    @if($selected->is_berbayar)
                    <div class="flex items-start gap-2 p-1.5 rounded
                        {{ $selected->fin_by ? 'bg-green-50 border border-green-200' : 'bg-white border border-gray-200' }}">
                        <span class="text-lg leading-none mt-0.5">{{ $selected->fin_by ? '✅' : '⬜' }}</span>
                        <div class="flex-1">
                            <p class="font-semibold text-blue-700">Finance — Konfirmasi Pembayaran</p>
                            @if($selected->fin_by)
                                <p class="text-gray-500 text-[10px]">{{ $selected->fin_by }}
                                    · {{ $selected->fin_at?->format('d/m/Y H:i') }}</p>
                            @elseif($selected->status === 'Disetujui CS' && $selected->cs_by)
                                <button wire:click="approveFin({{ $selected->id }})"
                                    class="mt-1 bg-blue-500 hover:bg-blue-600 text-white text-[10px] px-2 py-0.5 cursor-pointer">
                                    Konfirmasi Bayar
                                </button>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- HK --}}
                    <div class="flex items-start gap-2 p-1.5 rounded
                        {{ $selected->hk_by ? 'bg-green-50 border border-green-200' : 'bg-white border border-gray-200' }}">
                        <span class="text-lg leading-none mt-0.5">{{ $selected->hk_by ? '✅' : '⬜' }}</span>
                        <div class="flex-1">
                            <p class="font-semibold text-orange-600">Housekeeping — Kebersihan Ruangan</p>
                            @if($selected->hk_by)
                                <p class="text-gray-500 text-[10px]">{{ $selected->hk_by }}
                                    · {{ $selected->hk_at?->format('d/m/Y H:i') }}</p>
                            @elseif($selected->status === 'Disetujui CS' && $selected->cs_by)
                                <button wire:click="checkHK({{ $selected->id }})"
                                    class="mt-1 bg-orange-400 hover:bg-orange-500 text-white text-[10px] px-2 py-0.5 cursor-pointer">
                                    ✓ HK Siap
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- ENG --}}
                    <div class="flex items-start gap-2 p-1.5 rounded
                        {{ $selected->eng_by ? 'bg-green-50 border border-green-200' : 'bg-white border border-gray-200' }}">
                        <span class="text-lg leading-none mt-0.5">{{ $selected->eng_by ? '✅' : '⬜' }}</span>
                        <div class="flex-1">
                            <p class="font-semibold text-purple-700">Engineering — AC / Lampu / Stop Kontak</p>
                            @if($selected->eng_by)
                                <p class="text-gray-500 text-[10px]">{{ $selected->eng_by }}
                                    · {{ $selected->eng_at?->format('d/m/Y H:i') }}</p>
                            @elseif($selected->status === 'Disetujui CS' && $selected->cs_by)
                                <button wire:click="checkENG({{ $selected->id }})"
                                    class="mt-1 bg-purple-500 hover:bg-purple-600 text-white text-[10px] px-2 py-0.5 cursor-pointer">
                                    ✓ ENG Siap
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- SEC Open --}}
                    <div class="flex items-start gap-2 p-1.5 rounded
                        {{ $selected->sec_open_by ? 'bg-green-50 border border-green-200' : 'bg-white border border-gray-200' }}">
                        <span class="text-lg leading-none mt-0.5">{{ $selected->sec_open_by ? '✅' : '⬜' }}</span>
                        <div class="flex-1">
                            <p class="font-semibold text-green-700">Security — Buka / Keamanan</p>
                            @if($selected->sec_open_by)
                                <p class="text-gray-500 text-[10px]">{{ $selected->sec_open_by }}
                                    · {{ $selected->sec_open_at?->format('d/m/Y H:i') }}</p>
                            @elseif($selected->status === 'Siap Pelaksanaan')
                                <button wire:click="secOpen({{ $selected->id }})"
                                    class="mt-1 bg-green-600 hover:bg-green-700 text-white text-[10px] px-2 py-0.5 cursor-pointer">
                                    ▶ Buka Fasilitas
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- SEC Close --}}
                    <div class="flex items-start gap-2 p-1.5 rounded
                        {{ $selected->sec_close_by ? 'bg-green-50 border border-green-200' : 'bg-white border border-gray-200' }}">
                        <span class="text-lg leading-none mt-0.5">{{ $selected->sec_close_by ? '✅' : '⬜' }}</span>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-700">Security — Tutup &amp; Kondisi Akhir</p>
                            @if($selected->sec_close_by)
                                <p class="text-gray-500 text-[10px]">{{ $selected->sec_close_by }}
                                    · {{ $selected->sec_close_at?->format('d/m/Y H:i') }}</p>
                                @if($selected->sec_close_catatan)
                                    <p class="mt-0.5 text-gray-600 italic">{{ $selected->sec_close_catatan }}</p>
                                @endif
                            @elseif($selected->status === 'Sedang Berlangsung')
                                <textarea wire:model="secCloseCatatan" rows="2"
                                    class="w-full border border-gray-300 px-1 py-0.5 mt-1 text-[10px] focus:outline-none resize-none"
                                    placeholder="Catatan kondisi ruangan (opsional)..."></textarea>
                                <button wire:click="secClose({{ $selected->id }})"
                                    class="mt-1 bg-gray-600 hover:bg-gray-700 text-white text-[10px] px-2 py-0.5 cursor-pointer">
                                    ■ Tutup Fasilitas
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Reject button (only if not finalized) --}}
                    @if(!$selected->isFinalized())
                        <div class="pt-1 border-t border-gray-200">
                            <button wire:click="reject({{ $selected->id }})"
                                class="w-full bg-red-400 hover:bg-red-500 text-white text-[10px] font-bold py-1 cursor-pointer">
                                ✕ Tolak Reservasi
                            </button>
                        </div>
                    @endif

                    @if($selected->status === 'Ditolak')
                        <div class="bg-red-50 border border-red-200 text-red-600 text-[10px] p-1.5 rounded">
                            Reservasi ini telah ditolak.
                        </div>
                    @endif
                </div>

                {{-- Edit button --}}
                <button wire:click="openEdit"
                    class="w-full border border-blue-400 text-blue-600 hover:bg-blue-50 text-[11px] py-1 cursor-pointer">
                    ✏ Edit Data Reservasi
                </button>

            </div>
        </div>

    @endif

    {{-- ══════════════ MODAL: SCAN QR ══════════════ --}}
    @if($showScanModal)
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <div style="position:fixed;inset:0;z-index:60;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.55);"
         wire:click.self="$set('showScanModal', false)">
        <div style="background:white;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,0.25);width:100%;max-width:440px;overflow:hidden;">

            {{-- Header --}}
            <div style="background:linear-gradient(135deg,#065f46 0%,#059669 100%);padding:14px 18px;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <path stroke-linecap="round" d="M14 14h2m0 0h3m-3 0v3m0 0v3m3-6h1"/>
                    </svg>
                    <span style="color:white;font-weight:700;font-size:14px;">Scan QR Reservasi</span>
                </div>
                <button wire:click="$set('showScanModal', false)"
                        style="color:white;background:rgba(255,255,255,0.2);border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:center;">✕</button>
            </div>

            <div style="padding:20px 20px 16px;">

                @if(!$scanData)
                {{-- Step 1: Kamera Scanner + Manual Input --}}
                <div x-data="{
                    scanner: null,
                    scanning: false,
                    camErr: '',
                    init() { this.$nextTick(() => this.startScan()); },
                    destroy() { this.stopScan(); },
                    startScan() {
                        this.camErr = '';
                        if (typeof Html5Qrcode === 'undefined') {
                            this.camErr = 'Library scanner belum siap. Gunakan input manual.';
                            return;
                        }
                        const el = document.getElementById('ams-qr-reader');
                        if (!el) return;
                        this.scanner = new Html5Qrcode('ams-qr-reader');
                        this.scanner.start(
                            { facingMode: 'environment' },
                            { fps: 10, qrbox: { width: 220, height: 220 } },
                            (text) => {
                                const m = text.match(/qr-scan\/([^\/?#\s]+)/);
                                const tok = m ? m[1] : text.trim();
                                this.stopScan();
                                $wire.set('scanToken', tok).then(() => $wire.lookupQr());
                            },
                            () => {}
                        ).then(() => { this.scanning = true; })
                         .catch(err => {
                            this.scanning = false;
                            this.camErr = 'Kamera tidak tersedia atau izin ditolak. Gunakan input manual.';
                         });
                    },
                    stopScan() {
                        if (this.scanner) {
                            this.scanner.stop().catch(() => {}).finally(() => {
                                try { this.scanner.clear(); } catch(e) {}
                                this.scanner = null;
                                this.scanning = false;
                            });
                        }
                    }
                }">
                    {{-- Viewfinder kamera --}}
                    <div style="position:relative;border-radius:10px;overflow:hidden;background:#111;margin-bottom:10px;">
                        <div id="ams-qr-reader" style="width:100%;"></div>
                        <div x-show="!scanning && !camErr"
                             style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:12px;background:#1f2937;">
                            Memulai kamera...
                        </div>
                    </div>

                    <div x-show="camErr" style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;border-radius:8px;padding:8px 12px;font-size:11px;margin-bottom:10px;" x-text="camErr"></div>

                    <div style="display:flex;justify-content:center;gap:8px;margin-bottom:14px;">
                        <button x-show="!scanning" x-on:click="startScan()" type="button"
                                style="font-size:11px;font-weight:600;padding:5px 14px;border-radius:6px;border:1px solid #059669;color:#059669;background:white;cursor:pointer;">
                            📷 Nyalakan Kamera
                        </button>
                        <button x-show="scanning" x-on:click="stopScan()" type="button"
                                style="font-size:11px;font-weight:600;padding:5px 14px;border-radius:6px;border:1px solid #6b7280;color:#6b7280;background:white;cursor:pointer;">
                            ■ Stop Kamera
                        </button>
                    </div>

                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                        <div style="flex:1;height:1px;background:#e5e7eb;"></div>
                        <span style="font-size:11px;color:#9ca3af;">atau input manual</span>
                        <div style="flex:1;height:1px;background:#e5e7eb;"></div>
                    </div>

                    <div style="display:flex;gap:8px;">
                        <input wire:model="scanToken" type="text"
                               placeholder="Paste token QR di sini..."
                               style="flex:1;border:1.5px solid #d1d5db;border-radius:8px;padding:8px 12px;font-size:12px;font-family:monospace;outline:none;"
                               wire:keydown.enter="lookupQr" x-on:keydown.enter="stopScan()" />
                        <button wire:click="lookupQr" wire:loading.attr="disabled"
                                x-on:click="stopScan()"
                                style="background:#059669;color:white;border:none;border-radius:8px;padding:8px 16px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;">
                            <span wire:loading.remove wire:target="lookupQr">Cari</span>
                            <span wire:loading wire:target="lookupQr">...</span>
                        </button>
                    </div>

                    @if($scanError)
                    <div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;border-radius:8px;padding:8px 12px;font-size:12px;margin-top:8px;">
                        {{ $scanError }}
                    </div>
                    @endif
                </div>

                @else
                {{-- Step 2: Konfirmasi --}}
                @php
                    $sStatus = $scanData['status'];
                    $canBuka  = $sStatus === 'Siap Pelaksanaan';
                    $canTutup = $sStatus === 'Sedang Berlangsung';
                    $statusBg = match($sStatus) {
                        'Siap Pelaksanaan'   => '#ecfdf5',
                        'Sedang Berlangsung' => '#fff7ed',
                        default              => '#f9fafb',
                    };
                    $statusColor = match($sStatus) {
                        'Siap Pelaksanaan'   => '#065f46',
                        'Sedang Berlangsung' => '#c2410c',
                        default              => '#6b7280',
                    };
                @endphp
                <div style="background:{{ $statusBg }};border-radius:10px;padding:14px;margin-bottom:14px;">
                    <div style="font-size:11px;font-weight:800;color:{{ $statusColor }};text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;">
                        {{ $sStatus }}
                    </div>
                    @foreach([
                        ['Nomor',     $scanData['nomor']],
                        ['Fasilitas', $scanData['fasilitas']],
                        ['Tanggal',   $scanData['tanggal']],
                        ['Jam',       $scanData['jam']],
                        ['Tenant',    $scanData['tenant']],
                        ['Unit',      $scanData['unit']],
                    ] as [$lbl, $val])
                    <div style="display:flex;gap:8px;font-size:12px;margin-bottom:4px;">
                        <span style="color:#9ca3af;width:64px;flex-shrink:0;">{{ $lbl }}</span>
                        <span style="color:#111827;font-weight:600;">{{ $val }}</span>
                    </div>
                    @endforeach
                </div>

                @if($canBuka || $canTutup)
                <p style="font-size:11px;color:#6b7280;margin-bottom:10px;text-align:center;">
                    Konfirmasi tindakan untuk reservasi ini:
                </p>
                <div style="display:flex;gap:8px;">
                    <button wire:click="$set('scanData', null)"
                            style="flex:1;border:1px solid #d1d5db;background:white;color:#6b7280;border-radius:8px;padding:9px;font-size:12px;font-weight:600;cursor:pointer;">
                        ← Kembali
                    </button>
                    @if($canBuka)
                    <button wire:click="qrBuka" wire:loading.attr="disabled"
                            style="flex:2;background:#16a34a;color:white;border:none;border-radius:8px;padding:9px;font-size:13px;font-weight:700;cursor:pointer;">
                        <span wire:loading.remove wire:target="qrBuka">▶ Konfirmasi Buka Fasilitas</span>
                        <span wire:loading wire:target="qrBuka">Memproses...</span>
                    </button>
                    @elseif($canTutup)
                    <button wire:click="qrTutup" wire:loading.attr="disabled"
                            style="flex:2;background:#dc2626;color:white;border:none;border-radius:8px;padding:9px;font-size:13px;font-weight:700;cursor:pointer;">
                        <span wire:loading.remove wire:target="qrTutup">■ Konfirmasi Tutup Fasilitas</span>
                        <span wire:loading wire:target="qrTutup">Memproses...</span>
                    </button>
                    @endif
                </div>
                @else
                <div style="background:#f3f4f6;border-radius:8px;padding:10px;text-align:center;font-size:12px;color:#6b7280;">
                    Status <strong>{{ $sStatus }}</strong> — tidak ada tindakan yang diperlukan.
                </div>
                <button wire:click="$set('showScanModal', false)"
                        style="width:100%;margin-top:10px;background:#6b7280;color:white;border:none;border-radius:8px;padding:9px;font-size:12px;font-weight:600;cursor:pointer;">
                    Tutup
                </button>
                @endif
                @endif

            </div>
        </div>
    </div>
    @endif

</div>

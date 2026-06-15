<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\TenantRequest;
use App\Models\WorkOrder;
use App\Models\Tenant;
use App\Models\HandoverUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination, WithFileUploads;

    // Per-column filters
    public string $fNoRequest     = '';
    public string $fTglVerifikasi = '';
    public string $fKategori      = '';
    public string $fSubKategori   = '';
    public string $fLotNo         = '';
    public string $fTglStr        = '';
    public string $fNama          = '';
    public string $fKepemilikan   = '';
    public string $fSalesAgent    = '';
    public string $fDescs         = '';
    public string $fRequestBy     = '';
    public string $fRequestVia    = '';
    public string $fStatus        = '';
    public string $fDescStatus    = '';

    public int $perPage = 20;

    // Selected row
    public ?int $selectedId = null;

    // Panel mode: null | 'input' | 'edit'
    public ?string $panelMode = null;

    // Form: Input
    public string $formLotNo       = '';
    public string $formNama        = '';
    public string $formKepemilikan = '';
    public string $formSalesAgent  = '';
    public string $formKategori    = '';
    public string $formSubKategori = '';
    public string $formPelaporanVia = '';
    public string $formDescs       = '';
    public string $formRequestBy   = '';
    public string $formBerulang    = 'Tidak';
    public string $formTglStr      = '';
    public string $formStatus      = 'Pesan Diterima';
    public string $formDescStatus  = '';

    // Form: Edit (status + desc_status only editable)
    public string $editStatus     = '';
    public string $editDescStatus = '';
    public string $editTglVerifikasi = '';
    public $editFoto = null;

    public function updated($prop): void
    {
        if (str_starts_with($prop, 'f') && ! str_starts_with($prop, 'form')) {
            $this->resetPage();
        }
    }

    public function selectRow(int $id): void
    {
        $this->selectedId = ($this->selectedId === $id) ? null : $id;
        if ($this->panelMode !== null) {
            $this->panelMode = null;
        }
    }

    public function openInput(): void
    {
        $this->resetFormInput();
        $this->formTglStr = now()->toDateString();
        $this->panelMode  = 'input';
        $this->selectedId = null;
    }

    public function openEdit(): void
    {
        if (! $this->selectedId) return;
        $req = TenantRequest::find($this->selectedId);
        if (! $req) return;

        $this->editStatus       = $req->status ?? '';
        $this->editDescStatus   = $req->desc_status ?? '';
        $this->editTglVerifikasi = $req->tgl_verifikasi?->toDateString() ?? '';
        $this->panelMode = 'edit';
    }

    public function closePanel(): void
    {
        $this->panelMode = null;
    }

    public function updatedFormLotNo(): void
    {
        $lot = strtoupper(trim($this->formLotNo));
        if ($lot === '') {
            $this->formNama = '';
            $this->formKepemilikan = '';
            return;
        }
        $tenant = Tenant::with('user')
            ->whereRaw('UPPER(unit_number) = ?', [$lot])
            ->first();
        if ($tenant) {
            $this->formNama        = strtoupper($tenant->user?->name ?? '');
            $this->formKepemilikan = $tenant->status ?? '';
        }
        $handover = HandoverUnit::whereRaw('UPPER(lot_no) = ?', [$lot])->first();
        if ($handover?->str_date) {
            $this->formTglStr = $handover->str_date->format('Y-m-d');
        }
    }

    public function saveInput(): void
    {
        $this->validate([
            'formLotNo'       => 'required',
            'formKategori'    => 'required',
            'formDescs'       => 'required',
            'formPelaporanVia'=> 'required',
        ], [
            'formLotNo.required'       => 'No. Unit wajib diisi.',
            'formKategori.required'    => 'Kategori wajib dipilih.',
            'formDescs.required'       => 'Keterangan wajib diisi.',
            'formPelaporanVia.required'=> 'Pelaporan Via wajib dipilih.',
        ]);

        // Generate no_request
        $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $month = (int) now()->format('n');
        $year  = now()->year;
        $roman = $romanMonths[$month - 1];
        $lastReq = TenantRequest::orderByDesc('id')->first();
        $nextNum = 1;
        if ($lastReq) {
            preg_match('/R(\d+)/', $lastReq->no_request, $m);
            $nextNum = isset($m[1]) ? ((int)$m[1] + 1) : 1;
        }
        $noRequest = sprintf('R%07d/%s/%d-MAP', $nextNum, $roman, $year);

        TenantRequest::create([
            'no_request'    => $noRequest,
            'tanggal'       => now(),
            'lot_no'        => strtoupper(trim($this->formLotNo)),
            'nama'          => strtoupper(trim($this->formNama)),
            'kepemilikan'   => $this->formKepemilikan,
            'sales_agent'   => strtoupper(trim($this->formSalesAgent)),
            'kategori'      => $this->formKategori,
            'sub_kategori'  => $this->formSubKategori,
            'pelaporan_via' => $this->formPelaporanVia,
            'descs'         => $this->formDescs,
            'request_by'    => strtoupper(trim($this->formRequestBy)) ?: strtoupper(trim($this->formNama)),
            'berulang'      => $this->formBerulang,
            'tgl_str'       => $this->formTglStr ?: null,
            'status'        => $this->formStatus,
            'desc_status'   => $this->formDescStatus,
            'input_by'      => Auth::user()?->name ?? 'system',
            'is_selesai'    => false,
        ]);

        $this->resetFormInput();
        $this->panelMode = null;
        session()->flash('success', 'Tenant Request berhasil disimpan: ' . $noRequest);
    }

    public function saveEdit(): void
    {
        if (! $this->selectedId) return;
        $req = TenantRequest::find($this->selectedId);
        if (! $req) return;

        $this->validate([
            'editFoto' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], ['editFoto.mimes' => 'File harus JPG, PNG, atau PDF.', 'editFoto.max' => 'Ukuran file max 5MB.']);

        $data = [
            'status'         => $this->editStatus,
            'desc_status'    => $this->editDescStatus,
            'tgl_verifikasi' => $this->editTglVerifikasi ?: null,
            'done_by'        => Auth::user()?->name ?? 'system',
        ];
        // Auto-stamp status transition dates (only set once, never overwrite)
        if ($this->editStatus === 'Dalam Pengecekan' && ! $req->tgl_verifikasi) {
            $data['tgl_verifikasi'] = now()->toDateString();
        }
        if ($this->editStatus === 'Dalam Proses' && ! $req->tgl_dalam_proses) {
            $data['tgl_dalam_proses'] = now()->toDateString();
        }
        if ($this->editStatus === 'Selesai' && ! $req->tgl_selesai) {
            $data['tgl_selesai'] = now()->toDateString();
        }

        if ($this->editFoto) {
            $path = $this->editFoto->store('tenant-foto', 'public');
            $data['foto'] = $path;
        }

        $req->update($data);
        $this->editFoto = null;
        $this->panelMode = null;
        session()->flash('success', 'Request berhasil diperbarui.');
    }

    public function buatWo(int $id): void
    {
        $req = TenantRequest::find($id);
        if (! $req) return;

        // Idempotent: check if WO already exists
        $existing = WorkOrder::where('no_complain', $req->no_request)->first();
        if ($existing) {
            session()->flash('info', 'Work Order sudah ada: ' . $existing->no_wo);
            return;
        }

        // Auto-generate WO number (EX) — global counter shared with IN
        $noWo = WorkOrder::generateNoWo('EX');

        // Map kategori/sub_kategori to jenis_wo
        $subToJenis = [
            'CIVIL'            => 'CIVIL',
            'ELECTRICAL'       => 'ELECTRICAL',
            'PLUMBING'         => 'PLUMBING',
            'MECHANICAL'       => 'MECHANICAL',
            'HVAC'             => 'HVAC',
            'ACCESS CARD'      => 'PERGANTIAN ACCESS CARD',
            'GENERAL'          => 'GENERAL',
            'PAINTING'         => 'PAINTING',
            'LIFT'             => 'MECHANICAL',
            'WATER / ELECTRICITY' => 'WATER / ELECTRICITY',
        ];
        $jenisWo = $subToJenis[$req->sub_kategori] ?? 'GENERAL';

        $wo = WorkOrder::create([
            'ex_in'        => 'EX',
            'no_complain'  => $req->no_request,
            'no_wo'        => $noWo,
            'jenis_wo'     => $jenisWo,
            'tanggal'      => now(),
            'lot_no'       => $req->lot_no,
            'name'         => $req->nama,
            'descs'        => $req->descs,
            'status_comp'  => 'Pesan Diterima',
            'request_by'   => $req->request_by,
            'request_via'  => $req->pelaporan_via,
            'assign_dep'   => 'ENG',
            'input_by'     => Auth::user()?->name ?? 'system',
        ]);

        // Update the request's desc_status
        $req->update([
            'desc_status' => ($req->desc_status ? $req->desc_status . "\n" : '') . 'WO ' . $noWo . ' dibuat',
            'status'      => 'Dalam Pengecekan',
        ]);

        session()->flash('success', 'Work Order ' . $noWo . ' berhasil dibuat.');
    }

    public function closeRequest(): void
    {
        if (! $this->selectedId) return;
        $req = TenantRequest::find($this->selectedId);
        if (! $req) return;

        $req->update(['is_selesai' => true]);
        $this->selectedId = null;
        $this->panelMode  = null;
        session()->flash('success', 'Request berhasil ditutup.');
    }

    private function resetFormInput(): void
    {
        $this->formLotNo        = '';
        $this->formNama         = '';
        $this->formKepemilikan  = '';
        $this->formSalesAgent   = '';
        $this->formKategori     = '';
        $this->formSubKategori  = '';
        $this->formPelaporanVia = '';
        $this->formDescs        = '';
        $this->formRequestBy    = '';
        $this->formBerulang     = 'Tidak';
        $this->formTglStr       = '';
        $this->formStatus       = 'Pesan Diterima';
        $this->formDescStatus   = '';
    }

    public function with(): array
    {
        $query = TenantRequest::where('is_selesai', false)
            ->when($this->fNoRequest,     fn($q) => $q->where('no_request',   'like', "%{$this->fNoRequest}%"))
            ->when($this->fTglVerifikasi, fn($q) => $q->whereDate('tgl_verifikasi', $this->fTglVerifikasi))
            ->when($this->fKategori,      fn($q) => $q->where('kategori',     'like', "%{$this->fKategori}%"))
            ->when($this->fSubKategori,   fn($q) => $q->where('sub_kategori', 'like', "%{$this->fSubKategori}%"))
            ->when($this->fLotNo,         fn($q) => $q->where('lot_no',       'like', "%{$this->fLotNo}%"))
            ->when($this->fTglStr,        fn($q) => $q->whereDate('tgl_str',  $this->fTglStr))
            ->when($this->fNama,          fn($q) => $q->where('nama',         'like', "%{$this->fNama}%"))
            ->when($this->fKepemilikan,   fn($q) => $q->where('kepemilikan',  'like', "%{$this->fKepemilikan}%"))
            ->when($this->fSalesAgent,    fn($q) => $q->where('sales_agent',  'like', "%{$this->fSalesAgent}%"))
            ->when($this->fDescs,         fn($q) => $q->where('descs',        'like', "%{$this->fDescs}%"))
            ->when($this->fRequestBy,     fn($q) => $q->where('request_by',   'like', "%{$this->fRequestBy}%"))
            ->when($this->fRequestVia,    fn($q) => $q->where('pelaporan_via', $this->fRequestVia))
            ->when($this->fStatus,        fn($q) => $q->where('status',       $this->fStatus))
            ->when($this->fDescStatus,    fn($q) => $q->where('desc_status',  'like', "%{$this->fDescStatus}%"))
            ->orderByDesc('tanggal');

        $requests = $query->paginate($this->perPage);

        $noRequests = $requests->pluck('no_request')->filter()->values()->toArray();
        $linkedWos  = WorkOrder::whereIn('no_complain', $noRequests)->get()->keyBy('no_complain');

        $selectedRequest = $this->selectedId ? TenantRequest::find($this->selectedId) : null;

        return compact('requests', 'linkedWos', 'selectedRequest');
    }
}; ?>

<div class="p-4">
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-3 px-4 py-2 bg-green-100 border border-green-400 text-green-800 rounded text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div class="mb-3 px-4 py-2 bg-blue-100 border border-blue-400 text-blue-800 rounded text-sm">
            {{ session('info') }}
        </div>
    @endif

    {{-- Table --}}
    <div class="border border-blue-200 overflow-x-auto rounded-lg shadow-sm" style="font-size: 11px;">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <span>TENANT REQUEST BELUM SELESAI</span>
            <span class="text-xs font-normal opacity-80" wire:loading>Memuat...</span>
        </div>
        <table class="border-collapse" style="min-width: 2200px; width: 100%;">
            <thead>
                {{-- Header labels --}}
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-1 py-1.5 text-center w-7">#</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:180px;">NOMOR/DATE REQUEST</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:100px;">TGL.VERIFIKASI</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:120px;">CATEGORY</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:120px;">SUB CATEGORY</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:80px;">LOT NO</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:100px;">TGL. STR</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:140px;">NAMA</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:90px;">KEPEMILIKAN</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:90px;">SALES/AGENT</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:200px;">DESCS</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:120px;">REQUEST BY</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:90px;">REQUEST VIA</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:120px;">STATUS</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:70px;">PICTURE</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:170px;">DESC STATUS</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:160px;">WORK ORDER</th>
                </tr>
                {{-- Filter row --}}
                <tr style="background-color:#f0f7ff;">
                    <th class="border border-blue-100 px-1 py-0.5"></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fNoRequest" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <input wire:model.live="fTglVerifikasi" type="date"
                            class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white" /></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fKategori" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fSubKategori" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fLotNo" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <input wire:model.live="fTglStr" type="date"
                            class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white" /></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fNama" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fKepemilikan" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fSalesAgent" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fDescs" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fRequestBy" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <select wire:model.live="fRequestVia" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                            <option value=""></option>
                            @foreach(\App\Models\TenantRequest::pelaporanViaOptions() as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <select wire:model.live="fStatus" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                            <option value=""></option>
                            @foreach(\App\Models\TenantRequest::statusOptions() as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="border border-blue-100 px-1 py-0.5"></th>
                    <th class="border border-blue-100 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fDescStatus" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-blue-100 px-1 py-0.5"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $idx => $req)
                @php
                    $isSelected = $selectedId === $req->id;
                    $linkedWo   = $linkedWos->get($req->no_request);
                    $tglStr     = $req->tgl_str;
                    $hariStr    = $tglStr ? (int)\Carbon\Carbon::parse($tglStr)->diffInDays(now()) : null;
                    $fotoUrl    = $req->foto ? asset('storage/' . $req->foto) : null;
                    $isPdf      = $req->foto && str_ends_with(strtolower($req->foto), '.pdf');
                    $bgStyle    = $isSelected ? 'background:linear-gradient(to right,#dbeafe,#eff6ff);border-left:3px solid #2563eb;' : match($req->status) {
                        'Pesan Diterima'         => 'background-color:#fffbeb;',
                        'Dalam Pengecekan'       => 'background-color:#eff6ff;',
                        'Dalam Proses'           => 'background-color:#fff7ed;',
                        'Tidak Dapat Diaplikasi' => 'background-color:#fef2f2;',
                        default => '',
                    };
                @endphp
                <tr wire:click="selectRow({{ $req->id }})" style="{{ $bgStyle }}" class="cursor-pointer hover:opacity-80">
                    <td class="border border-blue-100 px-1 py-1 text-center text-gray-500 align-top">
                        {{ $requests->firstItem() + $idx }}
                    </td>
                    {{-- NOMOR/DATE REQUEST --}}
                    <td class="border border-blue-100 px-2 py-1 align-top">
                        <div class="font-mono font-semibold text-[#1a6b9a]">{{ $req->no_request }}</div>
                        <div class="text-gray-400 text-[10px]">{{ $req->tanggal?->format('Y-m-d H:i:s') }}</div>
                    </td>
                    <td class="border border-blue-100 px-2 py-1 align-top text-center">
                        {{ $req->tgl_verifikasi?->format('Y-m-d') ?? '-' }}
                    </td>
                    <td class="border border-blue-100 px-2 py-1 align-top">{{ $req->kategori ?? '-' }}</td>
                    <td class="border border-blue-100 px-2 py-1 align-top">{{ $req->sub_kategori ?? '-' }}</td>
                    <td class="border border-blue-100 px-2 py-1 align-top text-center font-semibold">{{ $req->lot_no ?? '-' }}</td>
                    {{-- TGL. STR --}}
                    <td class="border border-blue-100 px-2 py-1 align-top text-center">
                        @if($tglStr)
                            <div>{{ $tglStr->format('Y-m-d') }}</div>
                            <div class="font-bold text-red-600 text-[10px]">{{ $hariStr }} hari</div>
                        @else -
                        @endif
                    </td>
                    <td class="border border-blue-100 px-2 py-1 align-top font-semibold">{{ $req->nama ?? '-' }}</td>
                    <td class="border border-blue-100 px-2 py-1 align-top text-center">{{ $req->kepemilikan ?? '-' }}</td>
                    <td class="border border-blue-100 px-2 py-1 align-top text-center">{{ $req->sales_agent ?? '-' }}</td>
                    <td class="border border-blue-100 px-2 py-1 align-top" style="max-width:200px;word-wrap:break-word;white-space:normal;">
                        {{ $req->descs ?? '-' }}
                    </td>
                    <td class="border border-blue-100 px-2 py-1 align-top">{{ $req->request_by ?? '-' }}</td>
                    <td class="border border-blue-100 px-2 py-1 align-top text-center">{{ $req->pelaporan_via ?? '-' }}</td>
                    <td class="border border-blue-100 px-2 py-1 align-top text-center">
                        @php $sc = match($req->status) {
                            'Pesan Diterima'         => 'background:#fef3c7;color:#92400e;border:1px solid #fcd34d;',
                            'Dalam Pengecekan'       => 'background:#dbeafe;color:#1e40af;border:1px solid #93c5fd;',
                            'Dalam Proses'           => 'background:#ffedd5;color:#c2410c;border:1px solid #fdba74;',
                            'Selesai'                => 'background:#dcfce7;color:#166534;border:1px solid #86efac;',
                            'Tidak Dapat Diaplikasi' => 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;',
                            default                  => 'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;',
                        }; @endphp
                        <span style="display:inline-block;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:600;white-space:nowrap;{{ $sc }}">{{ $req->status ?? '-' }}</span>
                    </td>
                    {{-- PICTURE --}}
                    <td class="border border-blue-100 px-2 py-1 align-top text-center" wire:click.stop>
                        @if($fotoUrl)
                            <a href="{{ $fotoUrl }}" target="_blank"
                               class="text-blue-600 hover:underline text-[10px] font-semibold leading-tight">
                                Lampiran<br>Foto
                            </a>
                        @endif
                    </td>
                    <td class="border border-blue-100 px-2 py-1 align-top text-[10px]"
                        style="white-space:pre-line;max-width:170px;word-wrap:break-word;">{{ $req->desc_status ?? '-' }}</td>
                    <td class="border border-blue-100 px-2 py-1 align-top text-center" wire:click.stop>
                        @if($linkedWo)
                            <div class="font-mono text-[10px] font-semibold" style="color:#1a6b3c;">{{ $linkedWo->no_wo }}</div>
                            <div class="text-[10px] text-gray-500">{{ $linkedWo->status_comp ?? '-' }}</div>
                        @else
                            <button wire:click.stop="buatWo({{ $req->id }})"
                                class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-semibold rounded-md text-white shadow-sm"
                                style="background:linear-gradient(135deg,#2563eb,#3b82f6);">
                                + Buat WO
                            </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="17" class="border border-gray-300 px-4 py-6 text-center text-gray-400">
                        Tidak ada data tenant request yang belum selesai.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="flex items-center justify-between mt-2 text-[11px] text-gray-600">
        <div class="flex items-center gap-1">
            @php
                $cur  = $requests->currentPage();
                $last = $requests->lastPage();
                $nums = collect();
                for ($p = 1; $p <= $last; $p++) {
                    if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
                }
                $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
                $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
                $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
            @endphp
            @if($requests->onFirstPage())
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
            @if($requests->hasMorePages())
                <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
                <button wire:click="setPage({{ $last }})" class="{{ $pBtn }}">›|</button>
            @else
                <span class="{{ $pDis }}">›</span>
                <span class="{{ $pDis }}">›|</span>
            @endif
            <span class="ml-3">
                <select wire:model.live="perPage" class="border border-gray-400 text-[11px] px-1 py-0.5 bg-white">
                    <option value="20">20</option><option value="50">50</option>
                </select>
            </span>
        </div>
        <span>View {{ $requests->firstItem() ?? 0 }}–{{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }}</span>
    </div>

    {{-- Selected info + action buttons --}}
    <div class="mt-3 flex items-center gap-2 flex-wrap">
        <div class="text-xs text-gray-500 mr-2">
            @if($selectedId && $selectedRequest)
                <span class="font-semibold text-blue-700">✔ {{ $selectedRequest->no_request }}</span>
                – {{ $selectedRequest->nama }}
            @else
                <span class="text-gray-400 italic">Pilih baris untuk aksi</span>
            @endif
        </div>

        <button wire:click="openInput"
            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded flex items-center gap-1">
            + Input
        </button>

        <button wire:click="openEdit" @if(!$selectedId) disabled @endif
            class="px-3 py-1.5 text-xs font-semibold rounded flex items-center gap-1"
            style="{{ $selectedId ? 'background:#f59e0b; color:#fff;' : 'background:#e5e7eb; color:#9ca3af; cursor:not-allowed;' }}">
            ✏ Edit / Upload
        </button>

        <button wire:click="closeRequest" @if(!$selectedId) disabled @endif
            onclick="return confirm('Tutup request ini sebagai selesai?')"
            class="px-3 py-1.5 text-xs font-semibold rounded flex items-center gap-1
                {{ $selectedId ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}">
            ✕ Close Request
        </button>

        <a href="{{ route('karyawan.cs.tenant-request-selesai') }}"
            class="px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white text-xs font-semibold rounded flex items-center gap-1">
            📊 Report Status
        </a>
    </div>
    <p class="mt-1 text-[10px] text-gray-400 italic">* Klik baris untuk memilih. Klik baris yang sama untuk batal pilih. Tombol aksi aktif saat baris dipilih.</p>

    {{-- ═══════════════════════════════════════════════════════════════
         PANEL: INPUT
         ═══════════════════════════════════════════════════════════════ --}}
    @if($panelMode === 'input')
    <div class="mt-4 border border-blue-300 rounded-lg bg-blue-50 p-4">
        <h3 class="text-sm font-bold text-blue-800 mb-3 uppercase tracking-wide">Input Tenant Request Baru</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            {{-- TGL REQUEST (ticking clock) --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">TGL REQUEST</label>
                <div x-data="{
                        time: '',
                        init() {
                            const fmt = () => {
                                const now = new Date();
                                const d = String(now.getDate()).padStart(2,'0');
                                const m = String(now.getMonth()+1).padStart(2,'0');
                                const y = now.getFullYear();
                                const h = String(now.getHours()).padStart(2,'0');
                                const mi = String(now.getMinutes()).padStart(2,'0');
                                this.time = d+'-'+m+'-'+y+' '+h+':'+mi;
                            };
                            fmt();
                            setInterval(fmt, 30000);
                        }
                    }"
                    class="px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-mono text-green-700 font-semibold select-none"
                    x-text="time"></div>
            </div>

            {{-- NO. UNIT --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">NO. UNIT <span class="text-red-500">*</span></label>
                <input wire:model.lazy="formLotNo" type="text" placeholder="MP/18/AN"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs uppercase focus:outline-none focus:ring-1 focus:ring-blue-400">
                @error('formLotNo') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
            </div>

            {{-- NAMA PELAPOR --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">NAMA PELAPOR</label>
                <input wire:model="formNama" type="text" placeholder="Auto-isi dari No. Unit"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs uppercase bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-400">
            </div>

            {{-- KEPEMILIKAN --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">KEPEMILIKAN</label>
                <input wire:model="formKepemilikan" type="text" placeholder="Owner / Tenant"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-400">
            </div>

            {{-- SALES AGENT --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">SALES AGENT</label>
                <input wire:model="formSalesAgent" type="text" placeholder="Nama sales agent"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs uppercase focus:outline-none focus:ring-1 focus:ring-blue-400">
            </div>

            {{-- PELAPORAN VIA --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">PELAPORAN VIA <span class="text-red-500">*</span></label>
                <select wire:model="formPelaporanVia"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="">-- Pilih --</option>
                    @foreach(\App\Models\TenantRequest::pelaporanViaOptions() as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
                @error('formPelaporanVia') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
            </div>

            {{-- KATEGORI --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">KATEGORI <span class="text-red-500">*</span></label>
                <select wire:model="formKategori"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="">-- Pilih --</option>
                    @foreach(\App\Models\TenantRequest::kategoriOptions() as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
                @error('formKategori') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
            </div>

            {{-- SUB KATEGORI --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">SUB KATEGORI</label>
                <select wire:model="formSubKategori"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="">-- Pilih --</option>
                    @foreach(\App\Models\TenantRequest::subKategoriOptions() as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            {{-- REQUEST BY --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">REQUEST BY</label>
                <input wire:model="formRequestBy" type="text" placeholder="Nama peminta (kosong = nama pelapor)"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs uppercase focus:outline-none focus:ring-1 focus:ring-blue-400">
            </div>

            {{-- BERULANG --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">BERULANG</label>
                <select wire:model="formBerulang"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="Tidak">Tidak</option>
                    <option value="Ya">Ya</option>
                </select>
            </div>

            {{-- TGL. STR (Start Problem) --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">TGL. STR (Mulai Masalah)</label>
                <input wire:model="formTglStr" type="date"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
            </div>

            {{-- STATUS --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">STATUS</label>
                <select wire:model="formStatus"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                    @foreach(\App\Models\TenantRequest::statusOptions() as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            {{-- KETERANGAN --}}
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">KETERANGAN / KELUHAN <span class="text-red-500">*</span></label>
                <textarea wire:model="formDescs" rows="3" placeholder="Deskripsi keluhan atau permintaan..."
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400 resize-none"></textarea>
                @error('formDescs') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
            </div>

            {{-- DESC STATUS --}}
            <div class="md:col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1">DESC STATUS</label>
                <textarea wire:model="formDescStatus" rows="3" placeholder="Keterangan status awal..."
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400 resize-none"></textarea>
            </div>
        </div>

        {{-- Save / Cancel --}}
        <div class="flex gap-2 mt-4">
            <button wire:click="saveInput"
                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded">
                💾 Simpan
            </button>
            <button wire:click="closePanel"
                class="px-4 py-1.5 bg-gray-400 hover:bg-gray-500 text-white text-xs font-semibold rounded">
                ✕ Batal
            </button>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         PANEL: EDIT / UPLOAD
         ═══════════════════════════════════════════════════════════════ --}}
    @if($panelMode === 'edit' && $selectedRequest)
    <div class="mt-4 border border-yellow-300 rounded-lg bg-yellow-50 p-4">
        <h3 class="text-sm font-bold text-yellow-800 mb-3 uppercase tracking-wide">
            Edit / Upload – {{ $selectedRequest->no_request }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            {{-- Read-only info --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">No. Unit</label>
                <div class="px-2 py-1.5 bg-gray-100 border border-gray-200 rounded text-xs font-semibold">
                    {{ $selectedRequest->lot_no ?? '-' }}
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Nama Pelapor</label>
                <div class="px-2 py-1.5 bg-gray-100 border border-gray-200 rounded text-xs">
                    {{ $selectedRequest->nama ?? '-' }}
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Kategori / Sub</label>
                <div class="px-2 py-1.5 bg-gray-100 border border-gray-200 rounded text-xs">
                    {{ $selectedRequest->kategori ?? '-' }} / {{ $selectedRequest->sub_kategori ?? '-' }}
                </div>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Keterangan</label>
                <div class="px-2 py-1.5 bg-gray-100 border border-gray-200 rounded text-xs whitespace-pre-line">
                    {{ $selectedRequest->descs ?? '-' }}
                </div>
            </div>

            {{-- Editable: TGL Verifikasi --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">TGL Verifikasi</label>
                <input wire:model="editTglVerifikasi" type="date"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-yellow-400">
            </div>

            {{-- Editable: Status --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select wire:model="editStatus"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-yellow-400">
                    @foreach(\App\Models\TenantRequest::statusOptions() as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Editable: Desc Status --}}
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Desc Status (Update Progres)</label>
                <textarea wire:model="editDescStatus" rows="3"
                    placeholder="Catatan status / progress terbaru..."
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-yellow-400 resize-none"></textarea>
            </div>

            {{-- Upload File --}}
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Upload Foto / Dokumen</label>
                @if($selectedRequest && $selectedRequest->foto)
                <div class="mb-2 flex items-center gap-2">
                    <span class="text-xs text-green-700 font-semibold">✔ Sudah ada lampiran</span>
                    <a href="{{ asset('storage/' . $selectedRequest->foto) }}" target="_blank"
                        class="text-xs text-blue-600 underline">Lihat file lama</a>
                </div>
                @endif
                <input wire:model="editFoto" type="file" accept="image/*,.pdf"
                    class="block w-full text-xs text-gray-600 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-yellow-200 file:text-yellow-800 hover:file:bg-yellow-300">
                @error('editFoto') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-400 mt-1">JPG/PNG/PDF, max 5MB. Upload baru akan menggantikan foto lama.</p>
                @if($editFoto)
                <p class="text-xs text-blue-600 mt-1">✔ File dipilih: {{ $editFoto->getClientOriginalName() }}</p>
                @endif
            </div>
        </div>

        <div class="flex gap-2">
            <button wire:click="saveEdit"
                class="px-4 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-semibold rounded">
                💾 Simpan Perubahan
            </button>
            <button wire:click="closePanel"
                class="px-4 py-1.5 bg-gray-400 hover:bg-gray-500 text-white text-xs font-semibold rounded">
                ✕ Tutup
            </button>
        </div>
    </div>
    @endif

    {{-- Note --}}
    <p class="mt-3 text-xs text-gray-400 italic">
        * Klik baris untuk memilih. Klik baris yang sama untuk batal pilih. Tombol aksi aktif saat baris dipilih.
    </p>
</div>

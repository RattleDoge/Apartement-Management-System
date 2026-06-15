<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\InOutPermit;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination, WithFileUploads;

    // UI state
    public ?string $panelMode  = null;
    public int     $selectedId = 0;
    public int     $perPage    = 20;

    // Per-column filters (f prefix, not form prefix)
    public string $fNomor       = '';
    public string $fUnit        = '';
    public string $fTenantName  = '';
    public string $fDate        = '';
    public string $fTanggalIjin = '';
    public string $fJam         = '';
    public string $fJenis       = '';
    public string $fDescs       = '';
    public string $fRequestBy   = '';
    public string $fRequestVia  = '';
    public string $fStatus      = '';

    // Form props for Input / Edit panel
    public string $formUnit        = '';
    public string $formTenantName  = '';
    public string $formTanggal     = '';
    public string $formTanggalIjin = '';
    public string $formJam         = '';
    public string $formJenis       = 'Masuk';
    public string $formDescs       = '';
    public string $formRequestBy   = '';
    public string $formRequestVia  = 'aplikasi';
    public        $formFoto        = null;

    // Feedback
    public string $successMsg = '';
    public string $errorMsg   = '';

    // ─── Lifecycle ───────────────────────────────────────────────────────────

    public function updated(string $prop): void
    {
        if (str_starts_with($prop, 'f') && !str_starts_with($prop, 'form')) {
            $this->resetPage();
        }
    }

    // ─── Data ────────────────────────────────────────────────────────────────

    public function with(): array
    {
        $q = InOutPermit::query()->orderByDesc('id');

        if ($this->fNomor)       $q->where('nomor',        'like', "%{$this->fNomor}%");
        if ($this->fUnit)        $q->where('unit',         'like', "%{$this->fUnit}%");
        if ($this->fTenantName)  $q->where('tenant_name',  'like', "%{$this->fTenantName}%");
        if ($this->fDate)        $q->whereDate('tanggal',         $this->fDate);
        if ($this->fTanggalIjin) $q->whereDate('tanggal_ijin',    $this->fTanggalIjin);
        if ($this->fJam)         $q->where('jam',          'like', "%{$this->fJam}%");
        if ($this->fJenis)       $q->where('jenis',                $this->fJenis);
        if ($this->fDescs)       $q->where('descs',        'like', "%{$this->fDescs}%");
        if ($this->fRequestBy)   $q->where('request_by',   'like', "%{$this->fRequestBy}%");
        if ($this->fRequestVia)  $q->where('request_via',  'like', "%{$this->fRequestVia}%");
        if ($this->fStatus)      $q->where('status',       'like', "%{$this->fStatus}%");

        // FIFO: antrian permit yang belum diproses, urut dari yang paling lama diterima
        $fifoQueue = \App\Models\InOutPermit::where('status', 'Pesan Diterima')
            ->orderBy('created_at')
            ->pluck('id')
            ->values()
            ->mapWithKeys(fn($id, $pos) => [$id => $pos + 1])
            ->toArray();

        return ['permits' => $q->paginate($this->perPage), 'fifoQueue' => $fifoQueue];
    }

    // ─── Panel actions ────────────────────────────────────────────────────────

    public function openInput(): void
    {
        $this->resetFormProps();
        $this->formTanggal     = now()->format('Y-m-d');
        $this->formTanggalIjin = now()->format('Y-m-d');
        $this->formJam         = now()->format('H:i');
        $this->panelMode  = 'input';
        $this->successMsg = '';
        $this->errorMsg   = '';
        $this->resetValidation();
    }

    public function openEdit(): void
    {
        if (!$this->selectedId) { return; }
        $p = InOutPermit::findOrFail($this->selectedId);
        $this->formUnit        = $p->unit ?? '';
        $this->formTenantName  = $p->tenant_name ?? '';
        $this->formTanggal     = $p->tanggal?->format('Y-m-d') ?? '';
        $this->formTanggalIjin = $p->tanggal_ijin?->format('Y-m-d') ?? '';
        $this->formJam         = $p->jam ? substr($p->jam, 0, 5) : '';
        $this->formJenis       = $p->jenis ?? 'Masuk';
        $this->formDescs       = $p->descs ?? '';
        $this->formRequestBy   = $p->request_by ?? '';
        $this->formRequestVia  = $p->request_via ?? 'aplikasi';
        $this->formFoto        = null;
        $this->panelMode  = 'edit';
        $this->successMsg = '';
        $this->errorMsg   = '';
        $this->resetValidation();
    }

    public function closePanel(): void
    {
        $this->panelMode  = null;
        $this->successMsg = '';
        $this->errorMsg   = '';
        $this->resetFormProps();
        $this->resetValidation();
    }

    // ─── CRUD ────────────────────────────────────────────────────────────────

    public function saveInput(): void
    {
        $this->validate([
            'formUnit'        => 'required',
            'formTanggal'     => 'required|date',
            'formTanggalIjin' => 'required|date',
            'formJenis'       => 'required|in:Masuk,Keluar',
            'formDescs'       => 'required',
            'formFoto'        => 'nullable|image|max:4096',
        ]);

        $nomor = $this->generateNomor();
        $foto  = null;
        if ($this->formFoto) {
            $foto = $this->formFoto->store('in-out-foto', 'public');
        }

        InOutPermit::create([
            'nomor'        => $nomor,
            'unit'         => $this->formUnit,
            'tenant_name'  => $this->formTenantName ?: null,
            'tanggal'      => $this->formTanggal,
            'tanggal_ijin' => $this->formTanggalIjin,
            'jam'          => $this->formJam ?: null,
            'jenis'        => $this->formJenis,
            'descs'        => $this->formDescs,
            'request_by'   => $this->formRequestBy ?: null,
            'request_via'  => $this->formRequestVia ?: null,
            'status'       => 'Pesan Diterima',
            'foto'         => $foto,
            'input_by'     => auth()->user()?->name,
        ]);

        $this->successMsg = "Permit {$nomor} berhasil dibuat.";
        $this->panelMode  = null;
        $this->resetFormProps();
    }

    public function saveEdit(): void
    {
        $this->validate([
            'formUnit'        => 'required',
            'formTanggal'     => 'required|date',
            'formTanggalIjin' => 'required|date',
            'formJenis'       => 'required|in:Masuk,Keluar',
            'formDescs'       => 'required',
            'formFoto'        => 'nullable|image|max:4096',
        ]);

        $permit = InOutPermit::findOrFail($this->selectedId);
        $data   = [
            'unit'         => $this->formUnit,
            'tenant_name'  => $this->formTenantName ?: null,
            'tanggal'      => $this->formTanggal,
            'tanggal_ijin' => $this->formTanggalIjin,
            'jam'          => $this->formJam ?: null,
            'jenis'        => $this->formJenis,
            'descs'        => $this->formDescs,
            'request_by'   => $this->formRequestBy ?: null,
            'request_via'  => $this->formRequestVia ?: null,
        ];
        if ($this->formFoto) {
            $data['foto'] = $this->formFoto->store('in-out-foto', 'public');
        }

        $permit->update($data);
        $this->successMsg = "Data berhasil diupdate.";
        $this->panelMode  = null;
        $this->resetFormProps();
    }

    // ─── Approval flow ───────────────────────────────────────────────────────

    public function approve(int $id): void
    {
        $permit = InOutPermit::findOrFail($id);
        $by     = auth()->user()?->name ?? 'System';
        $now    = now();

        match ($permit->status) {
            'Pesan Diterima' => $permit->update([
                'status'         => 'Approve by Customer Service',
                'approved_cs_by' => $by,
                'approved_cs_at' => $now,
            ]),
            'Approve by Customer Service' => $permit->update([
                'status'         => 'Approve by FA',
                'approved_fa_by' => $by,
                'approved_fa_at' => $now,
            ]),
            'Approve by FA' => $permit->update([
                'status'          => 'Approve by Security',
                'approved_sec_by' => $by,
                'approved_sec_at' => $now,
            ]),
            default => null,
        };
    }

    public function unApprove(int $id): void
    {
        $permit = InOutPermit::findOrFail($id);
        if ($permit->isPending()) {
            $permit->update(['status' => 'Tidak Disetujui']);
        }
    }

    // ─── Row selection / InActive ─────────────────────────────────────────────

    public function selectRow(int $id): void
    {
        $this->selectedId = ($this->selectedId === $id) ? 0 : $id;
    }

    public function toggleActive(): void
    {
        if (!$this->selectedId) { return; }
        $permit = InOutPermit::findOrFail($this->selectedId);
        $permit->update(['is_active' => !$permit->is_active]);
        $this->selectedId = 0;
        $this->successMsg = 'Status aktif berhasil diubah.';
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function resetFormProps(): void
    {
        $this->formUnit        = '';
        $this->formTenantName  = '';
        $this->formTanggal     = '';
        $this->formTanggalIjin = '';
        $this->formJam         = '';
        $this->formJenis       = 'Masuk';
        $this->formDescs       = '';
        $this->formRequestBy   = '';
        $this->formRequestVia  = 'aplikasi';
        $this->formFoto        = null;
    }

    private function generateNomor(): string
    {
        $romans = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $roman  = $romans[now()->month - 1];
        $year   = now()->year;

        $last = InOutPermit::orderByDesc('id')->first();
        $seq  = 1;
        if ($last && preg_match('/KMB(\d+)/', $last->nomor, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return "KMB{$seq}/{$roman}/{$year}-MAP";
    }
} ?>

<div class="flex gap-2 h-full p-2">

    {{-- ── Main list panel ─────────────────────────────────────────────────── --}}
    <div class="flex flex-col flex-1 min-w-0">

        {{-- Title + FIFO badge + Input button --}}
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-2">
                <div class="text-white text-sm font-bold px-3 py-1.5 rounded-md" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                    List In &amp; Out
                </div>
                <span class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded">FIFO</span>
                <span class="text-[10px] text-gray-500">Permit diproses berurutan dari yang paling lama diterima</span>
            </div>
            <button wire:click="openInput"
                class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1 cursor-pointer">
                + Input
            </button>
        </div>

        {{-- Success message --}}
        @if($successMsg)
            <div class="bg-green-100 border border-green-400 text-green-700 text-xs px-3 py-1 mb-1 flex justify-between">
                <span>{{ $successMsg }}</span>
                <button wire:click="$set('successMsg','')" class="font-bold">✕</button>
            </div>
        @endif

        {{-- Table --}}
        <div class="overflow-auto flex-1 border border-blue-200 rounded-lg">
            <table class="w-full text-xs border-collapse min-w-max">
                <thead>
                    {{-- Column headers --}}
                    <tr class="text-center sticky top-0 z-10" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%); color:white;">
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">NO</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap" title="Antrian FIFO">ANTRIAN</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">NOMOR</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">UNIT</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">TENANT NAME</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">DATE</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">TANGGAL IJIN</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">JAM</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">JENIS</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">DESCS</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">REQUEST BY</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">REQUEST VIA</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">STATUS</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">FOTO</th>
                        <th class="border border-blue-400 px-1 py-1.5 whitespace-nowrap">ACTION</th>
                    </tr>
                    {{-- Per-column filter row --}}
                    <tr class="bg-white sticky top-[29px] z-10">
                        <th class="border border-gray-300 p-0"></th>
                        <th class="border border-gray-300 p-0"></th>
                        <th class="border border-gray-300 p-0">
                            <input wire:model.live.debounce.300ms="fNomor"
                                class="w-full text-xs px-1 py-0.5 border-0 focus:outline-none" />
                        </th>
                        <th class="border border-gray-300 p-0">
                            <input wire:model.live.debounce.300ms="fUnit"
                                class="w-full text-xs px-1 py-0.5 border-0 focus:outline-none" />
                        </th>
                        <th class="border border-gray-300 p-0">
                            <input wire:model.live.debounce.300ms="fTenantName"
                                class="w-full text-xs px-1 py-0.5 border-0 focus:outline-none" />
                        </th>
                        <th class="border border-gray-300 p-0">
                            <input type="date" wire:model.live="fDate"
                                class="w-full text-xs px-1 py-0.5 border-0 focus:outline-none" />
                        </th>
                        <th class="border border-gray-300 p-0">
                            <input type="date" wire:model.live="fTanggalIjin"
                                class="w-full text-xs px-1 py-0.5 border-0 focus:outline-none" />
                        </th>
                        <th class="border border-gray-300 p-0">
                            <input wire:model.live.debounce.300ms="fJam"
                                class="w-full text-xs px-1 py-0.5 border-0 focus:outline-none" />
                        </th>
                        <th class="border border-gray-300 p-0">
                            <select wire:model.live="fJenis"
                                class="w-full text-xs px-1 py-0.5 border-0 bg-white focus:outline-none">
                                <option value=""></option>
                                @foreach(\App\Models\InOutPermit::jenisOptions() as $j)
                                    <option value="{{ $j }}">{{ $j }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="border border-gray-300 p-0">
                            <input wire:model.live.debounce.300ms="fDescs"
                                class="w-full text-xs px-1 py-0.5 border-0 focus:outline-none" />
                        </th>
                        <th class="border border-gray-300 p-0">
                            <input wire:model.live.debounce.300ms="fRequestBy"
                                class="w-full text-xs px-1 py-0.5 border-0 focus:outline-none" />
                        </th>
                        <th class="border border-gray-300 p-0">
                            <select wire:model.live="fRequestVia"
                                class="w-full text-xs px-1 py-0.5 border-0 bg-white focus:outline-none">
                                <option value=""></option>
                                @foreach(\App\Models\InOutPermit::requestViaOptions() as $v)
                                    <option value="{{ $v }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="border border-gray-300 p-0">
                            <select wire:model.live="fStatus"
                                class="w-full text-xs px-1 py-0.5 border-0 bg-white focus:outline-none">
                                <option value=""></option>
                                @foreach(\App\Models\InOutPermit::statusOptions() as $s)
                                    <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="border border-gray-300 p-0"></th>
                        <th class="border border-gray-300 p-0"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permits as $i => $permit)
                        @php
                            $statusColor = match($permit->status) {
                                'Approve by Customer Service' => 'text-blue-600',
                                'Approve by FA'               => 'text-purple-600',
                                'Approve by Security'         => 'text-green-600 font-semibold',
                                'Tidak Disetujui'             => 'text-red-500',
                                default                       => 'text-orange-500',
                            };
                            $rowBg = $selectedId === $permit->id
                                ? 'bg-blue-100'
                                : ($i % 2 === 0 ? 'bg-white hover:bg-gray-50' : 'bg-gray-50 hover:bg-gray-100');
                        @endphp
                        <tr wire:click="selectRow({{ $permit->id }})"
                            class="cursor-pointer {{ $rowBg }}
                                   {{ !$permit->is_active ? 'opacity-50' : '' }}">
                            <td class="border border-gray-200 px-1 py-0.5 text-center text-gray-500">
                                {{ $permits->firstItem() + $i }}
                            </td>
                            {{-- FIFO antrian --}}
                            <td class="border border-gray-200 px-1 py-0.5 text-center">
                                @if(isset($fifoQueue[$permit->id]))
                                    @php $q = $fifoQueue[$permit->id]; @endphp
                                    <span class="font-bold text-[10px] px-1 py-0.5 rounded
                                        {{ $q === 1 ? 'bg-green-500 text-white' : ($q <= 3 ? 'bg-yellow-400 text-gray-800' : 'bg-gray-200 text-gray-600') }}">
                                        #{{ $q }}
                                    </span>
                                @else
                                    <span class="text-gray-300 text-[10px]">✓</span>
                                @endif
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 whitespace-nowrap font-mono text-xs">
                                {{ $permit->nomor }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 whitespace-nowrap font-semibold">
                                {{ $permit->unit }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5">{{ $permit->tenant_name }}</td>
                            <td class="border border-gray-200 px-1 py-0.5 whitespace-nowrap">
                                {{ $permit->tanggal?->format('Y-m-d') }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 whitespace-nowrap">
                                {{ $permit->tanggal_ijin?->format('Y-m-d') }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 text-center whitespace-nowrap">
                                {{ $permit->jam ? substr($permit->jam, 0, 5) : '' }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 text-center font-semibold
                                {{ $permit->jenis === 'Keluar' ? 'text-orange-500' : 'text-green-600' }}">
                                {{ $permit->jenis }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 max-w-[180px] truncate"
                                title="{{ $permit->descs }}">
                                {{ $permit->descs }}
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5">{{ $permit->request_by }}</td>
                            <td class="border border-gray-200 px-1 py-0.5 text-center">{{ $permit->request_via }}</td>
                            <td class="border border-gray-200 px-1 py-0.5 whitespace-nowrap {{ $statusColor }}">
                                {{ $permit->status }}
                                @if($permit->status === 'Approve by Customer Service' && $permit->approved_cs_by)
                                    <div class="text-gray-400 text-[10px]">{{ $permit->approved_cs_by }}</div>
                                @elseif($permit->status === 'Approve by FA' && $permit->approved_fa_by)
                                    <div class="text-gray-400 text-[10px]">{{ $permit->approved_fa_by }}</div>
                                @elseif($permit->status === 'Approve by Security' && $permit->approved_sec_by)
                                    <div class="text-gray-400 text-[10px]">{{ $permit->approved_sec_by }}</div>
                                @endif
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 text-center">
                                @if($permit->foto)
                                    <a href="{{ Storage::url($permit->foto) }}" target="_blank"
                                       wire:click.stop class="text-blue-500 underline text-xs whitespace-nowrap">
                                        Lampiran Foto
                                    </a>
                                @endif
                            </td>
                            <td class="border border-gray-200 px-1 py-0.5 text-center whitespace-nowrap">
                                @if($permit->isPending())
                                    <button wire:click.stop="approve({{ $permit->id }})"
                                        class="bg-teal-500 hover:bg-teal-600 text-white text-xs px-2 py-0.5 cursor-pointer mr-0.5">
                                        Approve
                                    </button>
                                    <button wire:click.stop="unApprove({{ $permit->id }})"
                                        class="bg-gray-400 hover:bg-gray-500 text-white text-xs px-2 py-0.5 cursor-pointer">
                                        Unapp
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center text-gray-400 py-4 text-xs">
                                Tidak ada data
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Bottom toolbar + pagination --}}
        <div class="flex items-center justify-between mt-1 px-1 flex-wrap gap-1">

            {{-- Edit / InActive toolbar --}}
            <div class="flex items-center gap-3 text-xs">
                <button wire:click="openEdit"
                    class="{{ $selectedId ? 'text-blue-600 hover:underline cursor-pointer' : 'text-gray-300 cursor-default' }}">
                    ✏ Edit
                </button>
                <button wire:click="toggleActive"
                    class="{{ $selectedId ? 'text-red-500 hover:underline cursor-pointer' : 'text-gray-300 cursor-default' }}">
                    InActive
                </button>
            </div>

            {{-- Reference-style pagination --}}
            @php
                $cur  = $permits->currentPage();
                $last = $permits->lastPage();
                $nums = collect();
                for ($p = 1; $p <= $last; $p++) {
                    if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
                }
                $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
                $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
                $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
            @endphp
            <div class="flex items-center gap-1 text-xs text-gray-600 flex-wrap">
                @if($permits->onFirstPage())
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
                @if($permits->hasMorePages())
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
                    <option value="100">100</option>
                </select>

                <span class="ml-1 text-gray-500">
                    View {{ $permits->firstItem() ?? 0 }}–{{ $permits->lastItem() ?? 0 }}
                    of {{ $permits->total() }}
                </span>
            </div>
        </div>

        {{-- Approval legend note --}}
        <div class="mt-2 text-[10px] text-gray-500 border-t border-gray-200 pt-1">
            <span class="font-semibold">Alur Approval:</span>
            <span class="text-orange-500 ml-1">Pesan Diterima</span> →
            <span class="text-blue-600">CS</span> →
            <span class="text-purple-600">FA</span> →
            <span class="text-green-600">Security</span>
            &nbsp;|&nbsp; CS: verifikasi data hunian &nbsp;|&nbsp; FA: cek tunggakan WE &amp; IPL &nbsp;|&nbsp; Security: cek fisik barang
        </div>
    </div>

    {{-- ── Side panel (Input / Edit) ───────────────────────────────────────── --}}
    @if($panelMode === 'input' || $panelMode === 'edit')
        <div class="w-72 border border-gray-300 bg-gray-50 flex flex-col text-xs shrink-0">

            {{-- Panel header --}}
            <div class="flex items-center justify-between text-white px-3 py-1.5" style="background: linear-gradient(135deg, #1e3a8a, #2563eb);">
                <span class="font-bold">
                    {{ $panelMode === 'input' ? 'Input Baru' : 'Edit Data' }}
                </span>
                <button wire:click="closePanel" class="text-white hover:text-gray-200 font-bold">✕</button>
            </div>

            <div class="p-3 flex flex-col gap-2 overflow-y-auto flex-1">

                @if($errorMsg)
                    <p class="text-red-500 text-xs bg-red-50 border border-red-200 px-2 py-1">{{ $errorMsg }}</p>
                @endif

                {{-- Unit --}}
                <div>
                    <label class="font-semibold text-gray-600">Unit / Lot No <span class="text-red-500">*</span></label>
                    <input wire:model="formUnit" type="text"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400"
                        placeholder="contoh: MP/5/BV" />
                    @error('formUnit') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                {{-- Tenant Name --}}
                <div>
                    <label class="font-semibold text-gray-600">Tenant Name</label>
                    <input wire:model="formTenantName" type="text"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400"
                        placeholder="Nama tenant (opsional)" />
                </div>

                {{-- Jenis --}}
                <div>
                    <label class="font-semibold text-gray-600">Jenis <span class="text-red-500">*</span></label>
                    <select wire:model="formJenis"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 bg-white focus:outline-none focus:border-blue-400">
                        @foreach(\App\Models\InOutPermit::jenisOptions() as $j)
                            <option value="{{ $j }}">{{ $j }}</option>
                        @endforeach
                    </select>
                    @error('formJenis') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                {{-- Tanggal --}}
                <div>
                    <label class="font-semibold text-gray-600">Tanggal Request <span class="text-red-500">*</span></label>
                    <input wire:model="formTanggal" type="date"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400" />
                    @error('formTanggal') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                {{-- Tanggal Ijin --}}
                <div>
                    <label class="font-semibold text-gray-600">Tanggal Ijin <span class="text-red-500">*</span></label>
                    <input wire:model="formTanggalIjin" type="date"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400" />
                    @error('formTanggalIjin') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                {{-- Jam --}}
                <div>
                    <label class="font-semibold text-gray-600">Jam</label>
                    <input wire:model="formJam" type="time"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400" />
                </div>

                {{-- Deskripsi barang --}}
                <div>
                    <label class="font-semibold text-gray-600">Deskripsi Barang <span class="text-red-500">*</span></label>
                    <textarea wire:model="formDescs" rows="3"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400 resize-y"
                        placeholder="Contoh: Kulkas 2 pintu, warna silver..."></textarea>
                    @error('formDescs') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                {{-- Request By --}}
                <div>
                    <label class="font-semibold text-gray-600">Request By</label>
                    <input wire:model="formRequestBy" type="text"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 focus:outline-none focus:border-blue-400"
                        placeholder="Nama pemohon" />
                </div>

                {{-- Request Via --}}
                <div>
                    <label class="font-semibold text-gray-600">Request Via</label>
                    <select wire:model="formRequestVia"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 bg-white focus:outline-none focus:border-blue-400">
                        <option value=""></option>
                        @foreach(\App\Models\InOutPermit::requestViaOptions() as $v)
                            <option value="{{ $v }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Foto lampiran --}}
                <div>
                    <label class="font-semibold text-gray-600">Lampiran Foto</label>
                    <input wire:model="formFoto" type="file" accept="image/*"
                        class="w-full border border-gray-300 px-2 py-1 mt-0.5 text-xs bg-white focus:outline-none" />
                    <div wire:loading wire:target="formFoto"
                        class="text-blue-500 text-[10px] mt-0.5">Mengupload...</div>
                    @error('formFoto') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                {{-- Submit button --}}
                <button
                    wire:click="{{ $panelMode === 'input' ? 'saveInput' : 'saveEdit' }}"
                    wire:loading.attr="disabled"
                    class="w-full bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-bold py-1.5 mt-1 cursor-pointer">
                    <span wire:loading.remove wire:target="{{ $panelMode === 'input' ? 'saveInput' : 'saveEdit' }}">
                        {{ $panelMode === 'input' ? 'Simpan' : 'Update' }}
                    </span>
                    <span wire:loading wire:target="{{ $panelMode === 'input' ? 'saveInput' : 'saveEdit' }}">
                        Menyimpan...
                    </span>
                </button>

            </div>
        </div>
    @endif

</div>

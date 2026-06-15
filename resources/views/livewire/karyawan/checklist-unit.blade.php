<?php

use App\Models\UnitChecklist;
use App\Models\Tenant;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination;

    public bool   $showPanel   = false;
    public ?int   $editId      = null;

    // LOT NO lookup step
    public string $fLotNo        = '';
    public string $fTenantName   = '';
    public bool   $lotResolved   = false;
    public string $lotError      = '';

    // Form fields
    public string $fChecklistDate      = '';
    public string $fDefect             = '';
    public string $fNoMtrWater         = '';
    public string $fCurrentRead        = '';
    public string $fFirstWaterInvoice  = '';

    public string $savedMsg = '';
    public string $search   = '';

    public function updatedSearch(): void { $this->resetPage(); }

    public function lookupTenant(): void
    {
        $this->lotError    = '';
        $this->fTenantName = '';
        $this->lotResolved = false;

        if (! $this->fLotNo) {
            $this->lotError = 'Masukkan Lot No terlebih dahulu.';
            return;
        }

        $tenant = Tenant::whereRaw('UPPER(unit_number) = ?', [strtoupper(trim($this->fLotNo))])
            ->with('user')
            ->first();

        if ($tenant) {
            $this->fTenantName = $tenant->user?->name ?? $tenant->unit_number;
            $this->lotResolved = true;
        } else {
            // Allow manual entry even if not found in system
            $this->fTenantName = '';
            $this->lotResolved = true;
            $this->lotError    = 'Unit tidak ditemukan di sistem. Nama tenant perlu diisi manual.';
        }
    }

    public function openAdd(): void
    {
        $this->resetForm();
        $this->fChecklistDate = now()->format('Y-m-d');
        $this->fFirstWaterInvoice = now()->addDays(6)->startOfMonth()->addMonth()->format('Y-m-d');
        $this->showPanel = true;
    }

    public function openEdit(int $id): void
    {
        $c = UnitChecklist::findOrFail($id);
        $this->editId              = $id;
        $this->fLotNo              = $c->lot_no;
        $this->fTenantName         = $c->tenant_name ?? '';
        $this->fChecklistDate      = $c->checklist_date->format('Y-m-d');
        $this->fDefect             = $c->defect ?? '';
        $this->fNoMtrWater         = $c->no_mtr_water ?? '';
        $this->fCurrentRead        = $c->current_read ?? '';
        $this->fFirstWaterInvoice  = $c->first_water_invoice?->format('Y-m-d') ?? '';
        $this->lotResolved         = true;
        $this->showPanel           = true;
    }

    public function save(): void
    {
        $this->validate([
            'fLotNo'             => 'required|string|max:50',
            'fTenantName'        => 'nullable|string|max:255',
            'fChecklistDate'     => 'required|date',
            'fDefect'            => 'nullable|string',
            'fNoMtrWater'        => 'nullable|string|max:50',
            'fCurrentRead'       => 'nullable|string|max:50',
            'fFirstWaterInvoice' => 'nullable|date',
        ]);

        $data = [
            'lot_no'              => strtoupper(trim($this->fLotNo)),
            'tenant_name'         => $this->fTenantName ? strtoupper($this->fTenantName) : null,
            'checklist_date'      => $this->fChecklistDate,
            'defect'              => $this->fDefect ?: null,
            'no_mtr_water'        => $this->fNoMtrWater ?: null,
            'current_read'        => $this->fCurrentRead ?: null,
            'first_water_invoice' => $this->fFirstWaterInvoice ?: null,
        ];

        if ($this->editId) {
            UnitChecklist::findOrFail($this->editId)->update($data);
            $this->savedMsg = 'Checklist berhasil diperbarui.';
        } else {
            UnitChecklist::create($data);
            $this->savedMsg = 'Checklist berhasil ditambahkan.';
        }

        $this->showPanel = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        UnitChecklist::findOrFail($id)->delete();
        $this->savedMsg = 'Checklist berhasil dihapus.';
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editId             = null;
        $this->fLotNo             = '';
        $this->fTenantName        = '';
        $this->lotResolved        = false;
        $this->lotError           = '';
        $this->fChecklistDate     = '';
        $this->fDefect            = '';
        $this->fNoMtrWater        = '';
        $this->fCurrentRead       = '';
        $this->fFirstWaterInvoice = '';
        $this->savedMsg           = '';
    }

    public function with(): array
    {
        $checklists = UnitChecklist::when($this->search, function ($q) {
                $q->where('lot_no', 'like', "%{$this->search}%")
                  ->orWhere('tenant_name', 'like', "%{$this->search}%");
            })
            ->orderByDesc('checklist_date')
            ->paginate(10);

        return compact('checklists');
    }
}
?>

<div class="p-5">

    {{-- Header --}}
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <span>LIST CHECKLIST UNIT</span>
            @if($savedMsg)
            <span class="text-xs font-normal bg-white/20 px-2 py-0.5 rounded">{{ $savedMsg }}</span>
            @endif
        </div>
    </div>
    <div class="flex items-center justify-end mb-4">
        <div class="flex items-center gap-3">
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="Cari lot / tenant..."
                   class="text-xs border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] w-48">
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-blue-200 rounded-lg overflow-x-auto shadow-sm">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-8">#</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-36">LOT NO</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">TENANT NAME</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-32">DATE</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-24">PRINT</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-24">AKSI</th>
                </tr>
                <tr class="bg-white">
                    <td class="border border-gray-200 px-1 py-0.5"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"></td>
                    <td class="border border-gray-200 px-1 py-0.5"></td>
                </tr>
            </thead>
            <tbody>
                @forelse($checklists as $i => $c)
                <tr class="border-b border-gray-100 hover:bg-[#f0f8f0] transition-colors">
                    <td class="border border-gray-100 px-3 py-1.5 text-center text-gray-500">
                        {{ ($checklists->currentPage() - 1) * $checklists->perPage() + $i + 1 }}
                    </td>
                    <td class="border border-gray-100 px-3 py-1.5 font-semibold text-[#1a5c2e] font-mono">{{ $c->lot_no }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-gray-700">{{ $c->tenant_name ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center font-mono">
                        {{ $c->checklist_date->format('d/m/Y') }}
                    </td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center">
                        <a href="{{ route('karyawan.checklist-unit.print', $c->id) }}" target="_blank"
                           class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-semibold rounded bg-slate-600 hover:bg-slate-700 text-white">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Print
                        </a>
                    </td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button wire:click="openEdit({{ $c->id }})"
                                    class="px-2 py-0.5 text-[10px] font-semibold rounded bg-amber-500 hover:bg-amber-600 text-white">
                                Edit
                            </button>
                            <button wire:click="delete({{ $c->id }})"
                                    wire:confirm="Hapus checklist unit {{ $c->lot_no }}?"
                                    class="px-2 py-0.5 text-[10px] font-semibold rounded bg-red-600 hover:bg-red-700 text-white">
                                Hapus
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-xs text-gray-400">
                        Belum ada data checklist unit.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination + action bar --}}
    <div class="mt-3 flex items-center justify-between border-t border-gray-200 pt-3">
        <button wire:click="openAdd"
                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded flex items-center gap-1">
            + Add
        </button>

        @if($checklists->hasPages())
        @php
            $cur  = $checklists->currentPage();
            $last = $checklists->lastPage();
            $nums = collect();
            for ($p = 1; $p <= $last; $p++) {
                if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
            }
            $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
            $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
            $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
        @endphp
        <div class="flex items-center gap-1">
            @if($checklists->onFirstPage())
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
            @if($checklists->hasMorePages())
                <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
                <button wire:click="setPage({{ $last }})" class="{{ $pBtn }}">›|</button>
            @else
                <span class="{{ $pDis }}">›</span>
                <span class="{{ $pDis }}">›|</span>
            @endif
        </div>
        @else
        <span class="text-xs text-gray-400">Total: {{ $checklists->total() }} record</span>
        @endif
    </div>

    {{-- ── Modal Form ── --}}
    @if($showPanel)
    <div class="fixed inset-0 z-50 flex items-center justify-center"
         style="background: rgba(0,0,0,0.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg mx-4 max-h-[92vh] overflow-y-auto">

            <div class="flex items-center justify-between px-5 py-3 border-b"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide">
                    {{ $editId ? 'Edit Checklist Unit' : 'Input Mode' }}
                </h3>
                <button wire:click="closePanel" class="text-white/70 hover:text-white text-lg leading-none">✕</button>
            </div>

            <form wire:submit="save" class="px-5 py-4 space-y-4">

                {{-- LOT NO lookup --}}
                <div class="flex items-start gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">LOT NO</label>
                        <div class="flex items-center gap-2">
                            <input wire:model="fLotNo" type="text"
                                   class="w-32 text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] font-mono uppercase"
                                   placeholder="e.g. MP/31/AC"
                                   {{ $editId ? 'readonly' : '' }}>
                            @if(! $editId)
                            <button type="button" wire:click="lookupTenant"
                                    class="px-3 py-1.5 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold">
                                Ok
                            </button>
                            @endif
                        </div>
                        @error('fLotNo') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                        @if($lotError)
                        <p class="text-amber-600 text-[10px] mt-0.5">{{ $lotError }}</p>
                        @endif
                    </div>

                    @if($lotResolved)
                    <div class="pt-5">
                        <span class="text-sm font-semibold text-gray-700">
                            Tenant Name :
                            @if($fTenantName)
                                <span class="text-[#1a5c2e]">{{ $fTenantName }}</span>
                            @else
                                <input wire:model="fTenantName" type="text"
                                       class="ml-1 text-sm border-b border-gray-400 px-1 focus:outline-none focus:border-[#1a5c2e] w-48"
                                       placeholder="Masukkan nama tenant">
                            @endif
                        </span>
                    </div>
                    @endif
                </div>

                @if($lotResolved)
                <div class="space-y-3 border-t border-gray-100 pt-3">

                    {{-- Checklist Date --}}
                    <div class="grid grid-cols-3 gap-3 items-center">
                        <label class="text-xs font-medium text-gray-600 text-right">Checklist Date</label>
                        <div class="col-span-2">
                            <input wire:model="fChecklistDate" type="date"
                                   class="text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            @error('fChecklistDate') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Defect --}}
                    <div class="grid grid-cols-3 gap-3 items-start">
                        <label class="text-xs font-medium text-gray-600 text-right pt-1.5">Defect</label>
                        <div class="col-span-2">
                            <textarea wire:model="fDefect" rows="3"
                                      class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] resize-none"></textarea>
                        </div>
                    </div>

                    {{-- No Mtr Water --}}
                    <div class="grid grid-cols-3 gap-3 items-center">
                        <label class="text-xs font-medium text-gray-600 text-right">No Mtr Water</label>
                        <div class="col-span-2">
                            <input wire:model="fNoMtrWater" type="text"
                                   class="w-48 text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                        </div>
                    </div>

                    {{-- Current Read --}}
                    <div class="grid grid-cols-3 gap-3 items-center">
                        <label class="text-xs font-medium text-gray-600 text-right">Current Read</label>
                        <div class="col-span-2">
                            <input wire:model="fCurrentRead" type="text"
                                   class="w-48 text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                        </div>
                    </div>

                    {{-- First Water Invoice --}}
                    <div class="grid grid-cols-3 gap-3 items-center">
                        <label class="text-xs font-medium text-gray-600 text-right">First Water Invoice</label>
                        <div class="col-span-2">
                            <input wire:model="fFirstWaterInvoice" type="date"
                                   class="text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                        </div>
                    </div>

                </div>
                @endif

                {{-- Buttons --}}
                <div class="flex justify-center gap-4 pt-2 pb-1 border-t border-gray-100">
                    @if($lotResolved)
                    <button type="submit"
                            class="px-8 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded font-semibold">
                        💾 Simpan
                    </button>
                    @endif
                    <button type="button" wire:click="closePanel"
                            class="px-8 py-2 bg-gray-400 hover:bg-gray-500 text-white text-sm rounded font-semibold">
                        ✕ Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

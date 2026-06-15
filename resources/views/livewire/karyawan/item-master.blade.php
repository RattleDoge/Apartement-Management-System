<?php

use App\Models\ItemMaster;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.karyawan')] class extends Component
{
    use WithPagination;

    // ── Filters ──────────────────────────────────────────────
    public string $fKode  = '';
    public string $fNama  = '';

    // ── Sort ─────────────────────────────────────────────────
    public string $sortField = 'kode';
    public string $sortDir   = 'asc';

    // ── Selection & panel ────────────────────────────────────
    public int     $selectedId = 0;
    public int     $perPage    = 100;
    public ?string $panelMode  = null;

    // ── Form ─────────────────────────────────────────────────
    public string $formKode     = '';
    public string $formNama     = '';
    public string $formSatuan   = '';
    public string $formHarga    = '';
    public string $formKategori = '';

    public function updated($prop): void
    {
        if (str_starts_with($prop, 'f') || $prop === 'perPage') {
            $this->resetPage();
        }
    }

    public function sort(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir   = 'asc';
        }
        $this->resetPage();
    }

    public function selectRow(int $id): void
    {
        $this->selectedId = ($this->selectedId === $id) ? 0 : $id;
        $this->panelMode  = null;
        $this->resetValidation();
    }

    public function openAdd(): void
    {
        $this->panelMode    = 'add';
        $this->formKode     = '';
        $this->formNama     = '';
        $this->formSatuan   = '';
        $this->formHarga    = '';
        $this->formKategori = '';
        $this->resetValidation();
    }

    public function openEdit(): void
    {
        if (! $this->selectedId) return;
        $item = ItemMaster::find($this->selectedId);
        if (! $item) return;
        $this->panelMode    = 'edit';
        $this->formKode     = $item->kode ?? '';
        $this->formNama     = $item->nama;
        $this->formSatuan   = $item->satuan;
        $this->formHarga    = (string) $item->harga;
        $this->formKategori = $item->kategori ?? '';
        $this->resetValidation();
    }

    public function closePanel(): void
    {
        $this->panelMode = null;
        $this->resetValidation();
    }

    public function saveItem(): void
    {
        $this->validate([
            'formNama'   => 'required|string|max:200',
            'formSatuan' => 'required|string|max:30',
            'formHarga'  => 'required|numeric|min:0',
        ], [
            'formNama.required'   => 'Description wajib diisi.',
            'formSatuan.required' => 'Satuan wajib diisi.',
            'formHarga.required'  => 'Price wajib diisi.',
            'formHarga.numeric'   => 'Price harus berupa angka.',
        ]);

        $data = [
            'kode'     => $this->formKode ?: null,
            'nama'     => $this->formNama,
            'satuan'   => $this->formSatuan,
            'harga'    => (int) $this->formHarga,
            'kategori' => $this->formKategori ?: null,
        ];

        if ($this->panelMode === 'edit' && $this->selectedId) {
            ItemMaster::findOrFail($this->selectedId)->update($data);
        } else {
            ItemMaster::create($data);
            $this->resetPage();
        }

        $this->closePanel();
    }

    public function with(): array
    {
        $items = ItemMaster::query()
            ->when($this->fKode, fn($q) => $q->where('kode', 'like', "%{$this->fKode}%"))
            ->when($this->fNama, fn($q) => $q->where('nama', 'like', "%{$this->fNama}%"))
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        return compact('items');
    }
}; ?>

@php
    $panelHeaderStyle = 'background: linear-gradient(135deg, #1e3a8a, #2563eb); color: white;';
    $panelHeaderClass = 'flex items-center justify-between px-3 py-1.5';
    $panelBtnClose    = 'w-5 h-5 rounded-full border border-white/60 flex items-center justify-center text-xs font-bold hover:bg-white/20 transition-colors leading-none';
    $fieldLabel       = 'text-right pr-3 text-gray-700 align-middle py-0.5 whitespace-nowrap';
    $inp              = 'border border-gray-400 px-2 py-0.5 text-[12px]';
@endphp

<div>
    <div class="px-3 py-3">

        {{-- Title --}}
        <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-2">
            <div class="px-3 py-2 text-white font-bold text-sm tracking-wide"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                WO ITEM MASTER FILE
            </div>
        </div>

        {{-- ── Table ── --}}
        <div class="border border-blue-200 overflow-x-auto rounded-lg shadow-sm" style="font-size:11px;">
            <table class="border-collapse w-full">
                <thead>
                    {{-- Column headers --}}
                    <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                        <th class="border border-blue-200 px-1 py-1.5 text-center" style="width:36px;">#</th>
                        <th class="border border-blue-200 px-2 py-1.5 text-center cursor-pointer select-none"
                            style="width:110px;"
                            wire:click="sort('kode')">
                            ITEM CODE
                            @if($sortField === 'kode')
                                {{ $sortDir === 'asc' ? '↑' : '↓' }}
                            @endif
                        </th>
                        <th class="border border-blue-200 px-2 py-1.5 text-center cursor-pointer select-none"
                            wire:click="sort('nama')">
                            DESCRIPTION
                            @if($sortField === 'nama')
                                {{ $sortDir === 'asc' ? '↑' : '↓' }}
                            @else
                                <span class="text-gray-400">↑</span>
                            @endif
                        </th>
                        <th class="border border-blue-200 px-2 py-1.5 text-center cursor-pointer select-none"
                            style="width:130px;"
                            wire:click="sort('harga')">
                            PRICE
                            @if($sortField === 'harga')
                                {{ $sortDir === 'asc' ? '↑' : '↓' }}
                            @endif
                        </th>
                    </tr>

                    {{-- Filter row --}}
                    <tr style="background-color:#f5f5f5;">
                        <th class="border border-gray-400 px-1 py-0.5"></th>
                        <th class="border border-gray-400 px-1 py-0.5">
                            <input wire:model.live.debounce.300ms="fKode" type="text"
                                   class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" />
                        </th>
                        <th class="border border-gray-400 px-1 py-0.5">
                            <input wire:model.live.debounce.300ms="fNama" type="text"
                                   class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" />
                        </th>
                        <th class="border border-gray-400 px-1 py-0.5"></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($items as $i => $item)
                    @php
                        $rowNo  = ($items->currentPage() - 1) * $items->perPage() + $i + 1;
                        $isSel  = $selectedId === $item->id;
                        $bgStyle = $isSel ? 'background-color:#d0e8ff;' : ($i % 2 === 0 ? 'background-color:#ffffff;' : 'background-color:#f9fdff;');
                    @endphp
                    <tr wire:click="selectRow({{ $item->id }})"
                        style="{{ $bgStyle }}"
                        class="cursor-pointer hover:opacity-80 transition-opacity">
                        <td class="border border-gray-300 px-1 py-0.5 text-center text-gray-500">{{ $rowNo }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 text-gray-700 font-medium">{{ $item->kode }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 text-blue-700 hover:underline cursor-pointer">
                            {{ $item->nama }}
                            @if ($item->kategori)
                            <span class="text-gray-400 text-[10px] ml-1">[{{ $item->kategori }}]</span>
                            @endif
                        </td>
                        <td class="border border-gray-300 px-2 py-0.5 text-right text-gray-700">
                            Rp {{ number_format($item->harga, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="border border-gray-300 px-4 py-6 text-center text-gray-400">
                            Tidak ada data item master.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Action Buttons & Pagination ── --}}
        <div class="flex items-center justify-between mt-1.5 text-[11px] text-gray-600">

            {{-- Left: Add / Edit --}}
            <div class="flex items-center gap-1.5">
                <button wire:click="openAdd"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded flex items-center gap-1">
                    + Add</button>
                <button wire:click="{{ $selectedId ? 'openEdit' : '' }}"
                        class="px-3 py-1.5 text-xs font-semibold rounded flex items-center gap-1"
                        style="{{ $selectedId ? 'background:#f59e0b; color:#fff;' : 'background:#e5e7eb; color:#9ca3af; cursor:not-allowed;' }}"
                        {{ !$selectedId ? 'disabled' : '' }}>
                    ✏ Edit</button>
            </div>

            {{-- Right: Pagination --}}
            @php
                $cur  = $items->currentPage();
                $last = $items->lastPage();
                $nums = collect();
                for ($p = 1; $p <= $last; $p++) {
                    if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
                }
                $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
                $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
                $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
            @endphp
            <div class="flex items-center gap-1">
                @if($items->onFirstPage())
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
                @if($items->hasMorePages())
                    <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
                    <button wire:click="setPage({{ $last }})" class="{{ $pBtn }}">›|</button>
                @else
                    <span class="{{ $pDis }}">›</span>
                    <span class="{{ $pDis }}">›|</span>
                @endif
                <span class="ml-2">
                    <select wire:model.live="perPage"
                            class="border border-gray-400 text-[11px] px-1 py-0.5 bg-white">
                        <option value="19">19</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </span>
                <span class="ml-2 text-gray-500">
                    View {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }}
                    of {{ $items->total() }}
                </span>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════
             PANEL: ADD / EDIT ITEM
        ════════════════════════════════════════════════════════ --}}
        @if ($panelMode !== null)
        <div class="mt-3 border border-gray-300 shadow-sm" style="max-width:560px;">
            <div class="{{ $panelHeaderClass }}" style="{{ $panelHeaderStyle }}">
                <span class="font-bold text-sm tracking-wide">
                    {{ $panelMode === 'edit' ? 'EDIT ITEM' : 'ADD ITEM' }}
                </span>
                <button wire:click="closePanel" class="{{ $panelBtnClose }}">✕</button>
            </div>
            <div class="bg-white px-5 py-4 text-[12px]">
                <table class="w-full" style="border-collapse:separate; border-spacing:0 6px;">
                    <colgroup><col style="width:130px;"><col></colgroup>

                    {{-- Item Code --}}
                    <tr>
                        <td class="{{ $fieldLabel }}">Item Code</td>
                        <td>
                            <input wire:model="formKode" type="text"
                                   placeholder="W01"
                                   style="text-transform:uppercase;"
                                   class="{{ $inp }} w-28" />
                            <span class="text-gray-400 text-[11px] ml-2">opsional</span>
                        </td>
                    </tr>

                    {{-- Description --}}
                    <tr>
                        <td class="{{ $fieldLabel }}">Description</td>
                        <td>
                            <input wire:model="formNama" type="text"
                                   class="{{ $inp }} w-80" />
                            @error('formNama')
                            <div class="text-red-500 text-[10px] mt-0.5">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>

                    {{-- Satuan --}}
                    <tr>
                        <td class="{{ $fieldLabel }}">Satuan</td>
                        <td>
                            <input wire:model="formSatuan" type="text"
                                   placeholder="pcs / M2 / kg / jam/orang"
                                   class="{{ $inp }} w-40" />
                            @error('formSatuan')
                            <div class="text-red-500 text-[10px] mt-0.5">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>

                    {{-- Price --}}
                    <tr>
                        <td class="{{ $fieldLabel }}">Price</td>
                        <td>
                            <div class="flex items-center gap-1">
                                <span class="text-gray-500 text-[12px]">Rp</span>
                                <input wire:model="formHarga" type="number" min="0" step="1"
                                       placeholder="150000"
                                       class="{{ $inp }} w-36 text-right" />
                            </div>
                            @if ($formHarga && is_numeric($formHarga))
                            <div class="text-gray-400 text-[11px] mt-0.5 ml-6">
                                = Rp {{ number_format((int)$formHarga, 0, ',', '.') }}
                            </div>
                            @endif
                            @error('formHarga')
                            <div class="text-red-500 text-[10px] mt-0.5">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>

                    {{-- Kategori --}}
                    <tr>
                        <td class="{{ $fieldLabel }}">Kategori</td>
                        <td>
                            <select wire:model="formKategori" class="{{ $inp }} w-52">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="CIVIL">Civil</option>
                                <option value="ELECTRICAL">Electrical</option>
                                <option value="PLUMBING">Plumbing</option>
                                <option value="MECHANICAL">Mechanical</option>
                                <option value="PAINTING">Painting</option>
                                <option value="ACCESS CARD">Access Card</option>
                                <option value="HVAC">HVAC</option>
                                <option value="GENERAL">General</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <div class="flex gap-4 mt-5 justify-center">
                    <button wire:click="saveItem" wire:loading.attr="disabled"
                            class="px-6 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveItem">💾 Simpan</span>
                        <span wire:loading wire:target="saveItem">Menyimpan...</span>
                    </button>
                    <button wire:click="closePanel"
                            class="px-6 py-1.5 bg-gray-400 hover:bg-gray-500 text-white text-xs font-semibold rounded">
                        ✕ Batal
                    </button>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

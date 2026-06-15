<?php

use App\Models\Facility;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithFileUploads;

    public bool $showPanel  = false;
    public ?int $editId     = null;
    public ?int $selectedId = null;

    public string  $fNama          = '';
    public ?int    $fMaxPengunjung  = null;
    public ?int    $fJumlahOrang   = null;
    public string  $fDurasi        = '01:00';
    public string  $fMaxTerlambat  = '00:30';
    public string  $fMinHadir      = '00:10';
    public string  $fOpen          = '08:00';
    public string  $fClose         = '18:00';
    public string  $fCheckBilling  = 'Tidak Aktif';
    public bool    $fIsBerbayar    = false;
    public string  $fBiaya         = '0';
    public mixed   $fIcon          = null;
    public string  $fTerms         = '';

    public string $savedMsg = '';
    public string $deleteConfirmId = '';

    public function selectRow(int $id): void
    {
        $this->selectedId = ($this->selectedId === $id) ? null : $id;
    }

    public function openAdd(): void
    {
        $this->resetForm();
        $this->showPanel = true;
    }

    public function openEdit(int $id): void
    {
        $f = Facility::findOrFail($id);
        $this->editId         = $id;
        $this->fNama          = $f->nama_fasilitas;
        $this->fMaxPengunjung = $f->max_pengunjung;
        $this->fJumlahOrang   = $f->jumlah_orang;
        $this->fDurasi        = $f->durasi;
        $this->fMaxTerlambat  = $f->max_terlambat;
        $this->fMinHadir      = $f->min_hadir;
        $this->fOpen          = $f->open_fasilitas;
        $this->fClose         = $f->close_fasilitas;
        $this->fCheckBilling  = $f->check_billing;
        $this->fIsBerbayar    = (bool) $f->is_berbayar;
        $this->fBiaya         = (string) ($f->biaya ?? 0);
        $this->fTerms         = $f->terms ?? '';
        $this->fIcon          = null;
        $this->showPanel      = true;
    }

    public function save(): void
    {
        $this->validate([
            'fNama'         => 'required|string|max:255',
            'fMaxPengunjung'=> 'nullable|integer|min:0',
            'fJumlahOrang'  => 'nullable|integer|min:0',
            'fDurasi'       => 'required|string',
            'fMaxTerlambat' => 'required|string',
            'fMinHadir'     => 'required|string',
            'fOpen'         => 'required|string',
            'fClose'        => 'required|string',
            'fCheckBilling' => 'required|in:Aktif,Tidak Aktif',
            'fBiaya'        => 'nullable|numeric|min:0',
            'fIcon'         => 'nullable|image|max:2048',
        ]);

        $data = [
            'nama_fasilitas'  => $this->fNama,
            'max_pengunjung'  => $this->fMaxPengunjung,
            'jumlah_orang'    => $this->fJumlahOrang,
            'durasi'          => $this->fDurasi,
            'max_terlambat'   => $this->fMaxTerlambat,
            'min_hadir'       => $this->fMinHadir,
            'open_fasilitas'  => $this->fOpen,
            'close_fasilitas' => $this->fClose,
            'check_billing'   => $this->fCheckBilling,
            'is_berbayar'     => $this->fIsBerbayar,
            'biaya'           => $this->fIsBerbayar ? (float) $this->fBiaya : 0,
            'terms'           => $this->fTerms ?: null,
        ];

        if ($this->fIcon) {
            if ($this->editId) {
                $old = Facility::find($this->editId)?->icon;
                if ($old) Storage::disk('public')->delete($old);
            }
            $data['icon'] = $this->fIcon->store('facility-icons', 'public');
        }

        if ($this->editId) {
            Facility::findOrFail($this->editId)->update($data);
            $this->savedMsg = 'Fasilitas berhasil diperbarui.';
        } else {
            Facility::create($data);
            $this->savedMsg = 'Fasilitas berhasil ditambahkan.';
        }

        $this->showPanel = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $f = Facility::findOrFail($id);
        if ($f->icon) Storage::disk('public')->delete($f->icon);
        $f->delete();
        $this->savedMsg = 'Fasilitas berhasil dihapus.';
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editId         = null;
        $this->selectedId     = null;
        $this->fNama          = '';
        $this->fMaxPengunjung = null;
        $this->fJumlahOrang   = null;
        $this->fDurasi        = '01:00';
        $this->fMaxTerlambat  = '00:30';
        $this->fMinHadir      = '00:10';
        $this->fOpen          = '08:00';
        $this->fClose         = '18:00';
        $this->fCheckBilling  = 'Tidak Aktif';
        $this->fIsBerbayar    = false;
        $this->fBiaya         = '0';
        $this->fIcon          = null;
        $this->fTerms         = '';
    }

    public function with(): array
    {
        $facilities = Facility::orderBy('nama_fasilitas')->get();
        /** @var \App\Models\User $user */
        $user     = auth()->user();
        $userDept = optional($user->karyawan)->departemen ?? '';
        $canEdit  = in_array($userDept, ['AM', 'CS']);
        return compact('facilities', 'canEdit');
    }
}
?>

<div class="p-5">

    {{-- Header --}}
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <span>DATA FASILITAS</span>
            @if($savedMsg)
            <span class="text-xs font-normal bg-white/20 px-2 py-0.5 rounded">{{ $savedMsg }}</span>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-blue-200 rounded-lg overflow-x-auto shadow-sm">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-6">#</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold min-w-36">FASILITAS</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-28">MAX PENGUNJUNG</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-24">JUMLAH ORANG</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">OPEN</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">CLOSE</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">DURATION</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-24">MAX TERLAMBAT</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">MIN HADIR</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-24">CHECK BILLING</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">BERBAYAR</th>
                    <th class="border border-blue-200 px-3 py-2 text-right font-semibold w-28">HARGA SEWA</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-16">ICON</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">TERMS AND CONDITIONS</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">AKSI</th>
                </tr>
                {{-- Filter row (visual only, matches ICMAP style) --}}
                <tr class="bg-white">
                    <td class="border border-gray-200 px-1 py-0.5"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50 text-gray-400" placeholder=""></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"></td>
                    <td class="border border-gray-200 px-1 py-0.5"></td>
                    <td class="border border-gray-200 px-1 py-0.5"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"></td>
                </tr>
            </thead>
            <tbody>
                @forelse($facilities as $i => $f)
                @php $isSelected = $selectedId === $f->id; @endphp
                <tr wire:click="selectRow({{ $f->id }})"
                    class="border-b border-gray-100 cursor-pointer transition-colors"
                    style="{{ $isSelected ? 'background-color:#fff9c4;' : ($i % 2 === 0 ? 'background:#fff;' : 'background:#f9f9f9;') }}"
                    onmouseover="{{ $isSelected ? '' : "this.style.background='#f0f8f0';" }}"
                    onmouseout="{{ $isSelected ? '' : "this.style.background='".($i % 2 === 0 ? '#fff' : '#f9f9f9')."';" }}">
                    <td class="border border-gray-100 px-3 py-1.5 text-center text-gray-500">{{ $i + 1 }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 font-semibold text-gray-800">{{ $f->nama_fasilitas }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center">{{ $f->max_pengunjung ?? '-' }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center">{{ $f->jumlah_orang ?? '-' }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center font-mono">{{ $f->open_fasilitas }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center font-mono">{{ $f->close_fasilitas }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center font-mono">{{ $f->durasi }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center font-mono">{{ $f->max_terlambat }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center font-mono">{{ $f->min_hadir }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center">
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold
                            {{ $f->check_billing === 'Aktif' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $f->check_billing }}
                        </span>
                    </td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center">
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold
                            {{ $f->is_berbayar ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $f->is_berbayar ? 'Ya' : 'Gratis' }}
                        </span>
                    </td>
                    <td class="border border-gray-100 px-3 py-1.5 text-right font-medium text-gray-700">
                        {{ $f->is_berbayar ? 'Rp '.number_format((float)$f->biaya, 0, ',', '.') : '—' }}
                    </td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center">
                        @if($f->icon)
                        <img src="{{ Storage::url($f->icon) }}" alt="" class="w-8 h-8 object-contain mx-auto rounded border border-gray-200">
                        @else
                        <span class="text-gray-300 text-[10px]">—</span>
                        @endif
                    </td>
                    <td class="border border-gray-100 px-3 py-1.5 text-gray-600 max-w-xs">
                        <span class="line-clamp-2 leading-snug">{{ $f->terms ? strip_tags($f->terms) : '—' }}</span>
                    </td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button wire:click="openEdit({{ $f->id }})"
                                    class="px-2 py-0.5 text-[10px] font-semibold rounded bg-amber-500 hover:bg-amber-600 text-white">
                                Edit
                            </button>
                            <button wire:click="delete({{ $f->id }})"
                                    wire:confirm="Hapus fasilitas '{{ $f->nama_fasilitas }}'?"
                                    class="px-2 py-0.5 text-[10px] font-semibold rounded bg-red-600 hover:bg-red-700 text-white">
                                Hapus
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="15" class="px-4 py-10 text-center text-xs text-gray-400">
                        Belum ada data fasilitas. Klik "+ Input Fasilitas" untuk menambahkan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Bottom action bar (ICMAP style) --}}
    <div class="mt-3 flex items-center justify-between border-t border-gray-200 pt-3">
        <div class="flex items-center gap-3">
            <button wire:click="openAdd"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded flex items-center gap-1">
                + Input Fasilitas
            </button>
            <button wire:click="{{ $selectedId ? 'openEdit('.$selectedId.')' : '' }}"
                    class="px-3 py-1.5 text-xs font-semibold rounded flex items-center gap-1"
                    style="{{ $selectedId ? 'background:#f59e0b; color:#fff;' : 'background:#e5e7eb; color:#9ca3af; cursor:not-allowed;' }}"
                    {{ !$selectedId ? 'disabled' : '' }}>
                ✏ Edit
            </button>
        </div>
        <span class="text-xs text-gray-400">Total: {{ $facilities->count() }} fasilitas</span>
    </div>

    {{-- ── Modal Form ── --}}
    @if($showPanel)
    <div class="fixed inset-0 z-50 flex items-center justify-center"
         style="background: rgba(0,0,0,0.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-5 py-3 border-b"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide">
                    {{ $editId ? 'Edit Fasilitas' : 'Input Fasilitas' }}
                </h3>
                <button wire:click="closePanel" class="text-white/70 hover:text-white text-lg leading-none">✕</button>
            </div>

            <form wire:submit="save" class="px-5 py-4 space-y-3">

                {{-- Nama Fasilitas --}}
                <div class="grid grid-cols-3 gap-3 items-center">
                    <label class="text-xs font-medium text-gray-600 text-left">Nama Fasilitas</label>
                    <div class="col-span-2">
                        <input wire:model="fNama" type="text"
                               class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]"
                               placeholder="Nama fasilitas">
                        @error('fNama') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Batas Pengunjung + Jumlah Orang --}}
                <div class="grid grid-cols-3 gap-3 items-center">
                    <label class="text-xs font-medium text-gray-600 text-left">Batas Pengunjung</label>
                    <div class="flex items-center gap-2 col-span-2">
                        <input wire:model="fMaxPengunjung" type="number" min="0"
                               class="w-28 text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]"
                               placeholder="0">
                        <label class="text-xs text-gray-600">Jumlah Orang</label>
                        <input wire:model="fJumlahOrang" type="number" min="0"
                               class="w-28 text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]"
                               placeholder="0">
                    </div>
                </div>

                {{-- Durasi --}}
                <div class="grid grid-cols-3 gap-3 items-center">
                    <label class="text-xs font-medium text-gray-600 text-left">Durasi Penggunaan</label>
                    <div class="col-span-2">
                        <input wire:model="fDurasi" type="time"
                               class="text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                        @error('fDurasi') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Max Terlambat + Min Kehadiran --}}
                <div class="grid grid-cols-3 gap-3 items-center">
                    <label class="text-xs font-medium text-gray-600 text-left">Max Terlambat</label>
                    <div class="flex items-center gap-2 col-span-2">
                        <input wire:model="fMaxTerlambat" type="time"
                               class="text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                        <label class="text-xs text-gray-600 whitespace-nowrap">Min Kehadiran</label>
                        <input wire:model="fMinHadir" type="time"
                               class="text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                    </div>
                </div>

                {{-- Open + Close --}}
                <div class="grid grid-cols-3 gap-3 items-center">
                    <label class="text-xs font-medium text-gray-600 text-left">Open Fasilitas</label>
                    <div class="flex items-center gap-2 col-span-2">
                        <input wire:model="fOpen" type="time"
                               class="text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                        <label class="text-xs text-gray-600 whitespace-nowrap">Close Fasilitas</label>
                        <input wire:model="fClose" type="time"
                               class="text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                    </div>
                </div>

                {{-- Check Billing --}}
                <div class="grid grid-cols-3 gap-3 items-center">
                    <label class="text-xs font-medium text-gray-600 text-left">Check Billing</label>
                    <div class="col-span-2">
                        <select wire:model="fCheckBilling"
                                class="text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] bg-white">
                            <option value="Tidak Aktif">Tidak Aktif</option>
                            <option value="Aktif">Aktif</option>
                        </select>
                    </div>
                </div>

                {{-- Harga Sewa — editable hanya AM/CS --}}
                <div class="grid grid-cols-3 gap-3 items-center">
                    <label class="text-xs font-medium text-gray-600 text-left">Sewa Berbayar</label>
                    <div class="col-span-2 flex items-center gap-3">
                        @if($canEdit)
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input wire:model.live="fIsBerbayar" type="checkbox" class="rounded border-gray-300">
                            <span class="text-xs text-gray-600">Ya, fasilitas ini berbayar</span>
                        </label>
                        @else
                        <span class="text-xs {{ $fIsBerbayar ? 'text-orange-600 font-semibold' : 'text-gray-400' }}">
                            {{ $fIsBerbayar ? 'Ya (berbayar)' : 'Gratis' }}
                            <span class="text-gray-400 font-normal ml-1">— hanya AM/CS yang dapat mengubah</span>
                        </span>
                        @endif
                    </div>
                </div>
                @if($fIsBerbayar)
                <div class="grid grid-cols-3 gap-3 items-center">
                    <label class="text-xs font-medium text-gray-600 text-left">Harga Sewa (Rp)</label>
                    <div class="col-span-2">
                        @if($canEdit)
                        <div x-data="{
                            fmt(v) {
                                let d = String(v).replace(/[^0-9]/g,'');
                                return d ? parseInt(d).toLocaleString('id-ID') : '';
                            },
                            onInput(e) {
                                let d = e.target.value.replace(/[^0-9]/g,'');
                                e.target.value = d ? parseInt(d).toLocaleString('id-ID') : '';
                                $wire.set('fBiaya', d);
                            }
                        }">
                            <input type="text" inputmode="numeric"
                                   x-init="$el.value = fmt('{{ $fBiaya }}')"
                                   x-on:input="onInput($event)"
                                   class="w-48 text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]"
                                   placeholder="0">
                        </div>
                        @error('fBiaya') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                        @else
                        <span class="text-sm font-medium text-gray-700">Rp {{ number_format((float)$fBiaya, 0, ',', '.') }}</span>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Upload Icon --}}
                <div class="grid grid-cols-3 gap-3 items-start">
                    <label class="text-xs font-medium text-gray-600 text-left pt-1.5">Upload Icon</label>
                    <div class="col-span-2">
                        <input wire:model="fIcon" type="file" accept="image/*"
                               class="text-xs text-gray-600 file:mr-2 file:py-1 file:px-2 file:rounded file:border file:border-gray-300 file:text-xs file:bg-gray-50 file:text-gray-600 hover:file:bg-gray-100">
                        @error('fIcon') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                        @if($fIcon)
                        <img src="{{ $fIcon->temporaryUrl() }}" alt="preview" class="mt-1.5 w-16 h-16 object-contain rounded border border-gray-200">
                        @elseif($editId && ($existing = \App\Models\Facility::find($editId)?->icon))
                        <div class="mt-1.5 flex items-center gap-2">
                            <img src="{{ Storage::url($existing) }}" alt="" class="w-16 h-16 object-contain rounded border border-gray-200">
                            <span class="text-[10px] text-gray-400">Icon saat ini</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Terms --}}
                <div class="grid grid-cols-3 gap-3 items-start">
                    <label class="text-xs font-medium text-gray-600 text-left pt-1.5">Terms</label>
                    <div class="col-span-2">
                        <textarea wire:model="fTerms" rows="4"
                                  class="w-full text-xs border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] resize-none"
                                  placeholder="Isi syarat & ketentuan penggunaan fasilitas..."></textarea>
                        <p class="text-[10px] text-gray-400 mt-0.5 leading-snug">
                            Note: Gunakan &lt;p&gt;...&lt;/p&gt; untuk paragraf baru dan &lt;br&gt; untuk enter.
                        </p>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex justify-center gap-4 pt-2 pb-1 border-t border-gray-100">
                    <button type="submit"
                            class="px-8 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded font-semibold">
                        💾 Simpan
                    </button>
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

<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\PreventiveMaintenance;

new #[Layout('layouts.karyawan')] class extends Component {

    public bool   $showPanel   = false;
    public ?int   $editId      = null;
    public string $fJudul      = '';
    public string $fArea       = '';
    public string $fTanggal    = '';
    public string $fJamMulai   = '';
    public string $fJamSelesai = '';
    public string $fPenanggung = '';
    public string $fStatus     = 'Terjadwal';
    public string $fCatatan    = '';
    public string $savedMsg    = '';
    public string $filterStatus = '';
    public string $filterBulan  = '';

    public function mount(): void
    {
        $this->filterBulan = now()->format('Y-m');
    }

    public function openAdd(): void
    {
        $this->resetForm();
        $this->fTanggal = now()->format('Y-m-d');
        $this->showPanel = true;
    }

    public function openEdit(int $id): void
    {
        $pm = PreventiveMaintenance::findOrFail($id);
        $this->editId      = $id;
        $this->fJudul      = $pm->judul;
        $this->fArea       = $pm->area ?? '';
        $this->fTanggal    = $pm->tanggal ? $pm->tanggal->format('Y-m-d') : '';
        $this->fJamMulai   = $pm->jam_mulai ?? '';
        $this->fJamSelesai = $pm->jam_selesai ?? '';
        $this->fPenanggung = $pm->penanggung_jawab ?? '';
        $this->fStatus     = $pm->status;
        $this->fCatatan    = $pm->catatan ?? '';
        $this->showPanel   = true;
    }

    public function save(): void
    {
        $this->validate([
            'fJudul'   => 'required|string|max:200',
            'fTanggal' => 'required|date',
            'fArea'    => 'nullable|string|max:100',
            'fStatus'  => 'required|string',
        ]);

        $data = [
            'judul'            => $this->fJudul,
            'area'             => $this->fArea ?: null,
            'tanggal'          => $this->fTanggal,
            'jam_mulai'        => $this->fJamMulai ?: null,
            'jam_selesai'      => $this->fJamSelesai ?: null,
            'penanggung_jawab' => $this->fPenanggung ?: null,
            'status'           => $this->fStatus,
            'catatan'          => $this->fCatatan ?: null,
        ];

        if ($this->editId) {
            PreventiveMaintenance::findOrFail($this->editId)->update($data);
            $this->savedMsg = 'PM diperbarui.';
        } else {
            PreventiveMaintenance::create($data);
            $this->savedMsg = 'PM ditambahkan.';
        }

        $this->showPanel = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        PreventiveMaintenance::findOrFail($id)->delete();
        $this->savedMsg = 'PM dihapus.';
    }

    public function closePanel(): void { $this->showPanel = false; $this->resetForm(); }

    private function resetForm(): void
    {
        $this->editId = null; $this->fJudul = ''; $this->fArea = '';
        $this->fTanggal = ''; $this->fJamMulai = ''; $this->fJamSelesai = '';
        $this->fPenanggung = ''; $this->fStatus = 'Terjadwal'; $this->fCatatan = '';
    }

    public function with(): array
    {
        $statusOptions = PreventiveMaintenance::statusOptions();

        [$year, $month] = explode('-', $this->filterBulan . '-01');

        $pms = PreventiveMaintenance::when($this->filterBulan, function($q) use ($year, $month) {
                $q->whereYear('tanggal', $year)->whereMonth('tanggal', $month);
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();

        $totalPm    = $pms->count();
        $selesai    = $pms->where('status', 'Selesai')->count();
        $terjadwal  = $pms->where('status', 'Terjadwal')->count();
        $dibatalkan = $pms->where('status', 'Dibatalkan')->count();

        return compact('statusOptions', 'pms', 'totalPm', 'selesai', 'terjadwal', 'dibatalkan');
    }
}
?>

<div class="p-5">
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <span>PREVENTIVE MAINTENANCE</span>
            @if($savedMsg)
            <span class="text-xs font-normal bg-white/20 px-2 py-0.5 rounded">{{ $savedMsg }}</span>
            @endif
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-4 gap-3 mb-4">
        @php $cards = [
            ['label' => 'Total PM',   'val' => $totalPm,    'color' => 'border-blue-200',  'text' => 'text-blue-700'],
            ['label' => 'Terjadwal',  'val' => $terjadwal,  'color' => 'border-amber-200', 'text' => 'text-amber-700'],
            ['label' => 'Selesai',    'val' => $selesai,    'color' => 'border-green-200', 'text' => 'text-green-700'],
            ['label' => 'Dibatalkan', 'val' => $dibatalkan, 'color' => 'border-red-200',   'text' => 'text-red-600'],
        ]; @endphp
        @foreach($cards as $c)
        <div class="bg-white border {{ $c['color'] }} rounded-xl p-3 shadow-sm text-center">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide">{{ $c['label'] }}</p>
            <p class="text-2xl font-bold {{ $c['text'] }} mt-0.5">{{ $c['val'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-2 mb-3">
        <input wire:model.live="filterBulan" type="month"
               class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
        <button wire:click="$set('filterStatus', '')"
                class="px-3 py-1 text-xs rounded-full border font-medium
                       {{ $filterStatus === '' ? 'bg-blue-600 text-white border-[#1a5c2e]' : 'bg-white text-gray-600 border-gray-200' }}">
            Semua Status
        </button>
        @foreach($statusOptions as $s)
        <button wire:click="$set('filterStatus', '{{ $s }}')"
                class="px-3 py-1 text-xs rounded-full border font-medium
                       {{ $filterStatus === $s ? 'bg-blue-600 text-white border-[#1a5c2e]' : 'bg-white text-gray-600 border-gray-200' }}">
            {{ $s }}
        </button>
        @endforeach
    </div>

    <div class="bg-white border border-blue-200 rounded-lg overflow-x-auto shadow-sm">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">JUDUL</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-24">AREA</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-24">TANGGAL</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">JAM</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-28">PENANGGUNG JAWAB</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-24">STATUS</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pms as $pm)
                @php
                    $statusColor = match($pm->status) {
                        'Selesai'    => 'bg-green-100 text-green-700',
                        'Dibatalkan' => 'bg-red-100 text-red-600',
                        default      => 'bg-amber-100 text-amber-700',
                    };
                @endphp
                <tr class="border-b border-gray-100 hover:bg-[#f0f8f0]">
                    <td class="border border-blue-100 px-3 py-2">
                        <p class="font-medium text-gray-800">{{ $pm->judul }}</p>
                        @if($pm->catatan)
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ \Str::limit($pm->catatan, 60) }}</p>
                        @endif
                    </td>
                    <td class="border border-blue-100 px-3 py-2 text-gray-600">{{ $pm->area ?? '—' }}</td>
                    <td class="border border-blue-100 px-3 py-2 text-center text-gray-700">
                        {{ $pm->tanggal ? $pm->tanggal->format('d/m/Y') : '—' }}
                    </td>
                    <td class="border border-blue-100 px-3 py-2 text-center text-gray-600">
                        @if($pm->jam_mulai)
                            {{ $pm->jam_mulai }}{{ $pm->jam_selesai ? '–' . $pm->jam_selesai : '' }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="border border-blue-100 px-3 py-2 text-gray-700">{{ $pm->penanggung_jawab ?? '—' }}</td>
                    <td class="border border-blue-100 px-3 py-2 text-center">
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold {{ $statusColor }}">
                            {{ $pm->status }}
                        </span>
                    </td>
                    <td class="border border-blue-100 px-3 py-2 text-center">
                        <div class="flex justify-center gap-1">
                            <button wire:click="openEdit({{ $pm->id }})" class="px-2 py-0.5 text-[10px] font-semibold rounded bg-amber-500 hover:bg-amber-600 text-white">Edit</button>
                            <button wire:click="delete({{ $pm->id }})" wire:confirm="Hapus jadwal PM ini?" class="px-2 py-0.5 text-[10px] font-semibold rounded bg-red-600 hover:bg-red-700 text-white">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Belum ada jadwal PM bulan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3 flex items-center gap-2">
        <button wire:click="openAdd" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded flex items-center gap-1">
            + Tambah Jadwal PM
        </button>
    </div>

    @if($showPanel)
    <div class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-3 border-b"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <h3 class="text-sm font-bold text-white uppercase">{{ $editId ? 'Edit PM' : 'Tambah PM' }}</h3>
                <button wire:click="closePanel" class="text-white/70 hover:text-white text-lg leading-none">✕</button>
            </div>
            <form wire:submit="save" class="px-5 py-4 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Judul Kegiatan</label>
                    <input wire:model="fJudul" type="text" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                    @error('fJudul') <p class="text-red-500 text-[10px]">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Area</label>
                        <input wire:model="fArea" type="text" placeholder="cth: Rooftop, Basement" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                        <input wire:model="fTanggal" type="date" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                        @error('fTanggal') <p class="text-red-500 text-[10px]">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jam Mulai</label>
                        <input wire:model="fJamMulai" type="time" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jam Selesai</label>
                        <input wire:model="fJamSelesai" type="time" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Penanggung Jawab</label>
                        <input wire:model="fPenanggung" type="text" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                        <select wire:model="fStatus" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 bg-white focus:outline-none">
                            @foreach($statusOptions as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Catatan</label>
                    <textarea wire:model="fCatatan" rows="2" class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] resize-none"></textarea>
                </div>
                <div class="flex justify-center gap-3 pt-2 border-t border-gray-100">
                    <button type="submit" class="px-8 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded font-semibold">💾 Simpan</button>
                    <button type="button" wire:click="closePanel" class="px-8 py-2 bg-gray-400 hover:bg-gray-500 text-white text-sm rounded font-semibold">✕ Batal</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>


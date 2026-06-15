<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\DailyReport;

new #[Layout('layouts.karyawan')] class extends Component {

    public bool   $showPanel   = false;
    public ?int   $editId      = null;
    public string $fNamaStaff  = '';
    public string $fDepartemen = '';
    public string $fTanggal    = '';
    public string $fKegiatan   = '';
    public string $fLokasi     = '';
    public string $fKeterangan = '';
    public string $savedMsg    = '';
    public string $filterBulan = '';

    public function mount(): void
    {
        $this->filterBulan  = now()->format('Y-m');
        $this->fNamaStaff   = auth()->user()->name ?? '';
        $this->fDepartemen  = optional(auth()->user()->karyawan)->departemen ?? '';
        $this->fTanggal     = now()->format('Y-m-d');
    }

    public function openAdd(): void
    {
        $this->resetForm();
        $this->fNamaStaff   = auth()->user()->name ?? '';
        $this->fDepartemen  = optional(auth()->user()->karyawan)->departemen ?? '';
        $this->fTanggal     = now()->format('Y-m-d');
        $this->showPanel    = true;
    }

    public function openEdit(int $id): void
    {
        $r = DailyReport::findOrFail($id);
        $this->editId      = $id;
        $this->fNamaStaff  = $r->nama_staff;
        $this->fDepartemen = $r->departemen ?? '';
        $this->fTanggal    = $r->tanggal ? $r->tanggal->format('Y-m-d') : '';
        $this->fKegiatan   = $r->kegiatan;
        $this->fLokasi     = $r->lokasi ?? '';
        $this->fKeterangan = $r->keterangan ?? '';
        $this->showPanel   = true;
    }

    public function save(): void
    {
        $this->validate([
            'fNamaStaff' => 'required|string|max:100',
            'fTanggal'   => 'required|date',
            'fKegiatan'  => 'required|string',
        ]);

        $data = [
            'nama_staff'  => $this->fNamaStaff,
            'departemen'  => $this->fDepartemen ?: null,
            'tanggal'     => $this->fTanggal,
            'kegiatan'    => $this->fKegiatan,
            'lokasi'      => $this->fLokasi ?: null,
            'keterangan'  => $this->fKeterangan ?: null,
        ];

        if ($this->editId) {
            DailyReport::findOrFail($this->editId)->update($data);
            $this->savedMsg = 'Laporan diperbarui.';
        } else {
            DailyReport::create($data);
            $this->savedMsg = 'Laporan disimpan.';
        }

        $this->showPanel = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        DailyReport::findOrFail($id)->delete();
        $this->savedMsg = 'Laporan dihapus.';
    }

    public function closePanel(): void { $this->showPanel = false; $this->resetForm(); }

    private function resetForm(): void
    {
        $this->editId = null; $this->fNamaStaff = ''; $this->fDepartemen = '';
        $this->fTanggal = ''; $this->fKegiatan = ''; $this->fLokasi = ''; $this->fKeterangan = '';
    }

    public function with(): array
    {
        [$year, $month] = array_pad(explode('-', $this->filterBulan), 2, null);

        $myName = auth()->user()->name ?? '';

        $reports = DailyReport::where('nama_staff', 'like', "%{$myName}%")
            ->when($year && $month, fn($q) => $q->whereYear('tanggal', $year)->whereMonth('tanggal', $month))
            ->orderByDesc('tanggal')
            ->get();

        return compact('reports');
    }
}
?>

<div class="p-5">
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-3">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <span>LAPORAN HARIAN</span>
            @if($savedMsg)
            <span class="text-xs font-normal bg-white/20 px-2 py-0.5 rounded">{{ $savedMsg }}</span>
            @endif
        </div>
    </div>

    {{-- Filter --}}
    <div class="flex items-center gap-3 mb-3">
        <input wire:model.live="filterBulan" type="month"
               class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-400">
        <span class="text-xs text-gray-400">{{ $reports->count() }} laporan</span>
    </div>

    <div class="bg-white border border-blue-200 rounded-lg overflow-x-auto shadow-sm">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-24">TANGGAL</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">KEGIATAN</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-28">LOKASI</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">KETERANGAN</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $r)
                <tr class="border-b border-gray-100 hover:bg-[#f0f8f0]">
                    <td class="border border-blue-100 px-3 py-2 text-center text-gray-600 font-medium">
                        {{ $r->tanggal ? $r->tanggal->format('d/m/Y') : '—' }}
                    </td>
                    <td class="border border-blue-100 px-3 py-2 text-gray-800">{{ $r->kegiatan }}</td>
                    <td class="border border-blue-100 px-3 py-2 text-gray-600">{{ $r->lokasi ?? '—' }}</td>
                    <td class="border border-blue-100 px-3 py-2 text-gray-500">{{ \Str::limit($r->keterangan ?? '', 80) }}</td>
                    <td class="border border-blue-100 px-3 py-2 text-center">
                        <div class="flex justify-center gap-1">
                            <button wire:click="openEdit({{ $r->id }})" class="px-2 py-0.5 text-[10px] font-semibold rounded bg-amber-500 hover:bg-amber-600 text-white">Edit</button>
                            <button wire:click="delete({{ $r->id }})" wire:confirm="Hapus laporan ini?" class="px-2 py-0.5 text-[10px] font-semibold rounded bg-red-600 hover:bg-red-700 text-white">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Belum ada laporan bulan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3 flex items-center gap-2">
        <button wire:click="openAdd" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded flex items-center gap-1">
            + Tambah Laporan
        </button>
    </div>

    @if($showPanel)
    <div class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-3 border-b"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <h3 class="text-sm font-bold text-white uppercase">{{ $editId ? 'Edit Laporan' : 'Tambah Laporan' }}</h3>
                <button wire:click="closePanel" class="text-white/70 hover:text-white text-lg leading-none">✕</button>
            </div>
            <form wire:submit="save" class="px-5 py-4 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Staff</label>
                        <input wire:model="fNamaStaff" type="text" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                        @error('fNamaStaff') <p class="text-red-500 text-[10px]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Departemen</label>
                        <input wire:model="fDepartemen" type="text" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                        <input wire:model="fTanggal" type="date" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                        @error('fTanggal') <p class="text-red-500 text-[10px]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Lokasi</label>
                        <input wire:model="fLokasi" type="text" placeholder="cth: Lobby, Lantai 10" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kegiatan</label>
                    <textarea wire:model="fKegiatan" rows="3" placeholder="Tulis kegiatan yang dilakukan..." class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] resize-none"></textarea>
                    @error('fKegiatan') <p class="text-red-500 text-[10px]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan Tambahan</label>
                    <textarea wire:model="fKeterangan" rows="2" class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] resize-none"></textarea>
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

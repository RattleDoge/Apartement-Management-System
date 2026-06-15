<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\HandoverUnit;

new #[Layout('layouts.karyawan')] class extends Component {

    public string $search   = '';
    public ?int   $editId   = null;
    public string $editDaya = '';

    public function with(): array
    {
        $units = HandoverUnit::query()
            ->when($this->search, fn($q) => $q->where('lot_no', 'like', '%' . $this->search . '%'))
            ->orderBy('lot_no')
            ->get();
        return ['units' => $units];
    }

    public function startEdit(int $id): void
    {
        $u = HandoverUnit::findOrFail($id);
        $this->editId   = $id;
        $this->editDaya = $u->daya_listrik ?? '';
    }

    public function cancelEdit(): void
    {
        $this->editId   = null;
        $this->editDaya = '';
    }

    public function saveDaya(): void
    {
        $this->validate(['editDaya' => 'nullable|string|max:20']);
        if (!$this->editId) return;
        $u = HandoverUnit::findOrFail($this->editId);
        $u->daya_listrik = $this->editDaya ?: null;
        $u->save();
        $this->editId   = null;
        $this->editDaya = '';
        session()->flash('success', 'Daya unit ' . $u->lot_no . ' berhasil diperbarui.');
    }
}; ?>

<div>

{{-- Flash --}}
@if(session('success'))
<div class="max-w-5xl mx-auto px-5 pt-4">
    <div class="flex items-center gap-2 px-3 py-2 bg-green-50 border border-green-300 rounded text-xs text-green-700">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
</div>
@endif

<div class="max-w-5xl mx-auto px-5 py-5">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-base font-bold text-gray-800">Daftar Daya Unit</h1>
            <p class="text-xs text-gray-500 mt-0.5">Kelola kapasitas daya listrik per unit — digunakan untuk menentukan tarif PLN saat import billing.</p>
        </div>
        <div class="text-xs text-gray-400">
            {{ $units->count() }} unit terdaftar
        </div>
    </div>

    {{-- Info Tarif PLN --}}
    <div class="grid grid-cols-3 gap-3 mb-4">
        @foreach([['≤ 900 VA', 'Rp 1.352,00/kWh', 'bg-blue-50 border-blue-200 text-blue-700'], ['≤ 2.200 VA', 'Rp 1.444,70/kWh', 'bg-amber-50 border-amber-200 text-amber-700'], ['> 2.200 VA', 'Rp 1.699,53/kWh', 'bg-green-50 border-green-200 text-green-700']] as [$daya, $tarif, $cls])
        <div class="border rounded px-3 py-2 {{ $cls }}">
            <div class="text-[10px] font-semibold uppercase tracking-wide">Daya {{ $daya }}</div>
            <div class="text-xs font-bold mt-0.5">{{ $tarif }}</div>
        </div>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="mb-3">
        <input wire:model.live.debounce.300ms="search"
               type="text" placeholder="Cari Lot No..."
               class="w-64 border border-gray-300 rounded px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] focus:border-[#1a5c2e]">
    </div>

    {{-- Table --}}
    <div class="border border-gray-200 rounded overflow-hidden">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 w-8">No</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Lot No</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Tipe Unit</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 w-40">Daya Listrik</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 w-32">KVA</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Tarif PLN</th>
                    <th class="px-3 py-2 text-center font-semibold text-gray-600 w-24">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($units as $i => $u)
            @php
                $isEditing = $this->editId === $u->id;
                $daya      = $u->daya_listrik;
                $vaNum     = $daya ? (float) preg_replace('/[^0-9.]/', '', $daya) : 0;
                $va        = $vaNum > 0 ? ($vaNum < 100 ? (int)round($vaNum * 1000) : (int)round($vaNum)) : 0;
                $kva       = $va > 0 ? number_format($va / 1000, 2) . ' KVA' : '—';
                $tarif     = $va <= 0 ? '—' : ($va <= 900 ? 'Rp 1.352/kWh' : ($va <= 2200 ? 'Rp 1.444,70/kWh' : 'Rp 1.699,53/kWh'));
                $tarifColor= $va <= 0 ? 'text-gray-400' : ($va <= 900 ? 'text-blue-600' : ($va <= 2200 ? 'text-amber-600' : 'text-green-600'));
            @endphp
            <tr class="border-b border-gray-100 hover:bg-gray-50 {{ $isEditing ? 'bg-yellow-50' : '' }}">
                <td class="px-3 py-2 text-gray-400">{{ $i + 1 }}</td>
                <td class="px-3 py-2 font-mono font-semibold text-gray-800">{{ $u->lot_no }}</td>
                <td class="px-3 py-2 text-gray-600">{{ $u->tipe_unit ?: '—' }}</td>

                {{-- Daya cell --}}
                <td class="px-3 py-2">
                    @if($isEditing)
                        <input wire:model="editDaya"
                               type="text"
                               placeholder="misal: 1300 VA"
                               class="w-32 border border-yellow-400 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-yellow-500">
                        @error('editDaya')<span class="text-red-500 text-[10px]">{{ $message }}</span>@enderror
                    @else
                        <span class="{{ $daya ? 'text-gray-800' : 'text-gray-400' }}">{{ $daya ?: '—' }}</span>
                    @endif
                </td>

                <td class="px-3 py-2 font-mono text-gray-700">{{ $isEditing ? '' : $kva }}</td>
                <td class="px-3 py-2 font-semibold {{ $tarifColor }}">{{ $isEditing ? '' : $tarif }}</td>

                {{-- Aksi --}}
                <td class="px-3 py-2 text-center">
                    @if($isEditing)
                    <div class="flex items-center justify-center gap-1">
                        <button wire:click="saveDaya"
                                class="px-2 py-1 text-[10px] font-semibold bg-green-600 hover:bg-green-700 text-white rounded transition-colors">
                            Simpan
                        </button>
                        <button wire:click="cancelEdit"
                                class="px-2 py-1 text-[10px] font-semibold bg-gray-200 hover:bg-gray-300 text-gray-700 rounded transition-colors">
                            Batal
                        </button>
                    </div>
                    @else
                    <button wire:click="startEdit({{ $u->id }})"
                            class="px-3 py-1 text-[10px] font-semibold bg-white border border-gray-300 hover:border-[#1a5c2e] hover:text-[#1a5c2e] text-gray-600 rounded transition-colors">
                        Edit
                    </button>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-xs text-gray-400">
                    {{ $search ? 'Tidak ada unit yang cocok dengan pencarian.' : 'Belum ada data unit.' }}
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-[10px] text-gray-400 mt-2">
        Perubahan daya akan otomatis diterapkan pada import billing berikutnya.
    </p>

</div>
</div>

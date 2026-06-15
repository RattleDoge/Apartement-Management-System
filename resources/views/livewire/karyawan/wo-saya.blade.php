<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\WorkOrder;

new #[Layout('layouts.karyawan')] class extends Component {

    public string $filterStatus = '';
    public string $search       = '';

    public function with(): array
    {
        $myName = optional(auth()->user())->name ?? '';

        $wos = WorkOrder::where('assign_staff', 'like', "%{$myName}%")
            ->when($this->filterStatus, fn($q) => $q->where('status_comp', $this->filterStatus))
            ->when($this->search, fn($q) => $q->where(function($q2) {
                $q2->where('no_wo', 'like', "%{$this->search}%")
                   ->orWhere('lokasi', 'like', "%{$this->search}%")
                   ->orWhere('jenis_wo', 'like', "%{$this->search}%");
            }))
            ->orderByDesc('tanggal')
            ->paginate(15);

        $statusOptions = [
            'Pesan Diterima',
            'Dalam Pengecekan',
            'Dalam Proses',
            'Menunggu Material',
            'Work Order Close',
        ];

        $totalSaya    = WorkOrder::where('assign_staff', 'like', "%{$myName}%")->count();
        $selesaiSaya  = WorkOrder::where('assign_staff', 'like', "%{$myName}%")->where('status_comp', 'Work Order Close')->count();
        $pendingSaya  = $totalSaya - $selesaiSaya;

        return compact('wos', 'statusOptions', 'totalSaya', 'selesaiSaya', 'pendingSaya');
    }
}
?>

<div class="p-5">
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            WO SAYA
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-3 gap-3 mb-4">
        @php $cards = [
            ['label' => 'Total WO',   'val' => $totalSaya,   'color' => 'border-blue-200',  'text' => 'text-blue-700'],
            ['label' => 'Selesai',    'val' => $selesaiSaya, 'color' => 'border-green-200', 'text' => 'text-green-700'],
            ['label' => 'Pending',    'val' => $pendingSaya, 'color' => 'border-red-200',   'text' => 'text-red-600'],
        ]; @endphp
        @foreach($cards as $c)
        <div class="bg-white border {{ $c['color'] }} rounded-xl p-3 shadow-sm text-center">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide">{{ $c['label'] }}</p>
            <p class="text-2xl font-bold {{ $c['text'] }} mt-0.5">{{ $c['val'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filter row --}}
    <div class="flex flex-wrap gap-2 mb-3">
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="Cari No WO / Lokasi..."
               class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] w-48">
        <button wire:click="$set('filterStatus', '')"
                class="px-3 py-1 text-xs rounded-full border font-medium
                       {{ $filterStatus === '' ? 'bg-blue-600 text-white border-[#1a5c2e]' : 'bg-white text-gray-600 border-gray-200' }}">
            Semua
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
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">NO WO</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">TANGGAL</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">JENIS</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">LOT NO</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">KETERANGAN</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-32">STATUS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($wos as $wo)
                @php
                    $statusColor = match($wo->status_comp) {
                        'Work Order Close'  => 'bg-green-100 text-green-700',
                        'Dalam Proses'      => 'bg-blue-100 text-blue-700',
                        'Dalam Pengecekan'  => 'bg-purple-100 text-purple-700',
                        'Menunggu Material' => 'bg-amber-100 text-amber-700',
                        default             => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <tr class="border-b border-gray-100 hover:bg-[#f0f8f0]">
                    <td class="border border-gray-100 px-3 py-2 font-mono font-semibold text-gray-700">{{ $wo->no_wo }}</td>
                    <td class="border border-gray-100 px-3 py-2 text-gray-600">{{ $wo->tanggal ? \Carbon\Carbon::parse($wo->tanggal)->format('d/m/Y') : '—' }}</td>
                    <td class="border border-gray-100 px-3 py-2 text-gray-700">{{ $wo->jenis_wo ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-2 text-gray-700">{{ $wo->lot_no ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-2 text-gray-500">{{ \Str::limit($wo->uraian_pekerjaan ?? '', 60) }}</td>
                    <td class="border border-gray-100 px-3 py-2 text-center">
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold {{ $statusColor }}">
                            {{ $wo->status_comp ?? '—' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Tidak ada WO yang ditugaskan ke Anda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($wos->hasPages())
    <div class="mt-3">{{ $wos->links() }}</div>
    @endif
</div>


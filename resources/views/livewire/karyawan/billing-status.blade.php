<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\HandoverUnit;
use App\Models\Tenant;

new #[Layout('layouts.karyawan')] class extends Component {

    public string $search     = '';
    public string $filterStatus = ''; // '' = all, 'aktif', 'nonaktif'

    public function with(): array
    {
        $units = HandoverUnit::query()
            ->when($this->search, fn($q) => $q->where('lot_no', 'like', '%' . $this->search . '%'))
            ->when($this->filterStatus === 'aktif',    fn($q) => $q->where('billing_aktif', true))
            ->when($this->filterStatus === 'nonaktif', fn($q) => $q->where('billing_aktif', false))
            ->orderBy('lot_no')
            ->get();

        $tenantMap = Tenant::with('user')
            ->whereIn('unit_number', $units->pluck('lot_no'))
            ->get()
            ->keyBy('unit_number');

        return ['units' => $units, 'tenantMap' => $tenantMap];
    }

    public function toggleBilling(int $id): void
    {
        $u = HandoverUnit::findOrFail($id);
        $u->billing_aktif = !$u->billing_aktif;
        $u->save();
    }
}; ?>

<div>

<div class="max-w-5xl mx-auto px-5 py-5">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-base font-bold text-gray-800">Status Billing Unit</h1>
            <p class="text-xs text-gray-500 mt-0.5">
                Unit yang billing-nya dinonaktifkan tidak akan diproses saat import billing meteran.
            </p>
        </div>
        @php
            $totalAktif    = $units->where('billing_aktif', true)->count();
            $totalNonAktif = $units->where('billing_aktif', false)->count();
        @endphp
        <div class="flex items-center gap-3 text-xs">
            <span class="flex items-center gap-1.5 px-3 py-1.5 bg-green-50 border border-green-200 rounded text-green-700 font-semibold">
                <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                Aktif: {{ $totalAktif }}
            </span>
            <span class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50 border border-red-200 rounded text-red-700 font-semibold">
                <span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span>
                Non-aktif: {{ $totalNonAktif }}
            </span>
        </div>
    </div>

    {{-- Info Box --}}
    @if($totalNonAktif > 0)
    <div class="flex items-start gap-2 px-3 py-2.5 mb-4 bg-amber-50 border border-amber-300 rounded text-xs text-amber-800">
        <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <span>
            <strong>{{ $totalNonAktif }} unit</strong> sedang dalam status billing non-aktif (meteran dicabut).
            Unit ini tidak akan diproses saat import billing.
        </span>
    </div>
    @endif

    {{-- Filter & Search --}}
    <div class="flex items-center gap-3 mb-3">
        <input wire:model.live.debounce.300ms="search"
               type="text" placeholder="Cari Lot No..."
               class="w-56 border border-gray-300 rounded px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] focus:border-[#1a5c2e]">

        <div class="flex rounded overflow-hidden border border-gray-300">
            @foreach(['' => 'Semua', 'aktif' => 'Aktif', 'nonaktif' => 'Non-aktif'] as $val => $label)
            <button wire:click="$set('filterStatus', '{{ $val }}')"
                    class="px-3 py-1.5 text-xs font-medium transition-colors
                           {{ $filterStatus === $val
                               ? 'bg-[#1a5c2e] text-white'
                               : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- Table --}}
    <div class="border border-gray-200 rounded overflow-hidden">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 w-8">No</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Lot No</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Nama Pemilik</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 w-28">Daya Listrik</th>
                    <th class="px-3 py-2 text-center font-semibold text-gray-600 w-28">Status Billing</th>
                    <th class="px-3 py-2 text-center font-semibold text-gray-600 w-36">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($units as $i => $u)
            @php
                $nama   = $tenantMap->get($u->lot_no)?->user?->name ?? '—';
                $aktif  = $u->billing_aktif !== false;
            @endphp
            <tr class="border-b border-gray-100 hover:bg-gray-50 {{ !$aktif ? 'bg-red-50/40' : '' }}">
                <td class="px-3 py-2 text-gray-400">{{ $i + 1 }}</td>
                <td class="px-3 py-2 font-mono font-semibold {{ $aktif ? 'text-gray-800' : 'text-red-600' }}">
                    {{ $u->lot_no }}
                </td>
                <td class="px-3 py-2 text-gray-700">{{ $nama }}</td>
                <td class="px-3 py-2 text-gray-600">{{ $u->daya_listrik ?: '—' }}</td>

                {{-- Status badge --}}
                <td class="px-3 py-2 text-center">
                    @if($aktif)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-700 border border-green-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                        Aktif
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-700 border border-red-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span>
                        Non-aktif
                    </span>
                    @endif
                </td>

                {{-- Toggle button --}}
                <td class="px-3 py-2 text-center">
                    @if($aktif)
                    <button
                        wire:click="toggleBilling({{ $u->id }})"
                        wire:confirm="Nonaktifkan billing unit {{ $u->lot_no }}? Unit ini tidak akan diproses saat import billing."
                        class="px-3 py-1 text-[10px] font-semibold border rounded transition-colors
                               bg-white border-red-300 text-red-600 hover:bg-red-50 hover:border-red-400">
                        Nonaktifkan
                    </button>
                    @else
                    <button
                        wire:click="toggleBilling({{ $u->id }})"
                        wire:confirm="Aktifkan kembali billing unit {{ $u->lot_no }}?"
                        class="px-3 py-1 text-[10px] font-semibold border rounded transition-colors
                               bg-white border-green-400 text-green-700 hover:bg-green-50 hover:border-green-500">
                        Aktifkan
                    </button>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-xs text-gray-400">
                    Tidak ada data unit.
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-[10px] text-gray-400 mt-2">
        Unit dinonaktifkan oleh Engineering saat meteran dicabut akibat tunggakan ≥ 4 bulan.
    </p>

</div>
</div>

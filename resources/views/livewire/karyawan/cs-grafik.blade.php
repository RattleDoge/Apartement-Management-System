<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\WorkOrder;
use App\Models\TenantRequest;
use App\Models\FacilityReservation;

new #[Layout('layouts.karyawan')] class extends Component {

    public int $tahun;
    public int $bulan;

    public function mount(): void
    {
        $this->tahun = (int) now()->format('Y');
        $this->bulan = (int) now()->format('n');
    }

    public function with(): array
    {
        $y = $this->tahun;
        $m = $this->bulan;

        // ── Work Order stats ────────────────────────────────────────────
        $woTotal   = WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->count();
        $woSelesai = WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)
                        ->where('status_comp', 'Work Order Close')->count();
        $woPending = $woTotal - $woSelesai;
        $woExternal= WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->where('ex_in', 'EX')->count();
        $woInternal= WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->where('ex_in', 'IN')->count();

        // WO per bulan (12 bulan)
        $woPerBulan = collect(range(1, 12))->map(fn($mn) => [
            'label'   => \Carbon\Carbon::create($y, $mn, 1)->isoFormat('MMM'),
            'total'   => WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->count(),
            'selesai' => WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $mn)
                            ->where('status_comp', 'Work Order Close')->count(),
            'masuk'   => WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $mn)->count(),
        ]);

        // WO by jenis (top 6)
        $woByJenis = WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)
            ->selectRaw('jenis_wo, COUNT(*) as total')
            ->groupBy('jenis_wo')
            ->orderByDesc('total')
            ->limit(7)
            ->get();

        // WO by status comp
        $woByStatus = WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)
            ->selectRaw('COALESCE(status_comp, \'Belum Diproses\') as status_comp, COUNT(*) as total')
            ->groupBy('status_comp')
            ->orderByDesc('total')
            ->get();

        // ── Tenant Request / Complain ───────────────────────────────────
        $trTotal   = TenantRequest::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->count();
        $trSelesai = TenantRequest::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->where('is_selesai', true)->count();
        $trPending = $trTotal - $trSelesai;

        $trPerBulan = collect(range(1, 12))->map(fn($mn) => [
            'label' => \Carbon\Carbon::create($y, $mn, 1)->isoFormat('MMM'),
            'total' => TenantRequest::whereYear('tanggal', $y)->whereMonth('tanggal', $mn)->count(),
        ]);

        $trByKategori = TenantRequest::whereYear('tanggal', $y)->whereMonth('tanggal', $m)
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        // ── Facility Reservation ────────────────────────────────────────
        $frTotal    = FacilityReservation::whereYear('tanggal_reservasi', $y)->whereMonth('tanggal_reservasi', $m)->count();
        $frSelesai  = FacilityReservation::whereYear('tanggal_reservasi', $y)->whereMonth('tanggal_reservasi', $m)->where('status', 'Selesai')->count();
        $frBerjalan = FacilityReservation::whereYear('tanggal_reservasi', $y)->whereMonth('tanggal_reservasi', $m)->where('status', 'Sedang Berlangsung')->count();

        $frByFasilitas = FacilityReservation::whereYear('tanggal_reservasi', $y)->whereMonth('tanggal_reservasi', $m)
            ->selectRaw('nama_fasilitas, COUNT(*) as total')
            ->groupBy('nama_fasilitas')
            ->orderByDesc('total')
            ->get();

        $tahunOptions = range(now()->year, now()->year - 3, -1);
        $bulanNames   = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

        return compact(
            'woTotal', 'woSelesai', 'woPending', 'woExternal', 'woInternal',
            'woPerBulan', 'woByJenis', 'woByStatus',
            'trTotal', 'trSelesai', 'trPending', 'trPerBulan', 'trByKategori',
            'frTotal', 'frSelesai', 'frBerjalan', 'frByFasilitas',
            'tahunOptions', 'bulanNames'
        );
    }
};
?>

<div class="p-5 space-y-6">

    {{-- ── Header ── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Grafik Customer Services</h1>
            <p class="text-xs text-gray-400 mt-0.5">Statistik Work Order, Complain & Reservasi Fasilitas</p>
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="bulan"
                    class="text-xs border border-gray-300 rounded px-2 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                @foreach($bulanNames as $i => $nm)
                <option value="{{ $i + 1 }}" {{ $bulan === $i+1 ? 'selected' : '' }}>{{ $nm }}</option>
                @endforeach
            </select>
            <select wire:model.live="tahun"
                    class="text-xs border border-gray-300 rounded px-2 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                @foreach($tahunOptions as $y)
                <option value="{{ $y }}" {{ $tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- ── Stat Cards ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach([
            ['WO Masuk',    $woTotal,   'border-blue-200',   'text-blue-700',  '#3b82f6'],
            ['WO Selesai',  $woSelesai, 'border-green-200',  'text-green-700', '#16a34a'],
            ['WO Pending',  $woPending, 'border-red-200',    'text-red-600',   '#dc2626'],
            ['Complain',    $trTotal,   'border-amber-200',  'text-amber-700', '#d97706'],
        ] as [$lbl, $val, $border, $txt, $hex])
        <div class="bg-white border {{ $border }} rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">{{ $lbl }}</p>
            <p class="text-3xl font-bold {{ $txt }}">{{ $val }}</p>
        </div>
        @endforeach
    </div>

    {{-- ── Row 2: WO Grafik ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- WO Masuk per Bulan (bar) --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">WO Masuk per Bulan {{ $tahun }}</p>
                <div class="flex items-center gap-3 text-[10px]">
                    <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:#3b82f6;"></span>Masuk</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:#16a34a;"></span>Selesai</span>
                </div>
            </div>
            <div class="px-4 py-4">
                @php $maxWo = max(1, $woPerBulan->max('masuk')); @endphp
                <div class="flex items-end gap-1" style="height:100px;">
                    @foreach($woPerBulan as $row)
                    <div class="flex-1 flex flex-col items-center gap-0.5">
                        <span class="text-[8px] text-gray-400">{{ $row['masuk'] ?: '' }}</span>
                        <div class="w-full flex gap-0.5 items-end" style="height:80px;">
                            <div class="flex-1 rounded-t" style="height:{{ max(2, ($row['masuk'] / $maxWo) * 76) }}px; background:#93c5fd;"></div>
                            <div class="flex-1 rounded-t" style="height:{{ max(2, ($row['selesai'] / $maxWo) * 76) }}px; background:#86efac;"></div>
                        </div>
                        <span class="text-[8px] text-gray-400">{{ $row['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- WO by Jenis (horizontal bar) --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">WO per Kategori</p>
            </div>
            @if($woByJenis->isEmpty())
            <div class="px-4 py-8 text-center text-xs text-gray-400">Tidak ada data bulan ini.</div>
            @else
            <div class="divide-y divide-gray-50 px-4 py-2">
                @php $maxJ = max(1, $woByJenis->max('total')); @endphp
                @php $colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316']; @endphp
                @foreach($woByJenis as $idx => $row)
                <div class="py-2 flex items-center gap-3">
                    <div class="w-28 text-[11px] text-gray-700 truncate shrink-0" title="{{ $row->jenis_wo }}">{{ $row->jenis_wo ?: '—' }}</div>
                    <div class="flex-1 h-4 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-4 rounded-full transition-all" style="width:{{ ($row->total / $maxJ) * 100 }}%; background:{{ $colors[$idx % count($colors)] }};"></div>
                    </div>
                    <span class="text-xs font-bold text-gray-700 w-6 text-right">{{ $row->total }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ── Row 3: WO Status + EX/IN Donut ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- WO by Status --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Status Work Order</p>
            </div>
            @if($woByStatus->isEmpty())
            <div class="px-4 py-8 text-center text-xs text-gray-400">Tidak ada data bulan ini.</div>
            @else
            <div class="divide-y divide-gray-50 px-4 py-2">
                @php
                    $maxSt = max(1, $woByStatus->max('total'));
                    $statusColors = [
                        'Work Order Close'  => '#16a34a',
                        'Selesai'           => '#22c55e',
                        'Dalam Proses'      => '#3b82f6',
                        'Dalam Pengecekan'  => '#06b6d4',
                        'Pesan Diterima'    => '#f59e0b',
                        'Belum Diproses'    => '#9ca3af',
                    ];
                @endphp
                @foreach($woByStatus as $row)
                @php $sc = $statusColors[$row->status_comp] ?? '#6b7280'; @endphp
                <div class="py-2 flex items-center gap-3">
                    <div class="w-32 text-[11px] text-gray-700 truncate shrink-0" title="{{ $row->status_comp }}">{{ $row->status_comp }}</div>
                    <div class="flex-1 h-4 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-4 rounded-full" style="width:{{ ($row->total / $maxSt) * 100 }}%; background:{{ $sc }};"></div>
                    </div>
                    <span class="text-xs font-bold text-gray-700 w-6 text-right">{{ $row->total }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- WO Internal vs External ── --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Work Order Internal vs External</p>
            </div>
            <div class="px-4 py-4 flex items-center justify-center gap-8">
                @php $woAll = max(1, $woInternal + $woExternal); @endphp
                {{-- Stacked bar --}}
                <div class="flex-1">
                    <div class="h-8 rounded-full overflow-hidden flex" style="background:#e5e7eb;">
                        <div class="h-8 flex items-center justify-center text-white text-[11px] font-bold transition-all"
                             style="width:{{ ($woInternal / $woAll) * 100 }}%; background:#3b82f6; min-width:{{ $woInternal > 0 ? '20px' : '0' }};">
                            {{ $woInternal > 0 ? $woInternal : '' }}
                        </div>
                        <div class="h-8 flex items-center justify-center text-white text-[11px] font-bold transition-all"
                             style="width:{{ ($woExternal / $woAll) * 100 }}%; background:#f59e0b; min-width:{{ $woExternal > 0 ? '20px' : '0' }};">
                            {{ $woExternal > 0 ? $woExternal : '' }}
                        </div>
                    </div>
                    <div class="flex gap-4 mt-2 text-[10px] text-gray-500 justify-center">
                        <span class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#3b82f6;"></span>
                            Internal ({{ $woInternal }})
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#f59e0b;"></span>
                            External ({{ $woExternal }})
                        </span>
                    </div>
                </div>
            </div>
            {{-- Percentage cards --}}
            <div class="grid grid-cols-2 divide-x divide-gray-100 border-t border-gray-100 mt-2">
                <div class="px-4 py-3 text-center">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Internal</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $woAll > 0 ? round(($woInternal / $woAll) * 100) : 0 }}%</p>
                </div>
                <div class="px-4 py-3 text-center">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">External</p>
                    <p class="text-2xl font-bold text-amber-600">{{ $woAll > 0 ? round(($woExternal / $woAll) * 100) : 0 }}%</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 4: Complain + Fasilitas ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Complain per Kategori --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Complain per Kategori</p>
                <div class="flex gap-3 text-[10px] text-gray-500">
                    <span>Total: <strong>{{ $trTotal }}</strong></span>
                    <span class="text-green-600">Selesai: <strong>{{ $trSelesai }}</strong></span>
                    <span class="text-red-500">Pending: <strong>{{ $trPending }}</strong></span>
                </div>
            </div>
            @if($trByKategori->isEmpty())
            <div class="px-4 py-8 text-center text-xs text-gray-400">Tidak ada complain bulan ini.</div>
            @else
            <div class="divide-y divide-gray-50 px-4 py-2">
                @php $maxTr = max(1, $trByKategori->max('total')); @endphp
                @foreach($trByKategori as $row)
                <div class="py-2 flex items-center gap-3">
                    <div class="w-28 text-[11px] text-gray-700 truncate shrink-0" title="{{ $row->kategori }}">{{ $row->kategori ?: 'Lainnya' }}</div>
                    <div class="flex-1 h-4 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-4 rounded-full" style="width:{{ ($row->total / $maxTr) * 100 }}%; background:#f59e0b;"></div>
                    </div>
                    <span class="text-xs font-bold text-gray-700 w-6 text-right">{{ $row->total }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Reservasi Fasilitas --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Reservasi Fasilitas</p>
                <div class="flex gap-3 text-[10px] text-gray-500">
                    <span>Total: <strong>{{ $frTotal }}</strong></span>
                    <span class="text-green-600">Selesai: <strong>{{ $frSelesai }}</strong></span>
                    <span class="text-orange-500">Berjalan: <strong>{{ $frBerjalan }}</strong></span>
                </div>
            </div>
            @if($frByFasilitas->isEmpty())
            <div class="px-4 py-8 text-center text-xs text-gray-400">Tidak ada reservasi bulan ini.</div>
            @else
            <div class="divide-y divide-gray-50 px-4 py-2">
                @php
                    $maxFr = max(1, $frByFasilitas->max('total'));
                    $frColors = ['#06b6d4','#8b5cf6','#10b981','#f97316','#3b82f6','#ec4899','#84cc16','#f59e0b','#6b7280'];
                @endphp
                @foreach($frByFasilitas as $idx => $row)
                <div class="py-2 flex items-center gap-3">
                    <div class="w-28 text-[11px] text-gray-700 truncate shrink-0" title="{{ $row->nama_fasilitas }}">{{ $row->nama_fasilitas }}</div>
                    <div class="flex-1 h-4 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-4 rounded-full" style="width:{{ ($row->total / $maxFr) * 100 }}%; background:{{ $frColors[$idx % count($frColors)] }};"></div>
                    </div>
                    <span class="text-xs font-bold text-gray-700 w-6 text-right">{{ $row->total }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ── Row 5: Complain Trend ── --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50">
            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Trend Complain per Bulan {{ $tahun }}</p>
        </div>
        <div class="px-4 py-4">
            @php $maxTrBulan = max(1, $trPerBulan->max('total')); @endphp
            <div class="flex items-end gap-1" style="height:100px;">
                @foreach($trPerBulan as $row)
                <div class="flex-1 flex flex-col items-center gap-0.5">
                    <span class="text-[8px] text-gray-400">{{ $row['total'] ?: '' }}</span>
                    <div class="w-full rounded-t" style="height:{{ max(2, ($row['total'] / $maxTrBulan) * 76) }}px; background:#fbbf24;"></div>
                    <span class="text-[8px] text-gray-400">{{ $row['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

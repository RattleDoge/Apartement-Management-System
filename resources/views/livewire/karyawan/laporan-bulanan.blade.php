<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\WorkOrder;
use App\Models\TenantRequest;
use App\Models\Invoice;
use App\Models\HandoverUnit;
use App\Models\WoFeedback;
use App\Models\FacilityReservation;

new #[Layout('layouts.karyawan')] class extends Component {

    public int $tahun;
    public int $bulan;

    public function mount(): void
    {
        $this->tahun = (int) now()->format('Y');
        $this->bulan = (int) now()->format('n');
    }

    public function downloadPdf(): void
    {
        $url = route('karyawan.laporan-bulanan.pdf', [
            'tahun' => $this->tahun,
            'bulan' => $this->bulan,
        ]);
        $this->js("window.open('{$url}', '_blank')");
    }

    public function with(): array
    {
        $y = $this->tahun;
        $m = $this->bulan;

        // WO stats for selected month
        $woBase     = fn() => WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m);
        $woTotal    = $woBase()->count();
        $woSelesai  = $woBase()->where('status_comp', 'Work Order Close')->count();
        $woPending  = $woBase()->where('status_comp', '!=', 'Work Order Close')->count();
        $woInternal = $woBase()->where('ex_in', 'IN')->count();
        $woExternal = $woBase()->where('ex_in', 'EX')->count();
        $woInSelesai = $woBase()->where('ex_in', 'IN')->where('status_comp', 'Work Order Close')->count();
        $woExSelesai = $woBase()->where('ex_in', 'EX')->where('status_comp', 'Work Order Close')->count();

        // WO per month for chart (12 months of selected year)
        $woPerBulan = collect(range(1, 12))->map(fn($mn) => [
            'bulan'    => \Carbon\Carbon::create($y, $mn, 1)->isoFormat('MMM'),
            'internal' => WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $mn)->where('ex_in', 'IN')->count(),
            'external' => WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $mn)->where('ex_in', 'EX')->count(),
        ]);

        // Tenant request stats
        $trTotal   = TenantRequest::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->count();
        $trSelesai = TenantRequest::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->where('is_selesai', true)->count();

        // Tenant request by kategori
        $trByKategori = TenantRequest::whereYear('tanggal', $y)->whereMonth('tanggal', $m)
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        // WO by jenis
        $woByJenis = WorkOrder::whereYear('tanggal', $y)->whereMonth('tanggal', $m)
            ->selectRaw('jenis_wo, COUNT(*) as total')
            ->groupBy('jenis_wo')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        // Invoice stats
        $invLunas   = Invoice::where('status_bayar', 'Lunas')->whereYear('inv_date', $y)->whereMonth('inv_date', $m)->count();
        $invBelum   = Invoice::where('status_bayar', '!=', 'Lunas')->whereYear('inv_date', $y)->whereMonth('inv_date', $m)->count();
        $invRevenue = Invoice::where('status_bayar', 'Lunas')->whereYear('tgl_bayar', $y)->whereMonth('tgl_bayar', $m)->sum('amount');

        // Unit count
        $totalUnit = HandoverUnit::count();

        // Avg WO rating
        $avgRating = WoFeedback::avg('rating');

        // Facility reservations
        $fasBase        = fn() => FacilityReservation::whereYear('tanggal_reservasi', $y)->whereMonth('tanggal_reservasi', $m);
        $fasTotal       = $fasBase()->count();
        $fasSelesai     = $fasBase()->where('status', 'Selesai')->count();
        $fasRevenue     = $fasBase()->where('is_berbayar', true)->where('status_bayar', 'Lunas')->sum('biaya');
        $fasByFasilitas = $fasBase()
            ->selectRaw('nama_fasilitas, COUNT(*) as total')
            ->groupBy('nama_fasilitas')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $tahunOptions = range(now()->year, now()->year - 3, -1);

        return compact(
            'woTotal', 'woSelesai', 'woPending',
            'woInternal', 'woExternal', 'woInSelesai', 'woExSelesai',
            'woPerBulan', 'trTotal', 'trSelesai',
            'trByKategori', 'woByJenis',
            'invLunas', 'invBelum', 'invRevenue',
            'totalUnit', 'avgRating',
            'fasTotal', 'fasSelesai', 'fasRevenue', 'fasByFasilitas',
            'tahunOptions'
        );
    }
}
?>

<div class="p-5">
    {{-- Header --}}
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            LAPORAN BULANAN
        </div>
    </div>
    <div class="flex items-center justify-end mb-5">
        <div class="flex items-center gap-2">
            <select wire:model.live="bulan"
                    class="text-xs border border-gray-300 rounded px-2 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $nm)
                <option value="{{ $i + 1 }}">{{ $nm }}</option>
                @endforeach
            </select>
            <select wire:model.live="tahun"
                    class="text-xs border border-gray-300 rounded px-2 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                @foreach($tahunOptions as $y)
                <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
            <button wire:click="downloadPdf"
                    class="flex items-center gap-1 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-1.5 rounded cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17v3a1 1 0 001 1h16a1 1 0 001-1v-3M16 6V4a1 1 0 00-1-1H9a1 1 0 00-1 1v2" />
                </svg>
                Export PDF
            </button>
        </div>
    </div>

    {{-- Stat Cards Row 1: WO --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
        @php
        $cards1 = [
            ['label'=>'WO Masuk',   'val'=>$woTotal,    'border'=>'#bfdbfe','color'=>'#1d4ed8'],
            ['label'=>'WO Selesai', 'val'=>$woSelesai,  'border'=>'#bbf7d0','color'=>'#15803d'],
            ['label'=>'WO Pending', 'val'=>$woPending,  'border'=>'#fecaca','color'=>'#dc2626'],
            ['label'=>'Complain',   'val'=>$trTotal,    'border'=>'#fde68a','color'=>'#b45309'],
        ];
        @endphp
        @foreach($cards1 as $c)
        <div class="bg-white rounded-xl p-4 shadow-sm text-center" style="border:1px solid {{ $c['border'] }};">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">{{ $c['label'] }}</p>
            <p class="text-2xl font-bold" style="color:{{ $c['color'] }};">{{ $c['val'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Stat Cards Row 2: WO Internal/External + Fasilitas + Unit --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-xl p-4 shadow-sm" style="border:1px solid #c7d2fe;">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-2">WO Internal</p>
            <p class="text-2xl font-bold" style="color:#4338ca;">{{ $woInternal }}</p>
            <p class="text-[10px] text-gray-400 mt-1">Selesai: <span class="font-semibold" style="color:#15803d;">{{ $woInSelesai }}</span></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm" style="border:1px solid #fed7aa;">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-2">WO External</p>
            <p class="text-2xl font-bold" style="color:#ea580c;">{{ $woExternal }}</p>
            <p class="text-[10px] text-gray-400 mt-1">Selesai: <span class="font-semibold" style="color:#15803d;">{{ $woExSelesai }}</span></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm" style="border:1px solid #d1fae5;">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-2">Fasilitas Tersewa</p>
            <p class="text-2xl font-bold" style="color:#059669;">{{ $fasTotal }}</p>
            <p class="text-[10px] text-gray-400 mt-1">Selesai: <span class="font-semibold" style="color:#15803d;">{{ $fasSelesai }}</span></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm" style="border:1px solid #e5e7eb;">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-2">Total Unit</p>
            <p class="text-2xl font-bold" style="color:#374151;">{{ $totalUnit }}</p>
            <p class="text-[10px] text-gray-400 mt-1">Avg Rating:
                <span class="font-semibold" style="color:#d97706;">{{ $avgRating ? number_format($avgRating,1) : '—' }} ★</span>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

        {{-- WO Per Bulan — stacked bar IN/EX --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">WO per Bulan ({{ $tahun }})</p>
                <div class="flex items-center gap-3 text-[10px] text-gray-500">
                    <span><span class="inline-block w-2.5 h-2.5 rounded-sm mr-1" style="background:#6366f1;"></span>Internal</span>
                    <span><span class="inline-block w-2.5 h-2.5 rounded-sm mr-1" style="background:#f97316;"></span>External</span>
                </div>
            </div>
            <div class="px-4 py-3">
                @php $maxWo = max(1, $woPerBulan->max(fn($r) => $r['internal'] + $r['external'])); @endphp
                <div class="flex items-end gap-1" style="height:90px;">
                    @foreach($woPerBulan as $row)
                    @php
                        $tot = $row['internal'] + $row['external'];
                        $hIn = $tot ? round(($row['internal'] / $maxWo) * 76) : 0;
                        $hEx = $tot ? round(($row['external'] / $maxWo) * 76) : 0;
                    @endphp
                    <div class="flex-1 flex flex-col items-center gap-0.5">
                        <span class="text-[7px] text-gray-400">{{ $tot ?: '' }}</span>
                        <div class="w-full flex flex-col justify-end" style="height:76px;">
                            @if($hEx > 0)<div class="w-full rounded-t" style="height:{{ $hEx }}px;background:#f97316;"></div>@endif
                            @if($hIn > 0)<div class="w-full {{ $hEx > 0 ? '' : 'rounded-t' }}" style="height:{{ $hIn }}px;background:#6366f1;"></div>@endif
                            @if($tot == 0)<div class="w-full rounded-t" style="height:4px;background:#e5e7eb;"></div>@endif
                        </div>
                        <span class="text-[7px] text-gray-400">{{ $row['bulan'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- WO by Jenis --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">WO per Jenis</p>
            </div>
            @if($woByJenis->isEmpty())
            <div class="px-4 py-6 text-center text-xs text-gray-400">Tidak ada data.</div>
            @else
            <div class="divide-y divide-gray-50">
                @php $maxJ = max(1, $woByJenis->max('total')); @endphp
                @foreach($woByJenis as $row)
                <div class="px-4 py-2 flex items-center gap-3">
                    <div class="w-24 text-xs text-gray-700 truncate shrink-0">{{ $row->jenis_wo ?: '—' }}</div>
                    <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-3 rounded-full" style="width:{{ ($row->total / $maxJ) * 100 }}%;background:#6366f1;"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-700 w-6 text-right">{{ $row->total }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Fasilitas per Nama --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Reservasi per Fasilitas</p>
                <span class="text-[10px] text-gray-400">Revenue: <strong class="text-green-700">Rp {{ number_format($fasRevenue,0,',','.') }}</strong></span>
            </div>
            @if($fasByFasilitas->isEmpty())
            <div class="px-4 py-6 text-center text-xs text-gray-400">Tidak ada reservasi bulan ini.</div>
            @else
            <div class="divide-y divide-gray-50">
                @php $maxF = max(1, $fasByFasilitas->max('total')); @endphp
                @foreach($fasByFasilitas as $row)
                <div class="px-4 py-2 flex items-center gap-3">
                    <div class="w-32 text-xs text-gray-700 truncate shrink-0">{{ $row->nama_fasilitas ?: '—' }}</div>
                    <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-3 rounded-full" style="width:{{ ($row->total / $maxF) * 100 }}%;background:#059669;"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-700 w-6 text-right">{{ $row->total }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Complain per Kategori + Invoice --}}
        <div class="space-y-3">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50">
                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Complain per Kategori</p>
                </div>
                @if($trByKategori->isEmpty())
                <div class="px-4 py-4 text-center text-xs text-gray-400">Tidak ada data.</div>
                @else
                <div class="divide-y divide-gray-50">
                    @php $maxT = max(1, $trByKategori->max('total')); @endphp
                    @foreach($trByKategori as $row)
                    <div class="px-4 py-1.5 flex items-center gap-3">
                        <div class="w-28 text-xs text-gray-700 truncate shrink-0">{{ $row->kategori ?: '—' }}</div>
                        <div class="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-2.5 rounded-full" style="width:{{ ($row->total / $maxT) * 100 }}%;background:#f59e0b;"></div>
                        </div>
                        <span class="text-xs font-semibold text-gray-700 w-5 text-right">{{ $row->total }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50">
                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Invoice Bulan Ini</p>
                </div>
                <div class="px-4 py-3 grid grid-cols-3 gap-3 text-center">
                    <div>
                        <p class="text-[10px] text-gray-400">Lunas</p>
                        <p class="text-xl font-bold" style="color:#15803d;">{{ $invLunas }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400">Belum</p>
                        <p class="text-xl font-bold" style="color:#dc2626;">{{ $invBelum }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400">Revenue</p>
                        <p class="text-[11px] font-bold" style="color:#1d4ed8;">Rp {{ number_format($invRevenue,0,',','.') }}</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>


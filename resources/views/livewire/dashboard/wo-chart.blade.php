<?php
use Livewire\Volt\Component;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $start    = '';
    public string $end      = '';
    public string $kategori = 'ALL';
    public array  $areaData = [];
    public array  $pieData  = [];

    public function mount(): void
    {
        $this->start = now()->startOfYear()->format('Y-m-d');
        $this->end   = now()->format('Y-m-d');
        $this->computeData();
    }

    public function submit(): void
    {
        $this->computeData();
        $this->dispatch('wo-chart-update', area: $this->areaData, pie: $this->pieData);
    }

    public function with(): array
    {
        return [
            'deptOptions' => array_merge(['ALL'], WorkOrder::assignDepOptions()),
        ];
    }

    private function computeData(): void
    {
        $year = (int) substr($this->start ?: now()->format('Y-m-d'), 0, 4);

        // ── Monthly per dept for the year ──
        $depts = WorkOrder::assignDepOptions();

        $rows = WorkOrder::select(
                DB::raw("CAST(strftime('%m', tanggal) AS INTEGER) as m"),
                'assign_dep',
                DB::raw('COUNT(*) as cnt')
            )
            ->whereYear('tanggal', $year)
            ->whereNotNull('assign_dep')
            ->groupBy('m', 'assign_dep')
            ->get()
            ->groupBy('assign_dep');

        $palette = [
            'ENG' => ['#3b82f6', 'rgba(59,130,246,0.15)'],
            'CS'  => ['#f97316', 'rgba(249,115,22,0.15)'],
            'SEC' => ['#8b5cf6', 'rgba(139,92,246,0.15)'],
            'HKP' => ['#22c55e', 'rgba(34,197,94,0.15)'],
            'FA'  => ['#ec4899', 'rgba(236,72,153,0.15)'],
        ];

        $datasets = [];
        foreach ($depts as $dept) {
            $monthly = array_fill(0, 12, 0);
            foreach (($rows[$dept] ?? []) as $row) {
                $monthly[$row->m - 1] = $row->cnt;
            }
            [$border, $bg] = $palette[$dept] ?? ['#6b7280', 'rgba(107,114,128,0.15)'];
            $datasets[] = [
                'label'            => $dept,
                'data'             => $monthly,
                'borderColor'      => $border,
                'backgroundColor'  => $bg,
                'fill'             => true,
                'tension'          => 0.4,
                'pointRadius'      => 4,
                'pointHoverRadius' => 6,
                'borderWidth'      => 2,
            ];
        }

        $this->areaData = [
            'year'     => $year,
            'labels'   => ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
            'datasets' => $datasets,
        ];

        // ── Selesai vs Belum Selesai ──
        $q = WorkOrder::whereBetween(DB::raw('DATE(tanggal)'), [$this->start, $this->end]);
        if ($this->kategori !== 'ALL') {
            $q->where('assign_dep', $this->kategori);
        }
        $total   = (clone $q)->count();
        $selesai = (clone $q)->whereIn('status_comp', ['Selesai', 'Work Order Close'])->count();
        $belum   = $total - $selesai;

        $this->pieData = [
            'total'      => $total,
            'selesai'    => $selesai,
            'belum'      => $belum,
            'pctSelesai' => $total > 0 ? round($selesai / $total * 100, 2) : 0,
            'pctBelum'   => $total > 0 ? round($belum   / $total * 100, 2) : 0,
            'title'      => "WORK ORDER {$this->kategori} , {$this->start} s/d {$this->end}",
        ];
    }
};
?>

<div class="mt-6 pb-8">

    {{-- ── Area Chart Title ── --}}
    <div class="text-center mb-2">
        <p class="text-xs font-semibold text-gray-500 tracking-wide">Breakdown of {{ $areaData['year'] ?? now()->year }}</p>
        <p class="text-sm font-bold text-gray-800 tracking-widest uppercase">Work Order</p>
    </div>

    {{-- ── Area Chart Canvas ── --}}
    <div wire:ignore class="mx-auto px-4" style="max-width: 900px; height: 280px;">
        <canvas id="woAreaChart"></canvas>
    </div>

    {{-- ── Filter Controls ── --}}
    <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 mt-6 mb-6 text-sm">
        <label class="flex items-center gap-2 text-gray-600">
            <span class="font-medium">Start :</span>
            <input wire:model="start" type="date"
                   class="border border-gray-400 rounded px-2 py-0.5 text-sm bg-white focus:outline-none focus:border-blue-400">
        </label>
        <label class="flex items-center gap-2 text-gray-600">
            <span class="font-medium">End :</span>
            <input wire:model="end" type="date"
                   class="border border-gray-400 rounded px-2 py-0.5 text-sm bg-white focus:outline-none focus:border-blue-400">
        </label>
        <label class="flex items-center gap-2 text-gray-600">
            <span class="font-medium">Kategori :</span>
            <select wire:model="kategori"
                    class="border border-gray-400 rounded px-2 py-0.5 text-sm bg-white focus:outline-none focus:border-blue-400">
                @foreach($deptOptions as $opt)
                <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </label>
        <button wire:click="submit" wire:loading.attr="disabled"
                class="px-5 py-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-sm rounded font-medium transition-colors">
            <span wire:loading.remove wire:target="submit">Submit</span>
            <span wire:loading wire:target="submit">Loading…</span>
        </button>
    </div>

    {{-- ── Pie Chart Title + Canvas ── --}}
    <div class="text-center mb-3">
        <p class="text-sm font-bold text-gray-800">{{ $pieData['title'] ?? '' }}</p>
    </div>

    <div wire:ignore class="mx-auto" style="max-width: 380px; height: 320px;">
        <canvas id="woPieChart"></canvas>
    </div>

    {{-- Summary numbers below pie --}}
    <div class="flex items-center justify-center gap-8 mt-4 text-xs">
        <div class="flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full" style="background:#e91e8c;"></span>
            <span class="text-gray-600 font-medium">SELESAI</span>
            <span class="font-bold text-gray-800">{{ number_format($pieData['selesai'] ?? 0) }}</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full" style="background:#4caf50;"></span>
            <span class="text-gray-600 font-medium">BELUM SELESAI</span>
            <span class="font-bold text-gray-800">{{ number_format($pieData['belum'] ?? 0) }}</span>
        </div>
        <div class="text-gray-400">
            Total: <span class="font-semibold text-gray-700">{{ number_format($pieData['total'] ?? 0) }}</span>
        </div>
    </div>

</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
@endassets

@script
<script>
    let _areaChart = null;
    let _pieChart  = null;

    function buildAreaChart(data) {
        const ctx = document.getElementById('woAreaChart');
        if (!ctx) return;
        if (_areaChart) { _areaChart.destroy(); _areaChart = null; }
        _areaChart = new Chart(ctx, {
            type: 'line',
            data: { labels: data.labels, datasets: data.datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'start',
                        labels: { boxWidth: 18, boxHeight: 10, font: { size: 11 }, padding: 14 }
                    },
                    tooltip: { mode: 'index' }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { size: 11 }, stepSize: 10 }
                    }
                }
            }
        });
    }

    function buildPieChart(data) {
        const ctx = document.getElementById('woPieChart');
        if (!ctx) return;
        if (_pieChart) { _pieChart.destroy(); _pieChart = null; }
        _pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [
                    'SELESAI (' + data.pctSelesai + '%)',
                    'BELUM SELESAI (' + data.pctBelum + '%)',
                ],
                datasets: [{
                    data: [data.selesai || 0, data.belum || 0],
                    backgroundColor: ['#e91e8c', '#4caf50'],
                    borderWidth: 1,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => '  ' + ctx.label + ': ' + ctx.raw + ' WO'
                        }
                    }
                }
            }
        });
    }

    // ── Initial render ──
    buildAreaChart(@json($areaData));
    buildPieChart(@json($pieData));

    // ── After submit ──
    $wire.on('wo-chart-update', ({ area, pie }) => {
        buildAreaChart(area);
        buildPieChart(pie);
    });
</script>
@endscript

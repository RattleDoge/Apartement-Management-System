<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\WorkOrder;
use App\Models\Tenant;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination;

    public int $filterYear = 0;
    public int $selectedId = 0;
    public int $perPage    = 10;

    // Filters
    public string $fExIn        = '';
    public string $fNoComplain  = '';
    public string $fNoWo        = '';
    public string $fDate        = '';
    public string $fLotNo       = '';
    public string $fName        = '';
    public string $fDescs       = '';
    public string $fRequestBy   = '';
    public string $fRequestVia  = '';
    public string $fAssignTo    = '';
    public string $fWorkStarted = '';
    public string $fClosingDate = '';
    public string $fDurasi      = '';
    public string $fDurasiiBln  = '';
    public string $fActionBy    = '';
    public string $fActionTaken = '';

    public function mount(): void
    {
        $this->filterYear = now()->year;
    }

    public function updated($prop): void
    {
        if (str_starts_with($prop, 'f') || $prop === 'perPage') {
            $this->resetPage();
        }
    }

    public function selectRow(int $id): void
    {
        $this->selectedId = ($this->selectedId === $id) ? 0 : $id;
    }

    public function applyYear(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $year = $this->filterYear ?: now()->year;

        $query = WorkOrder::whereYear('tanggal', $year)
            ->where(function ($q) {
                $q->where('status_comp', 'Work Order Close')
                  ->orWhereNotNull('work_closed');
            })
            ->when($this->fExIn,        fn($q) => $q->where('ex_in', $this->fExIn))
            ->when($this->fNoComplain,  fn($q) => $q->where('no_complain', 'like', "%{$this->fNoComplain}%"))
            ->when($this->fNoWo,        fn($q) => $q->where('no_wo', 'like', "%{$this->fNoWo}%"))
            ->when($this->fDate,        fn($q) => $q->whereDate('tanggal', $this->fDate))
            ->when($this->fLotNo,       fn($q) => $q->where('lot_no', 'like', "%{$this->fLotNo}%"))
            ->when($this->fName,        fn($q) => $q->where('name', 'like', "%{$this->fName}%"))
            ->when($this->fDescs,       fn($q) => $q->where('descs', 'like', "%{$this->fDescs}%"))
            ->when($this->fRequestBy,   fn($q) => $q->where('request_by', 'like', "%{$this->fRequestBy}%"))
            ->when($this->fRequestVia,  fn($q) => $q->where('request_via', $this->fRequestVia))
            ->when($this->fAssignTo,    fn($q) => $q->where('assign_dep', $this->fAssignTo))
            ->when($this->fDurasiiBln,  fn($q) => $q->where('durasi_bln', $this->fDurasiiBln))
            ->when($this->fActionBy,    fn($q) => $q->where('action_by', 'like', "%{$this->fActionBy}%"))
            ->when($this->fActionTaken, fn($q) => $q->where('action_taken', 'like', "%{$this->fActionTaken}%"))
            ->orderByDesc('work_closed')
            ->orderByDesc('tanggal');

        $workOrders = $query->paginate($this->perPage);

        // STR lookup: units that have a tenant record = sudah STR
        $lotNos = $workOrders->pluck('lot_no')->filter()
            ->map(fn($l) => strtoupper(trim($l)))->unique()->values()->toArray();
        $strSet = Tenant::get()
            ->pluck('unit_number')
            ->map(fn($u) => strtoupper(trim($u)))
            ->flip()
            ->toArray();

        $selectedWo = $this->selectedId > 0 ? WorkOrder::find($this->selectedId) : null;
        $total      = $query->getQuery()->getCountForPagination();

        return compact('workOrders', 'strSet', 'selectedWo');
    }
}; ?>

<div class="px-3 py-3">

    {{-- ── Year filter ── --}}
    <div class="flex items-center gap-2 mb-3 text-[12px]">
        <label class="text-gray-600 font-semibold">Pilih Tahun</label>
        <select wire:model="filterYear" class="border border-gray-400 px-2 py-0.5 text-[12px] bg-white">
            @for ($y = now()->year; $y >= now()->year - 5; $y--)
                <option value="{{ $y }}">{{ $y }}</option>
            @endfor
        </select>
        <button wire:click="applyYear"
            class="px-4 py-0.5 border border-gray-400 bg-gray-100 hover:bg-gray-200 text-[12px]">
            Tampilkan
        </button>
    </div>

    {{-- ── Table ── --}}
    <div class="border border-blue-200 overflow-x-auto rounded-lg shadow-sm" style="font-size: 11px;">
        <div class="font-bold text-sm px-3 py-2 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            WORK ORDER CLOSE
        </div>
        <table class="border-collapse" style="min-width: 2400px; width: 100%;">
            <thead>
                {{-- Header row --}}
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-1 py-1.5 text-center w-7">#</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:50px;">EX/IN</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:160px;">NO COMPLAIN</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:170px;">NO WO</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:130px;">DATE</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:80px;">LOT NO</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:140px;">NAME</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:200px;">DESCS</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:130px;">REQUEST BY</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:90px;">REQUEST VIA</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:100px;">ASSIGN TO</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:140px;">WORK STARTED</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:140px;">CLOSING DATE</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:90px;">DURASI</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:90px;">DURASI BLN</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:130px;">ACTION BY</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:200px;">ACTION TAKEN</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:60px;">POLLING</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:80px;">STATUS STR</th>
                </tr>

                {{-- Filter row --}}
                <tr style="background-color: #f5f5f5;">
                    <th class="border border-gray-400 px-1 py-0.5"></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <select wire:model.live="fExIn" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                            <option value=""></option>
                            <option value="IN">IN</option>
                            <option value="EX">EX</option>
                        </select>
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fNoComplain" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" />
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fNoWo" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" />
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live="fDate" type="date"
                            class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white" />
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fLotNo" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" />
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fName" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" />
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fDescs" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" />
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fRequestBy" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" />
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <select wire:model.live="fRequestVia" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                            <option value=""></option>
                            @foreach (\App\Models\WorkOrder::requestViaOptions() as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <select wire:model.live="fAssignTo" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                            <option value=""></option>
                            @foreach (\App\Models\WorkOrder::assignDepOptions() as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live="fWorkStarted" type="date"
                            class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white" />
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live="fClosingDate" type="date"
                            class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white" />
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fDurasi" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" />
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <select wire:model.live="fDurasiiBln" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                            <option value=""></option>
                            <option value="kurang1bln">kurang1bln</option>
                            <option value="1-3bln">1-3bln</option>
                            <option value="lebih3bln">lebih3bln</option>
                        </select>
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fActionBy" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" />
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fActionTaken" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" />
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5"></th>
                    <th class="border border-gray-400 px-1 py-0.5"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($workOrders as $i => $wo)
                @php
                    $rowNo    = ($workOrders->currentPage() - 1) * $workOrders->perPage() + $i + 1;
                    $isSel    = $selectedId === $wo->id;
                    $isIN     = $wo->ex_in === 'IN';
                    $bgStyle  = $isSel
                        ? 'background-color:#d0e8ff;'
                        : ($isIN ? 'background-color:#fffacd;' : 'background-color:#ffffff;');

                    $lotUpper = strtoupper(trim($wo->lot_no ?? ''));
                    $isStr    = ($lotUpper === 'BM' || isset($strSet[$lotUpper]));

                    // Calculate closing duration
                    $durasi = $wo->durasi;
                    if (! $durasi && $wo->work_started && $wo->work_closed) {
                        $mins = (int) $wo->work_started->diffInMinutes($wo->work_closed);
                        if ($mins < 60) $durasi = $mins . ' Menit';
                        elseif ($mins < 1440) $durasi = floor($mins/60) . ' Jam ' . ($mins%60) . ' Menit';
                        else $durasi = floor($mins/1440) . ' Hari ' . floor(($mins%1440)/60) . ' Jam';
                    }
                @endphp
                <tr wire:click="selectRow({{ $wo->id }})" style="{{ $bgStyle }}" class="cursor-pointer hover:opacity-80">
                    <td class="border border-gray-300 px-1 py-0.5 text-center text-gray-500 align-top">{{ $rowNo }}</td>

                    <td class="border border-gray-300 px-1 py-0.5 text-center font-medium align-top text-gray-700">
                        {{ $wo->ex_in }}
                    </td>

                    {{-- NO COMPLAIN --}}
                    <td class="border border-gray-300 px-2 py-0.5 align-top">
                        <div class="text-gray-700 font-medium">{{ $wo->no_complain ?? '-' }}</div>
                        @if($wo->tanggal)
                        <div class="text-gray-400 text-[10px]">{{ $wo->tanggal->format('Y-m-d H:i:s') }}</div>
                        @endif
                    </td>

                    {{-- NO WO --}}
                    <td class="border border-gray-300 px-2 py-0.5 align-top">
                        <a href="{{ route('karyawan.cs.work-order.print', $wo->id) }}" target="_blank"
                           class="text-[#1a6b9a] hover:underline font-medium">{{ $wo->no_wo }}</a>
                        @if($wo->input_by)
                        <div class="text-gray-400 text-[10px]">dibuat oleh {{ $wo->input_by }}</div>
                        @endif
                    </td>

                    <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700 whitespace-nowrap">
                        {{ $wo->tanggal?->format('Y-m-d H:i:s') }}
                    </td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top font-medium text-gray-700 text-center">
                        {{ $wo->lot_no ?? 'BM' }}
                    </td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700">{{ $wo->name }}</td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700" style="max-width:200px; word-wrap:break-word; white-space:normal;">
                        {{ $wo->descs }}
                    </td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700">{{ $wo->request_by }}</td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-600 text-center">{{ $wo->request_via }}</td>

                    {{-- ASSIGN TO: dep + staff --}}
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700">
                        <div class="font-medium">{{ $wo->assign_dep }}</div>
                        @if($wo->assign_staff)
                        <div class="text-[10px] text-gray-500">{{ $wo->assign_staff }}</div>
                        @endif
                    </td>

                    <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700 whitespace-nowrap">
                        @if($wo->work_started)
                            <div>{{ $wo->work_started->format('Y-m-d') }}</div>
                            <div class="text-gray-500 text-[10px]">{{ $wo->work_started->format('H:i:s') }}</div>
                        @else
                            <span class="text-gray-300">-</span>
                        @endif
                    </td>

                    <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700 whitespace-nowrap">
                        @if($wo->work_closed)
                            <div>{{ $wo->work_closed->format('Y-m-d') }}</div>
                            <div class="text-gray-500 text-[10px]">{{ $wo->work_closed->format('H:i:s') }}</div>
                        @else
                            <span class="text-gray-300">-</span>
                        @endif
                    </td>

                    <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-600 whitespace-nowrap text-center">
                        {{ $durasi ?? '-' }}
                    </td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-600 text-center">
                        {{ $wo->durasi_bln }}
                    </td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700">
                        {{ $wo->action_by ?? '-' }}
                    </td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700"
                        style="max-width:200px; word-wrap:break-word; white-space:normal;">
                        {{ $wo->action_taken ?? '-' }}
                    </td>

                    {{-- POLLING (empty) --}}
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-center text-gray-300">-</td>

                    {{-- STATUS STR --}}
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-center font-semibold">
                        @if($isStr)
                            <span style="color:#1a6b3c;">STR</span>
                        @else
                            <span class="text-red-600">BELUM STR</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="19" class="border border-gray-300 px-4 py-6 text-center text-gray-400">
                        Tidak ada data Work Order Close untuk tahun {{ $filterYear }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Action Buttons ── --}}
    @php $sel = $selectedId > 0; @endphp
    <div class="flex items-center gap-1 mt-2">
        @if($sel)
            <a href="{{ route('karyawan.cs.work-order.print', $selectedId) }}" target="_blank"
               class="flex items-center gap-1 px-3 py-1 border border-gray-400 bg-gray-100 text-[11px] hover:bg-gray-200">
                🖨 Print WO
            </a>
            <a href="{{ route('karyawan.cs.work-order.print', $selectedId) }}" target="_blank"
               class="flex items-center gap-1 px-3 py-1 border border-gray-400 bg-gray-100 text-[11px] hover:bg-gray-200">
                🖨 Print WO 2
            </a>
        @else
            <button disabled class="flex items-center gap-1 px-3 py-1 border border-gray-300 bg-gray-50 text-gray-400 text-[11px] cursor-not-allowed">
                🖨 Print WO
            </button>
            <button disabled class="flex items-center gap-1 px-3 py-1 border border-gray-300 bg-gray-50 text-gray-400 text-[11px] cursor-not-allowed">
                🖨 Print WO 2
            </button>
        @endif
    </div>

    {{-- ── Pagination ── --}}
    <div class="flex items-center justify-between mt-2 text-[11px] text-gray-600">
        <div class="flex items-center gap-1">
            @php
                $cur  = $workOrders->currentPage();
                $last = $workOrders->lastPage();
                $nums = collect();
                for ($p = 1; $p <= $last; $p++) {
                    if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
                }
                $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
                $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
                $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
            @endphp
            @if($workOrders->onFirstPage())
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
            @if($workOrders->hasMorePages())
                <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
                <button wire:click="setPage({{ $last }})" class="{{ $pBtn }}">›|</button>
            @else
                <span class="{{ $pDis }}">›</span>
                <span class="{{ $pDis }}">›|</span>
            @endif
            <span class="ml-3">
                <select wire:model.live="perPage" class="border border-gray-400 text-[11px] px-1 py-0.5 bg-white">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </span>
        </div>
        <span class="text-gray-500">
            View {{ $workOrders->firstItem() ?? 0 }}–{{ $workOrders->lastItem() ?? 0 }} of {{ $workOrders->total() }}
        </span>
    </div>

    {{-- ── Note ── --}}
    <div class="mt-5 border-2 border-red-500 p-3 inline-block text-[11px] text-gray-700">
        <p class="font-semibold mb-1">Note:</p>
        <p>* CS (Pengelola): "Tenant Request" atas unit <strong>SUDAH</strong> serah terima unit (STR)</p>
        <p>* CR (Dev): "Tenant Request" atas unit <strong>BELUM</strong> serah terima unit (STR)</p>
    </div>

</div>

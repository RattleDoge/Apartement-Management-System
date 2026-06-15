<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\TenantRequest;
use App\Models\WorkOrder;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination;

    // Per-column filters
    public string $fNoRequest     = '';
    public string $fTglVerifikasi = '';
    public string $fKategori      = '';
    public string $fSubKategori   = '';
    public string $fLotNo         = '';
    public string $fTglStr        = '';
    public string $fNama          = '';
    public string $fKepemilikan   = '';
    public string $fSalesAgent    = '';
    public string $fDescs         = '';
    public string $fRequestBy     = '';
    public string $fRequestVia    = '';
    public string $fStatus        = '';
    public string $fDescStatus    = '';

    public int $perPage = 25;

    public bool  $showDetail = false;
    public array $detailData = [];

    public function openDetail(int $id): void
    {
        $req = TenantRequest::find($id);
        if (! $req) return;

        $wo = WorkOrder::where('no_complain', $req->no_request)->first();

        $this->detailData = [
            'no_request'        => $req->no_request,
            'tanggal'           => $req->tanggal?->format('d M Y H:i'),
            'kategori'          => $req->kategori,
            'sub_kategori'      => $req->sub_kategori,
            'lot_no'            => $req->lot_no,
            'nama'              => $req->nama,
            'descs'             => $req->descs,
            'request_by'        => $req->request_by,
            'pelaporan_via'     => $req->pelaporan_via,
            'status'            => $req->status,
            'tgl_verifikasi'    => $req->tgl_verifikasi?->format('d M Y'),
            'tgl_dalam_proses'  => $req->tgl_dalam_proses?->format('d M Y'),
            'tgl_selesai'       => $req->tgl_selesai?->format('d M Y'),
            'done_by'           => $req->done_by,
            'desc_status'       => $req->desc_status,
            'foto'              => $req->foto,
            'input_by'          => $req->input_by,
            'foto_pengecekan'   => $wo?->foto_pengecekan,
            'foto_close'        => $wo?->foto_close,
            'balas_by'          => $wo?->balas_by,
            'action_by'         => $wo?->action_by,
        ];
        $this->showDetail = true;
    }

    public function updated($prop): void
    {
        if (str_starts_with($prop, 'f') || $prop === 'perPage') {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $query = TenantRequest::where('is_selesai', true)
            ->when($this->fNoRequest,     fn($q) => $q->where('no_request',   'like', "%{$this->fNoRequest}%"))
            ->when($this->fTglVerifikasi, fn($q) => $q->whereDate('tgl_verifikasi', $this->fTglVerifikasi))
            ->when($this->fKategori,      fn($q) => $q->where('kategori',     'like', "%{$this->fKategori}%"))
            ->when($this->fSubKategori,   fn($q) => $q->where('sub_kategori', 'like', "%{$this->fSubKategori}%"))
            ->when($this->fLotNo,         fn($q) => $q->where('lot_no',       'like', "%{$this->fLotNo}%"))
            ->when($this->fTglStr,        fn($q) => $q->whereDate('tgl_str',  $this->fTglStr))
            ->when($this->fNama,          fn($q) => $q->where('nama',         'like', "%{$this->fNama}%"))
            ->when($this->fKepemilikan,   fn($q) => $q->where('kepemilikan',  'like', "%{$this->fKepemilikan}%"))
            ->when($this->fSalesAgent,    fn($q) => $q->where('sales_agent',  'like', "%{$this->fSalesAgent}%"))
            ->when($this->fDescs,         fn($q) => $q->where('descs',        'like', "%{$this->fDescs}%"))
            ->when($this->fRequestBy,     fn($q) => $q->where('request_by',   'like', "%{$this->fRequestBy}%"))
            ->when($this->fRequestVia,    fn($q) => $q->where('pelaporan_via', $this->fRequestVia))
            ->when($this->fStatus,        fn($q) => $q->where('status',       $this->fStatus))
            ->when($this->fDescStatus,    fn($q) => $q->where('desc_status',  'like', "%{$this->fDescStatus}%"))
            ->orderByDesc('tanggal');

        $requests = $query->paginate($this->perPage);

        $noRequests = $requests->pluck('no_request')->filter()->values()->toArray();
        $linkedWos  = WorkOrder::whereIn('no_complain', $noRequests)->get()->keyBy('no_complain');

        return compact('requests', 'linkedWos');
    }
}; ?>

<div class="px-3 py-3">

    {{-- Table --}}
    <div class="border border-blue-200 overflow-x-auto rounded-lg shadow-sm" style="font-size: 11px;">
        <div class="font-bold text-sm px-3 py-2 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            TENANT REQUEST SELESAI
        </div>
        <table class="border-collapse" style="min-width: 2200px; width: 100%;">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-1 py-1.5 text-center w-7">#</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:180px;">NOMOR/DATE REQUEST</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:100px;">TGL.VERIFIKASI</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:120px;">CATEGORY</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:120px;">SUB CATEGORY</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:80px;">LOT NO</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:90px;">TGL. STR</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:140px;">NAMA</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:90px;">KEPEMILIKAN</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:90px;">SALES/AGENT</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:200px;">DESCS</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:120px;">REQUEST BY</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:90px;">REQUEST VIA</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:120px;">STATUS</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:70px;">DONE</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:70px;">PICTURE</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:170px;">DESC STATUS</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center" style="min-width:160px;">WORK ORDER</th>
                </tr>
                {{-- Filter row --}}
                <tr style="background-color:#f5f5f5;">
                    <th class="border border-gray-400 px-1 py-0.5"></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fNoRequest" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live="fTglVerifikasi" type="date"
                            class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white" /></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fKategori" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fSubKategori" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fLotNo" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live="fTglStr" type="date"
                            class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white" /></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fNama" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fKepemilikan" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fSalesAgent" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fDescs" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fRequestBy" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <select wire:model.live="fRequestVia" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                            <option value=""></option>
                            @foreach(\App\Models\TenantRequest::pelaporanViaOptions() as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <select wire:model.live="fStatus" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                            <option value=""></option>
                            @foreach(\App\Models\TenantRequest::statusOptions() as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="border border-gray-400 px-1 py-0.5"></th>
                    <th class="border border-gray-400 px-1 py-0.5"></th>
                    <th class="border border-gray-400 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fDescStatus" type="text"
                            class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                    <th class="border border-gray-400 px-1 py-0.5"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $idx => $req)
                @php
                    $linkedWo = $linkedWos->get($req->no_request);
                    $fotoUrl  = $req->foto ? asset('storage/' . $req->foto) : null;
                    $isPdf    = $req->foto && str_ends_with(strtolower($req->foto), '.pdf');
                @endphp
                <tr class="hover:opacity-80" style="background-color:#f0f8f0;">
                    <td class="border border-gray-300 px-1 py-0.5 text-center text-gray-500 align-top">
                        {{ $requests->firstItem() + $idx }}
                    </td>
                    {{-- NOMOR/DATE REQUEST --}}
                    <td class="border border-gray-300 px-2 py-0.5 align-top">
                        <button wire:click="openDetail({{ $req->id }})"
                                class="font-mono font-semibold text-[#1a6b9a] hover:underline text-left">
                            {{ $req->no_request }}
                        </button>
                        <div class="text-gray-400 text-[10px]">{{ $req->tanggal?->format('Y-m-d H:i:s') }}</div>
                    </td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-center">
                        {{ $req->tgl_verifikasi?->format('Y-m-d') ?? '-' }}
                    </td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top">{{ $req->kategori ?? '-' }}</td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top">{{ $req->sub_kategori ?? '-' }}</td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-center font-semibold">{{ $req->lot_no ?? '-' }}</td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-center">
                        {{ $req->tgl_str?->format('Y-m-d') ?? '-' }}
                    </td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top font-semibold">{{ $req->nama ?? '-' }}</td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-center">{{ $req->kepemilikan ?? '-' }}</td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-center">{{ $req->sales_agent ?? '-' }}</td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top"
                        style="max-width:200px;word-wrap:break-word;white-space:normal;">{{ $req->descs ?? '-' }}</td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top">{{ $req->request_by ?? '-' }}</td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-center">{{ $req->pelaporan_via ?? '-' }}</td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-center font-semibold" style="color:#1a6b3c;">
                        {{ $req->status ?? '-' }}
                    </td>
                    {{-- DONE --}}
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-center text-gray-600">
                        {{ $req->done_by ?? $req->input_by ?? '-' }}
                    </td>
                    {{-- PICTURE --}}
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-center">
                        @if($fotoUrl)
                            <a href="{{ $fotoUrl }}" target="_blank"
                               class="text-blue-600 hover:underline text-[10px] font-semibold leading-tight">
                                Lampiran<br>Foto
                            </a>
                        @endif
                    </td>
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-[10px]"
                        style="white-space:pre-line;max-width:170px;word-wrap:break-word;">{{ $req->desc_status ?? '-' }}</td>
                    {{-- WORK ORDER --}}
                    <td class="border border-gray-300 px-2 py-0.5 align-top text-center">
                        @if($linkedWo)
                            <a href="{{ route('karyawan.cs.work-order.print', $linkedWo->id) }}" target="_blank"
                               class="font-mono text-[10px] font-semibold underline hover:opacity-80"
                               style="color:#1a6b9a;">{{ $linkedWo->no_wo }}</a>
                            <div class="text-[10px] text-gray-500">{{ $linkedWo->status_comp ?? '-' }}</div>
                        @else
                            <span class="text-gray-300 text-[10px]">–</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="18" class="border border-gray-300 px-4 py-6 text-center text-gray-400">
                        Belum ada tenant request yang selesai.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="flex items-center justify-between mt-2 text-[11px] text-gray-600">
        <div class="flex items-center gap-1">
            @php
                $cur  = $requests->currentPage();
                $last = $requests->lastPage();
                $nums = collect();
                for ($p = 1; $p <= $last; $p++) {
                    if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
                }
                $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
                $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
                $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
            @endphp
            @if($requests->onFirstPage())
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
            @if($requests->hasMorePages())
                <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
                <button wire:click="setPage({{ $last }})" class="{{ $pBtn }}">›|</button>
            @else
                <span class="{{ $pDis }}">›</span>
                <span class="{{ $pDis }}">›|</span>
            @endif
            <span class="ml-3">
                <select wire:model.live="perPage" class="border border-gray-400 text-[11px] px-1 py-0.5 bg-white">
                    <option value="25">25</option><option value="50">50</option>
                </select>
            </span>
        </div>
        <span>View {{ $requests->firstItem() ?? 0 }}–{{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }}</span>
    </div>

    <div class="mt-3">
        <a href="{{ route('karyawan.cs.tenant-request-belum') }}"
            class="px-3 py-1 border border-gray-400 bg-gray-100 hover:bg-gray-200 text-[11px] inline-flex items-center gap-1">
            ← Kembali ke Belum Selesai
        </a>
    </div>

    {{-- Modal Detail Request --}}
    @if($showDetail && $detailData)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
         wire:click="$set('showDetail', false)">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm mx-4 overflow-y-auto"
             style="max-height:90vh;" wire:click.stop>

            <div class="bg-[#1e3a8a] text-white px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold tracking-wide uppercase">Tenant Request</p>
                    <p class="text-[11px] font-mono mt-0.5 opacity-90">{{ $detailData['no_request'] }}</p>
                </div>
                <button wire:click="$set('showDetail', false)" class="text-white/70 hover:text-white text-xl leading-none">✕</button>
            </div>

            <div class="px-5 py-4 space-y-3 text-[12px]">

                @if($detailData['kategori'])
                <div class="flex gap-2 flex-wrap">
                    <span class="bg-blue-100 text-blue-700 text-[10px] font-semibold px-2 py-0.5 rounded">{{ $detailData['kategori'] }}</span>
                    @if($detailData['sub_kategori'])
                    <span class="bg-gray-100 text-gray-600 text-[10px] px-2 py-0.5 rounded">{{ $detailData['sub_kategori'] }}</span>
                    @endif
                </div>
                @endif

                <table class="w-full text-[11px]" style="border-collapse:collapse;">
                    <tr><td class="text-gray-400 py-0.5 w-28">Lot No</td><td class="font-semibold">{{ $detailData['lot_no'] ?? '-' }}</td></tr>
                    <tr><td class="text-gray-400 py-0.5">Nama</td><td>{{ $detailData['nama'] ?? '-' }}</td></tr>
                    <tr><td class="text-gray-400 py-0.5">Request By</td><td>{{ $detailData['request_by'] ?? '-' }}</td></tr>
                    <tr><td class="text-gray-400 py-0.5">Via</td><td>{{ $detailData['pelaporan_via'] ?? '-' }}</td></tr>
                    <tr><td class="text-gray-400 py-0.5">Tanggal</td><td>{{ $detailData['tanggal'] }}</td></tr>
                </table>

                @if($detailData['descs'])
                <p class="text-gray-700 leading-relaxed bg-gray-50 rounded-lg px-3 py-2">{{ $detailData['descs'] }}</p>
                @endif

                {{-- Timeline --}}
                @php
                    $s = $detailData['status'] ?? '';
                    $pengecekanOn = $detailData['tgl_verifikasi']   || in_array($s, ['Dalam Pengecekan','Dalam Proses','Selesai']);
                    $prosesOn     = $detailData['tgl_dalam_proses'] || in_array($s, ['Dalam Proses','Selesai']);
                    $selesaiOn    = $detailData['tgl_selesai']      || $s === 'Selesai';
                @endphp
                <div class="space-y-2">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Timeline</p>
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                        <div><p class="font-semibold text-gray-700">Pesan Diterima</p>
                        <p class="text-gray-400 text-[10px]">{{ $detailData['input_by'] ?? '-' }} — {{ $detailData['tanggal'] }}</p></div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 w-2 h-2 rounded-full {{ $pengecekanOn ? 'bg-blue-500' : 'bg-gray-200' }} shrink-0"></span>
                        <div><p class="font-semibold {{ $pengecekanOn ? 'text-gray-700' : 'text-gray-300' }}">Dalam Pengecekan</p>
                        @if($detailData['tgl_verifikasi'])<p class="text-gray-400 text-[10px]">{{ $detailData['tgl_verifikasi'] }}</p>@endif</div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 w-2 h-2 rounded-full {{ $prosesOn ? 'bg-amber-500' : 'bg-gray-200' }} shrink-0"></span>
                        <div><p class="font-semibold {{ $prosesOn ? 'text-gray-700' : 'text-gray-300' }}">Dalam Proses</p>
                        @if($detailData['tgl_dalam_proses'])<p class="text-gray-400 text-[10px]">{{ $detailData['tgl_dalam_proses'] }}</p>@endif</div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 w-2 h-2 rounded-full {{ $selesaiOn ? 'bg-green-700' : 'bg-gray-200' }} shrink-0"></span>
                        <div><p class="font-semibold {{ $selesaiOn ? 'text-gray-700' : 'text-gray-300' }}">Selesai</p>
                        @if($detailData['tgl_selesai'])<p class="text-gray-400 text-[10px]">{{ $detailData['done_by'] ?? '' }} — {{ $detailData['tgl_selesai'] }}</p>@endif</div>
                    </div>
                </div>

                @if($detailData['desc_status'])
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                    <p class="text-[10px] text-amber-600 font-semibold mb-0.5">Keterangan</p>
                    <p class="text-gray-700">{{ $detailData['desc_status'] }}</p>
                </div>
                @endif

                @php
                    $semuaFoto = array_filter([
                        'Foto Tenant'     => $detailData['foto'] ?? null,
                        'Foto Pengecekan' => $detailData['foto_pengecekan'] ?? null,
                        'Foto Closing'    => $detailData['foto_close'] ?? null,
                    ]);
                @endphp
                @if(count($semuaFoto))
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-2">Foto</p>
                    <div class="space-y-3">
                        @foreach($semuaFoto as $label => $path)
                        <div x-data="{ lb: false }">
                            <p class="text-[10px] text-gray-500 mb-1">{{ $label }}</p>
                            <img src="{{ asset('storage/' . $path) }}" alt="{{ $label }}"
                                 class="rounded-lg max-h-36 object-contain border border-gray-200 cursor-zoom-in"
                                 @click="lb = true">
                            <div x-show="lb" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80" @click="lb = false">
                                <img src="{{ asset('storage/' . $path) }}" alt="{{ $label }}"
                                     class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain shadow-2xl">
                                <button class="absolute top-4 right-4 text-white text-2xl" @click="lb = false">✕</button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="pt-2 border-t border-gray-100 flex justify-end">
                    <button wire:click="$set('showDetail', false)"
                            class="text-xs text-gray-500 hover:text-gray-700 px-4 py-1.5 border border-gray-200 rounded-lg">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

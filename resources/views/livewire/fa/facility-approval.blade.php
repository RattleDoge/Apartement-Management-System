<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\FacilityReservation;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination;

    public string $search         = '';
    public string $filterFasilitas= '';
    public string $filterTab      = 'pending'; // pending | confirmed | all

    public ?int  $viewingId  = null;
    public bool  $showPanel  = false;
    public bool  $showConfirm= false;
    public string $catatan   = '';

    public function updatedSearch(): void          { $this->resetPage(); }
    public function updatedFilterFasilitas(): void { $this->resetPage(); }
    public function updatedFilterTab(): void       { $this->resetPage(); }

    public function with(): array
    {
        $q = FacilityReservation::where('is_berbayar', true)
            ->whereNotNull('cs_by')
            ->orderByDesc('tanggal_reservasi');

        match ($this->filterTab) {
            'pending'   => $q->whereNull('fin_by'),
            'confirmed' => $q->whereNotNull('fin_by'),
            default     => null,
        };

        if ($this->filterFasilitas) {
            $q->where('nama_fasilitas', $this->filterFasilitas);
        }
        if ($this->search) {
            $q->where(function ($sub) {
                $sub->where('nomor',        'like', "%{$this->search}%")
                    ->orWhere('unit',        'like', "%{$this->search}%")
                    ->orWhere('tenant_name', 'like', "%{$this->search}%");
            });
        }

        return [
            'rows'    => $q->paginate(15),
            'viewing' => $this->viewingId ? FacilityReservation::find($this->viewingId) : null,
            'fasilitasOptions' => FacilityReservation::fasilitasOptions(),
        ];
    }

    public function openDetail(int $id): void
    {
        $this->viewingId   = $id;
        $this->showPanel   = true;
        $this->showConfirm = false;
        $this->catatan     = '';
    }

    public function closePanel(): void
    {
        $this->showPanel   = false;
        $this->showConfirm = false;
        $this->viewingId   = null;
        $this->catatan     = '';
    }

    public function confirmPayment(int $id): void
    {
        $res = FacilityReservation::findOrFail($id);
        if ($res->fin_by) return; // already confirmed

        $res->update([
            'fin_by'      => auth()->user()->name,
            'fin_at'      => now(),
            'status_bayar'=> 'Sudah Bayar',
            // Promote to "Siap Pelaksanaan" if HK & ENG already signed off
            'status'      => ($res->hk_by && $res->eng_by) ? 'Siap Pelaksanaan' : $res->status,
        ]);

        $this->viewingId   = $id;
        $this->showConfirm = false;
        session()->flash('flash', "Pembayaran reservasi {$res->nomor} dikonfirmasi.");
    }
};
?>

<div class="p-6 max-w-screen-xl mx-auto" x-data>

    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <span>KONFIRMASI PEMBAYARAN FASILITAS</span>
            <span class="text-xs font-normal opacity-80">Reservasi berbayar yang perlu konfirmasi Finance</span>
        </div>
    </div>

    @if(session('flash'))
    <div class="mb-4 px-4 py-2 bg-green-50 border border-green-300 text-green-800 text-sm rounded">
        {{ session('flash') }}
    </div>
    @endif

    {{-- Filter bar --}}
    <div class="flex flex-wrap gap-2 mb-4">
        <input wire:model.live.debounce.300ms="search"
               type="text" placeholder="Cari nomor / unit / nama…"
               class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#2a8c56] w-56">

        <select wire:model.live="filterFasilitas"
                class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#2a8c56]">
            <option value="">Semua Fasilitas</option>
            @foreach($fasilitasOptions as $f)
            <option value="{{ $f }}">{{ $f }}</option>
            @endforeach
        </select>

        <div class="flex border border-gray-300 rounded overflow-hidden text-xs">
            @foreach(['pending' => 'Menunggu Konfirmasi', 'confirmed' => 'Sudah Dikonfirmasi', 'all' => 'Semua'] as $val => $lbl)
            <button wire:click="$set('filterTab', '{{ $val }}')"
                    class="px-3 py-1.5 transition-colors
                           {{ $filterTab === $val ? 'bg-[#1a5c2e] text-white font-semibold' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                {{ $lbl }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto border border-gray-200 rounded">
        <table class="min-w-full text-xs">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="px-3 py-2 text-left font-semibold">NOMOR</th>
                    <th class="px-3 py-2 text-left font-semibold">UNIT</th>
                    <th class="px-3 py-2 text-left font-semibold">TENANT</th>
                    <th class="px-3 py-2 text-left font-semibold">FASILITAS</th>
                    <th class="px-3 py-2 text-center font-semibold">TGL RESERVASI</th>
                    <th class="px-3 py-2 text-right font-semibold">BIAYA (Rp)</th>
                    <th class="px-3 py-2 text-center font-semibold">STATUS BAYAR</th>
                    <th class="px-3 py-2 text-center font-semibold">STATUS</th>
                    <th class="px-3 py-2 text-center font-semibold">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $res)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-mono text-[11px]">{{ $res->nomor }}</td>
                    <td class="px-3 py-2 font-semibold">{{ $res->unit }}</td>
                    <td class="px-3 py-2">{{ $res->tenant_name }}</td>
                    <td class="px-3 py-2">{{ $res->nama_fasilitas }}</td>
                    <td class="px-3 py-2 text-center whitespace-nowrap">{{ $res->tanggal_reservasi?->format('d/m/Y') }}</td>
                    <td class="px-3 py-2 text-right font-medium">
                        {{ $res->biaya ? number_format($res->biaya, 0, ',', '.') : '-' }}
                    </td>
                    <td class="px-3 py-2 text-center">
                        @if($res->status_bayar === 'Sudah Bayar')
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-800">Sudah Bayar</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-yellow-100 text-yellow-800">
                                {{ $res->status_bayar ?: 'Belum Bayar' }}
                            </span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-center">
                        @php
                            $sc = match($res->status) {
                                'Selesai'           => 'bg-green-100 text-green-800',
                                'Ditolak'           => 'bg-red-100 text-red-800',
                                'Siap Pelaksanaan'  => 'bg-blue-100 text-blue-800',
                                'Sedang Berlangsung'=> 'bg-violet-100 text-violet-800',
                                default             => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <div class="flex flex-col items-center gap-1">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $sc }}">{{ $res->status }}</span>
                            <div class="flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full shadow {{ $res->fin_by ? 'bg-green-500' : 'bg-gray-300' }}" title="FIN: {{ $res->fin_by ? 'Konfirmasi '.$res->fin_by : 'Belum dikonfirmasi' }}"></span>
                                <span class="text-[8px] text-gray-500 font-semibold">FIN</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-2 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button wire:click="openDetail({{ $res->id }})"
                                    class="px-2 py-1 text-[10px] bg-gray-100 hover:bg-gray-200 text-gray-700 rounded">
                                Detail
                            </button>
                            @if(!$res->fin_by)
                            <button wire:click="confirmPayment({{ $res->id }})"
                                    class="px-2 py-1 text-[10px] bg-[#1a5c2e] hover:bg-[#16492a] text-white rounded"
                                    onclick="return confirm('Konfirmasi pembayaran diterima untuk {{ $res->nomor }}?')">
                                Konfirmasi Bayar
                            </button>
                            @else
                            <span class="text-[10px] text-green-700 font-semibold">✓ Confirmed</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-3 py-8 text-center text-gray-400 text-xs">Tidak ada data.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-3">{{ $rows->links() }}</div>

    {{-- Detail Slide Panel --}}
    @if($showPanel && $viewing)
    <div class="fixed inset-0 z-40 flex justify-end">
        <div class="absolute inset-0 bg-black/30" wire:click="closePanel"></div>
        <div class="relative z-50 w-full max-w-xl bg-white h-full shadow-2xl flex flex-col overflow-hidden">

            <div class="flex items-center justify-between px-5 py-3 border-b shrink-0"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <h2 class="font-semibold text-white text-sm">Detail Reservasi — {{ $viewing->nomor }}</h2>
                <button wire:click="closePanel" class="text-white/70 hover:text-white text-lg leading-none">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto p-5 text-xs text-gray-700 space-y-4">

                <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                    <div><span class="text-gray-400">Nomor</span><p class="font-mono font-semibold mt-0.5">{{ $viewing->nomor }}</p></div>
                    <div><span class="text-gray-400">Unit</span><p class="font-semibold mt-0.5">{{ $viewing->unit }}</p></div>
                    <div><span class="text-gray-400">Tenant</span><p class="mt-0.5">{{ $viewing->tenant_name }}</p></div>
                    <div><span class="text-gray-400">Fasilitas</span><p class="font-semibold mt-0.5">{{ $viewing->nama_fasilitas }}</p></div>
                    <div><span class="text-gray-400">Tanggal</span><p class="mt-0.5">{{ $viewing->tanggal_reservasi?->format('d/m/Y') }}</p></div>
                    <div><span class="text-gray-400">Jam</span><p class="mt-0.5">{{ $viewing->jam_mulai }} — {{ $viewing->jam_selesai }}</p></div>
                    <div><span class="text-gray-400">Keperluan</span><p class="mt-0.5">{{ $viewing->keperluan ?: '-' }}</p></div>
                    <div><span class="text-gray-400">Jumlah Tamu</span><p class="mt-0.5">{{ $viewing->jumlah_tamu ?: '-' }}</p></div>
                </div>

                {{-- Biaya --}}
                <div class="border-t border-gray-100 pt-3">
                    <p class="font-semibold text-gray-600 mb-2">Informasi Biaya</p>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                        <div>
                            <span class="text-gray-400">Biaya</span>
                            <p class="font-bold text-base text-gray-900 mt-0.5">
                                Rp {{ $viewing->biaya ? number_format($viewing->biaya, 0, ',', '.') : '0' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-400">Status Bayar</span>
                            <p class="mt-0.5">
                                @if($viewing->status_bayar === 'Sudah Bayar')
                                    <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 font-semibold">Sudah Bayar</span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-800 font-semibold">
                                        {{ $viewing->status_bayar ?: 'Belum Bayar' }}
                                    </span>
                                @endif
                            </p>
                        </div>
                        @if($viewing->bukti_bayar)
                        <div class="col-span-2">
                            <span class="text-gray-400">Bukti Bayar</span>
                            <div class="mt-1">
                                <a href="{{ asset('storage/' . $viewing->bukti_bayar) }}" target="_blank"
                                   class="text-[#2a8c56] hover:underline text-xs">
                                    Lihat Bukti Pembayaran →
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Finance Confirmation --}}
                <div class="border-t border-gray-100 pt-3">
                    <p class="font-semibold text-gray-600 mb-2">Konfirmasi Finance</p>
                    @if($viewing->fin_by)
                    <div class="space-y-1">
                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">✓ Dikonfirmasi</span>
                        <p class="text-gray-500 mt-1">Oleh: {{ $viewing->fin_by }} · {{ $viewing->fin_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    @else
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">Menunggu Konfirmasi Finance</span>
                    @endif
                </div>

                {{-- Checklist Departemen --}}
                <div class="border-t border-gray-100 pt-3">
                    <p class="font-semibold text-gray-600 mb-2">Checklist Persiapan</p>
                    @php
                        $checks = [
                            ['label' => 'CS Approved',      'by' => $viewing->cs_by,       'at' => $viewing->cs_at],
                            ['label' => 'HK Ready',         'by' => $viewing->hk_by,       'at' => $viewing->hk_at],
                            ['label' => 'Engineering Ready','by' => $viewing->eng_by,       'at' => $viewing->eng_at],
                            ['label' => 'Security Open',    'by' => $viewing->sec_open_by,  'at' => $viewing->sec_open_at],
                        ];
                    @endphp
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($checks as $chk)
                        <div class="flex items-center gap-2 p-2 rounded border {{ $chk['by'] ? 'border-green-200 bg-green-50' : 'border-gray-100 bg-gray-50' }}">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0
                                {{ $chk['by'] ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400' }}">
                                {{ $chk['by'] ? '✓' : '–' }}
                            </span>
                            <div>
                                <p class="font-medium text-[11px] {{ $chk['by'] ? 'text-gray-800' : 'text-gray-400' }}">{{ $chk['label'] }}</p>
                                @if($chk['by'])
                                <p class="text-[9px] text-gray-400">{{ $chk['by'] }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>

            <div class="shrink-0 border-t border-gray-200 bg-gray-50 px-5 py-3 flex gap-2">
                @if(!$viewing->fin_by)
                <button wire:click="confirmPayment({{ $viewing->id }})"
                        class="px-4 py-2 bg-[#1a5c2e] hover:bg-[#16492a] text-white text-xs font-semibold rounded"
                        onclick="return confirm('Konfirmasi pembayaran diterima?')">
                    Konfirmasi Pembayaran
                </button>
                @endif
                <button wire:click="closePanel"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs rounded ml-auto">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif

</div>

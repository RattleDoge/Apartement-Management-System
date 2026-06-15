<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;
use App\Models\WorkOrder;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination;

    public string $search     = '';
    public string $filterFin  = 'pending'; // pending | approved | rejected | all

    public ?int  $viewingId   = null;
    public bool  $showPanel   = false;
    public bool  $showReject  = false;
    public string $finNotes   = '';

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedFilterFin(): void { $this->resetPage(); }

    public function with(): array
    {
        $q = WorkOrder::where(fn($sub) =>
                $sub->where('is_berbayar', true)
                    ->orWhereNotNull('item_service')
             )->orderByDesc('tanggal');

        match ($this->filterFin) {
            'pending'  => $q->whereNull('fin_by'),
            'approved' => $q->where('fin_status', 'Approved'),
            'rejected' => $q->where('fin_status', 'Rejected'),
            default    => null,
        };

        if ($this->search) {
            $q->where(function ($sub) {
                $sub->where('no_wo',  'like', "%{$this->search}%")
                    ->orWhere('lot_no', 'like', "%{$this->search}%")
                    ->orWhere('name',   'like', "%{$this->search}%");
            });
        }

        return [
            'rows'    => $q->paginate(15),
            'viewing' => $this->viewingId ? WorkOrder::find($this->viewingId) : null,
        ];
    }

    public function openDetail(int $id): void
    {
        $this->viewingId  = $id;
        $this->showPanel  = true;
        $this->showReject = false;
        $this->finNotes   = '';
    }

    public function closePanel(): void
    {
        $this->showPanel  = false;
        $this->showReject = false;
        $this->viewingId  = null;
    }

    public function approve(int $id): void
    {
        $wo = WorkOrder::findOrFail($id);
        $wo->update([
            'fin_by'     => auth()->user()->name,
            'fin_at'     => now(),
            'fin_status' => 'Approved',
            'fin_notes'  => null,
        ]);
        $this->viewingId = $id;
        session()->flash('flash', "WO {$wo->no_wo} disetujui.");
    }

    public function openReject(int $id): void
    {
        $this->viewingId  = $id;
        $this->showPanel  = true;
        $this->showReject = true;
        $this->finNotes   = '';
    }

    public function submitReject(): void
    {
        $this->validate(['finNotes' => 'required|string|min:5']);
        $wo = WorkOrder::findOrFail($this->viewingId);
        $wo->update([
            'fin_by'     => auth()->user()->name,
            'fin_at'     => now(),
            'fin_status' => 'Rejected',
            'fin_notes'  => $this->finNotes,
        ]);
        $this->closePanel();
        session()->flash('flash', "WO {$wo->no_wo} ditolak.");
    }
};
?>

<div class="p-6 max-w-screen-xl mx-auto" x-data>

    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <span>APPROVAL WO BERBAYAR</span>
            <span class="text-xs font-normal opacity-80">Persetujuan Finance untuk Work Order berbayar</span>
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
               type="text" placeholder="Cari No. WO / Lot / Nama…"
               class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#2a8c56] w-64">

        <select wire:model.live="filterFin"
                class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#2a8c56]">
            <option value="pending">Menunggu Approval</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="all">Semua</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto border border-gray-200 rounded">
        <table class="min-w-full text-xs">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="px-3 py-2 text-left font-semibold">NO. WO</th>
                    <th class="px-3 py-2 text-left font-semibold">LOT NO</th>
                    <th class="px-3 py-2 text-left font-semibold">NAMA</th>
                    <th class="px-3 py-2 text-left font-semibold">JENIS WO</th>
                    <th class="px-3 py-2 text-left font-semibold">TANGGAL</th>
                    <th class="px-3 py-2 text-right font-semibold">BIAYA (Rp)</th>
                    <th class="px-3 py-2 text-left font-semibold">KETERANGAN BIAYA</th>
                    <th class="px-3 py-2 text-center font-semibold">STATUS CS</th>
                    <th class="px-3 py-2 text-center font-semibold">STATUS FA</th>
                    <th class="px-3 py-2 text-center font-semibold">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $wo)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-mono text-[11px] text-gray-800">{{ $wo->no_wo }}</td>
                    <td class="px-3 py-2">{{ $wo->lot_no }}</td>
                    <td class="px-3 py-2">{{ $wo->name }}</td>
                    <td class="px-3 py-2">{{ $wo->jenis_wo }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $wo->tanggal?->format('d/m/Y') }}</td>
                    <td class="px-3 py-2 text-right font-medium">
                        @php
                            $rowTotal = collect($wo->item_service ?? [])->sum(fn($i) => ($i['harga'] ?? 0) * ($i['qty'] ?? 1));
                            $rowTotal = $rowTotal ?: ($wo->biaya_wo ?? 0);
                        @endphp
                        {{ $rowTotal > 0 ? 'Rp ' . number_format($rowTotal, 0, ',', '.') : '-' }}
                    </td>
                    <td class="px-3 py-2 max-w-xs truncate" title="{{ $wo->keterangan_biaya }}">
                        {{ $wo->keterangan_biaya ?: '-' }}
                    </td>
                    <td class="px-3 py-2 text-center">
                        @if($wo->cs_status === 'Verified')
                        <span class="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-blue-100 text-blue-700">✔ Verified</span>
                        @elseif($wo->cs_status === 'Rejected')
                        <span class="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-red-100 text-red-600">✖ Tolak</span>
                        @elseif($wo->bukti_bayar_wo)
                        <span class="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-amber-100 text-amber-700">Menunggu</span>
                        @else
                        <span class="text-[9px] text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-center">
                        @php
                            if (!$wo->fin_by) { $finDot = 'bg-yellow-400'; $finLbl = 'Menunggu'; $finTxt = 'text-yellow-700'; }
                            elseif ($wo->fin_status === 'Approved') { $finDot = 'bg-green-500'; $finLbl = 'LUNAS'; $finTxt = 'text-green-700'; }
                            else { $finDot = 'bg-red-500'; $finLbl = 'Ditolak'; $finTxt = 'text-red-600'; }
                        @endphp
                        <div class="flex flex-col items-center gap-0.5">
                            <div class="flex items-center gap-1">
                                <span class="inline-block w-3.5 h-3.5 rounded-full {{ $finDot }} shadow-md"></span>
                                <span class="text-[10px] font-bold text-gray-600">FA</span>
                            </div>
                            <span class="text-[9px] font-semibold {{ $finTxt }}">{{ $finLbl }}</span>
                        </div>
                    </td>
                    <td class="px-3 py-2 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button wire:click="openDetail({{ $wo->id }})"
                                    class="px-2 py-1 text-[10px] bg-gray-100 hover:bg-gray-200 text-gray-700 rounded">
                                Detail
                            </button>
                            @if(!$wo->fin_by)
                            <button wire:click="approve({{ $wo->id }})"
                                    class="px-2 py-1 text-[10px] bg-green-600 hover:bg-green-700 text-white rounded"
                                    onclick="return confirm('Setujui WO ini?')">
                                Setujui
                            </button>
                            <button wire:click="openReject({{ $wo->id }})"
                                    class="px-2 py-1 text-[10px] bg-red-600 hover:bg-red-700 text-white rounded">
                                Tolak
                            </button>
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
    <div class="mt-3">
        {{ $rows->links() }}
    </div>

    {{-- Detail / Reject Slide Panel --}}
    @if($showPanel && $viewing)
    <div class="fixed inset-0 z-40 flex justify-end" x-data>
        <div class="absolute inset-0 bg-black/30" wire:click="closePanel"></div>
        <div class="relative z-50 w-full max-w-xl bg-white h-full shadow-2xl flex flex-col overflow-hidden">

            <div class="flex items-center justify-between px-5 py-3 border-b shrink-0"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <h2 class="font-semibold text-white text-sm">Detail WO — {{ $viewing->no_wo }}</h2>
                <button wire:click="closePanel" class="text-white/70 hover:text-white text-lg leading-none">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto p-5 text-xs text-gray-700 space-y-4">

                {{-- Info WO --}}
                <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                    <div><span class="text-gray-400">No. WO</span><p class="font-mono font-semibold mt-0.5">{{ $viewing->no_wo }}</p></div>
                    <div><span class="text-gray-400">Lot No</span><p class="font-semibold mt-0.5">{{ $viewing->lot_no }}</p></div>
                    <div><span class="text-gray-400">Nama</span><p class="mt-0.5">{{ $viewing->name }}</p></div>
                    <div><span class="text-gray-400">Jenis WO</span><p class="mt-0.5">{{ $viewing->jenis_wo }}</p></div>
                    <div><span class="text-gray-400">Tanggal</span><p class="mt-0.5">{{ $viewing->tanggal?->format('d/m/Y H:i') }}</p></div>
                    <div><span class="text-gray-400">Status</span><p class="mt-0.5">{{ $viewing->status_comp }}</p></div>
                    <div class="col-span-2"><span class="text-gray-400">Deskripsi</span><p class="mt-0.5">{{ $viewing->descs ?: '-' }}</p></div>
                    <div class="col-span-2"><span class="text-gray-400">Action Taken</span><p class="mt-0.5">{{ $viewing->action_taken ?: '-' }}</p></div>
                </div>

                {{-- Item & Service --}}
                @php
                    $viewItems = $viewing->item_service ?? [];
                    $viewTotal = collect($viewItems)->sum(fn($i) => ($i['harga'] ?? 0) * ($i['qty'] ?? 1));
                @endphp
                <div class="border-t border-gray-100 pt-3">
                    <p class="font-semibold text-gray-600 mb-2">Item & Service / Tagihan</p>
                    @if($viewing->keterangan_biaya)
                    <p class="text-[11px] text-gray-500 italic mb-2">{{ $viewing->keterangan_biaya }}</p>
                    @endif
                    @if(count($viewItems) > 0)
                    <table class="w-full text-[11px] border-collapse mb-2">
                        <thead>
                            <tr class="bg-blue-50">
                                <th class="text-left px-2 py-1 border border-gray-200">Item</th>
                                <th class="text-center px-2 py-1 border border-gray-200 w-10">Qty</th>
                                <th class="text-right px-2 py-1 border border-gray-200 w-28">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($viewItems as $item)
                            <tr>
                                <td class="px-2 py-1 border border-gray-200">{{ $item['nama'] }}</td>
                                <td class="px-2 py-1 border border-gray-200 text-center">{{ $item['qty'] }}</td>
                                <td class="px-2 py-1 border border-gray-200 text-right font-semibold">
                                    Rp {{ number_format(($item['harga'] ?? 0) * ($item['qty'] ?? 1), 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-bold bg-gray-50">
                                <td colspan="2" class="px-2 py-1 border border-gray-200 text-right">Total</td>
                                <td class="px-2 py-1 border border-gray-200 text-right text-blue-700">
                                    Rp {{ number_format($viewTotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    @else
                    <p class="text-gray-400 text-[11px]">Belum ada item.</p>
                    @endif
                </div>

                {{-- Bukti Bayar Tenant --}}
                <div class="border-t border-gray-100 pt-3">
                    <p class="font-semibold text-gray-600 mb-2">Bukti Pembayaran Tenant</p>
                    @if($viewing->bukti_bayar_wo)
                    <p class="text-[10px] text-gray-400 mb-2">
                        Diupload: {{ $viewing->tgl_bukti_bayar_wo?->format('d/m/Y H:i') }}
                    </p>
                    <a href="{{ asset('storage/' . $viewing->bukti_bayar_wo) }}" target="_blank">
                        <img src="{{ asset('storage/' . $viewing->bukti_bayar_wo) }}"
                             class="max-h-52 rounded border border-gray-200 object-contain cursor-zoom-in hover:opacity-90 w-full">
                    </a>
                    @else
                    <p class="text-amber-600 text-[11px] bg-amber-50 rounded px-3 py-2">
                        Tenant belum mengupload bukti pembayaran.
                    </p>
                    @endif
                </div>

                {{-- Status CS --}}
                <div class="border-t border-gray-100 pt-3">
                    <p class="font-semibold text-gray-600 mb-2">Status Verifikasi CS</p>
                    @if($viewing->cs_status === 'Verified')
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">✔ Terverifikasi CS</span>
                        <span class="text-[10px] text-gray-500">oleh {{ $viewing->cs_by }} · {{ $viewing->cs_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    @elseif($viewing->cs_status === 'Rejected')
                    <div>
                        <span class="px-3 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">✖ Ditolak CS</span>
                        <p class="text-red-600 text-[11px] mt-1">{{ $viewing->cs_notes }}</p>
                    </div>
                    @else
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">Belum diverifikasi CS</span>
                    @endif
                </div>

                {{-- Status Approval FA --}}
                <div class="border-t border-gray-100 pt-3">
                    <p class="font-semibold text-gray-600 mb-2">Status Approval Finance (FA)</p>
                    @if(!$viewing->fin_by)
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">Menunggu Persetujuan FA</span>
                    @elseif($viewing->fin_status === 'Approved')
                        <div class="space-y-1">
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">✔ LUNAS — FA Approved</span>
                            <p class="text-gray-500 mt-1">Oleh: {{ $viewing->fin_by }} · {{ $viewing->fin_at?->format('d/m/Y H:i') }}</p>
                        </div>
                    @else
                        <div class="space-y-1">
                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">Rejected FA</span>
                            <p class="text-gray-500 mt-1">Oleh: {{ $viewing->fin_by }} · {{ $viewing->fin_at?->format('d/m/Y H:i') }}</p>
                            <p class="text-red-600 mt-1">Catatan: {{ $viewing->fin_notes }}</p>
                        </div>
                    @endif
                </div>

                @if($viewing->cs_status !== 'Verified' && !$viewing->fin_by)
                <div class="bg-yellow-50 border border-yellow-200 rounded p-3 text-[11px] text-yellow-800">
                    ⚠ Bukti bayar belum diverifikasi oleh CS. FA tetap bisa approve, namun disarankan tunggu verifikasi CS terlebih dahulu.
                </div>
                @endif

                {{-- Reject Form --}}
                @if($showReject && !$viewing->fin_by)
                <div class="border-t border-red-100 pt-3 bg-red-50 rounded p-3">
                    <p class="font-semibold text-red-700 mb-2">Catatan Penolakan</p>
                    <textarea wire:model="finNotes" rows="3"
                              class="w-full border border-red-300 rounded px-3 py-2 text-xs focus:outline-none focus:border-red-500"
                              placeholder="Masukkan alasan penolakan (min. 5 karakter)…"></textarea>
                    @error('finNotes') <p class="text-red-600 text-[10px] mt-1">{{ $message }}</p> @enderror
                    <div class="flex gap-2 mt-2">
                        <button wire:click="submitReject"
                                class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs rounded font-semibold">
                            Konfirmasi Tolak
                        </button>
                        <button wire:click="$set('showReject', false)"
                                class="px-4 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs rounded">
                            Batal
                        </button>
                    </div>
                </div>
                @endif

            </div>

            {{-- Panel footer actions --}}
            @if(!$viewing->fin_by)
            <div class="shrink-0 border-t border-gray-200 bg-gray-50 px-5 py-3 flex gap-2">
                <button wire:click="approve({{ $viewing->id }})"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded"
                        onclick="return confirm('Setujui WO {{ $viewing->no_wo }}?')">
                    Setujui
                </button>
                <button wire:click="$set('showReject', true)"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded">
                    Tolak
                </button>
                <button wire:click="closePanel"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs rounded ml-auto">
                    Tutup
                </button>
            </div>
            @else
            <div class="shrink-0 border-t border-gray-200 bg-gray-50 px-5 py-3 flex justify-end">
                <button wire:click="closePanel"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs rounded">
                    Tutup
                </button>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>

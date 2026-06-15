<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\InOutPermit;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination;

    public string $search      = '';
    public string $filterJenis = '';
    public string $filterDate  = '';
    public string $filterStatus = 'pending'; // pending | all | rejected

    public ?int  $viewingId   = null;
    public bool  $showPanel   = false;

    public function updatedSearch(): void      { $this->resetPage(); }
    public function updatedFilterJenis(): void { $this->resetPage(); }
    public function updatedFilterDate(): void  { $this->resetPage(); }
    public function updatedFilterStatus(): void{ $this->resetPage(); }

    public function with(): array
    {
        $q = InOutPermit::orderByDesc('tanggal_ijin');

        match ($this->filterStatus) {
            'pending'  => $q->where('status', 'Approve by Customer Service'),
            'rejected' => $q->where('status', 'Tidak Disetujui'),
            default    => null,
        };

        if ($this->filterJenis) {
            $q->where('jenis', $this->filterJenis);
        }
        if ($this->filterDate) {
            $q->whereDate('tanggal_ijin', $this->filterDate);
        }
        if ($this->search) {
            $q->where(function ($sub) {
                $sub->where('nomor',       'like', "%{$this->search}%")
                    ->orWhere('unit',       'like', "%{$this->search}%")
                    ->orWhere('tenant_name','like', "%{$this->search}%")
                    ->orWhere('request_by', 'like', "%{$this->search}%");
            });
        }

        return [
            'rows'    => $q->paginate(15),
            'viewing' => $this->viewingId ? InOutPermit::find($this->viewingId) : null,
        ];
    }

    public function openDetail(int $id): void
    {
        $this->viewingId = $id;
        $this->showPanel = true;
    }

    public function closePanel(): void
    {
        $this->showPanel  = false;
        $this->viewingId  = null;
    }

    public function approve(int $id): void
    {
        $permit = InOutPermit::findOrFail($id);
        if ($permit->status !== 'Approve by Customer Service') return;
        $permit->update([
            'status'          => 'Approve by FA',
            'approved_fa_by'  => auth()->user()->name,
            'approved_fa_at'  => now(),
        ]);
        $this->viewingId = $id;
        session()->flash('flash', "Permit {$permit->nomor} disetujui oleh Finance.");
    }

    public function reject(int $id): void
    {
        $permit = InOutPermit::findOrFail($id);
        $permit->update(['status' => 'Tidak Disetujui']);
        $this->closePanel();
        session()->flash('flash', "Permit {$permit->nomor} ditolak.");
    }
};
?>

<div class="p-6 max-w-screen-xl mx-auto" x-data>

    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <span>APPROVAL IN OUT PERMIT</span>
            <span class="text-xs font-normal opacity-80">Persetujuan Finance untuk izin keluar-masuk barang/pekerja</span>
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

        <select wire:model.live="filterJenis"
                class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#2a8c56]">
            <option value="">Semua Jenis</option>
            <option value="Masuk">Masuk</option>
            <option value="Keluar">Keluar</option>
        </select>

        <input wire:model.live="filterDate" type="date"
               class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#2a8c56]">

        <select wire:model.live="filterStatus"
                class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#2a8c56]">
            <option value="pending">Menunggu Approval FA</option>
            <option value="rejected">Ditolak</option>
            <option value="all">Semua Status</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto border border-gray-200 rounded">
        <table class="min-w-full text-xs">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="px-3 py-2 text-left font-semibold">NOMOR</th>
                    <th class="px-3 py-2 text-left font-semibold">UNIT</th>
                    <th class="px-3 py-2 text-left font-semibold">TENANT</th>
                    <th class="px-3 py-2 text-center font-semibold">JENIS</th>
                    <th class="px-3 py-2 text-center font-semibold">TGL IJIN</th>
                    <th class="px-3 py-2 text-center font-semibold">JAM</th>
                    <th class="px-3 py-2 text-left font-semibold">DESKRIPSI</th>
                    <th class="px-3 py-2 text-center font-semibold">STATUS</th>
                    <th class="px-3 py-2 text-center font-semibold">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $permit)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-mono text-[11px]">{{ $permit->nomor }}</td>
                    <td class="px-3 py-2 font-semibold">{{ $permit->unit }}</td>
                    <td class="px-3 py-2">{{ $permit->tenant_name }}</td>
                    <td class="px-3 py-2 text-center">
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold
                            {{ $permit->jenis === 'Masuk' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                            {{ $permit->jenis }}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-center whitespace-nowrap">{{ $permit->tanggal_ijin?->format('d/m/Y') }}</td>
                    <td class="px-3 py-2 text-center">{{ $permit->jam }}</td>
                    <td class="px-3 py-2 max-w-xs truncate" title="{{ $permit->descs }}">{{ $permit->descs ?: '-' }}</td>
                    <td class="px-3 py-2 text-center">
                        @php
                            $pDitolak = $permit->status === 'Tidak Disetujui';
                            $pCs  = in_array($permit->status, ['Approve by Customer Service','Approve by FA','Approve by Security']);
                            $pFa  = in_array($permit->status, ['Approve by FA','Approve by Security']);
                            $pSec = $permit->status === 'Approve by Security';
                        @endphp
                        <div class="flex items-center justify-center gap-2">
                            @foreach([['CS',$pCs],['FA',$pFa],['SEC',$pSec]] as [$dpt,$ok])
                            <div class="flex flex-col items-center gap-0.5">
                                <span class="w-3.5 h-3.5 rounded-full shadow-md {{ $pDitolak ? 'bg-red-500' : ($ok ? 'bg-green-500' : 'bg-gray-300') }}" title="{{ $dpt }}"></span>
                                <span class="text-[8px] font-semibold text-gray-500">{{ $dpt }}</span>
                            </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-3 py-2 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button wire:click="openDetail({{ $permit->id }})"
                                    class="px-2 py-1 text-[10px] bg-gray-100 hover:bg-gray-200 text-gray-700 rounded">
                                Detail
                            </button>
                            @if($permit->status === 'Approve by Customer Service')
                            <button wire:click="approve({{ $permit->id }})"
                                    class="px-2 py-1 text-[10px] bg-green-600 hover:bg-green-700 text-white rounded"
                                    onclick="return confirm('Setujui permit ini?')">
                                Setujui
                            </button>
                            <button wire:click="reject({{ $permit->id }})"
                                    class="px-2 py-1 text-[10px] bg-red-600 hover:bg-red-700 text-white rounded"
                                    onclick="return confirm('Tolak permit ini?')">
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
    <div class="mt-3">{{ $rows->links() }}</div>

    {{-- Detail Slide Panel --}}
    @if($showPanel && $viewing)
    <div class="fixed inset-0 z-40 flex justify-end">
        <div class="absolute inset-0 bg-black/30" wire:click="closePanel"></div>
        <div class="relative z-50 w-full max-w-xl bg-white h-full shadow-2xl flex flex-col overflow-hidden">

            <div class="flex items-center justify-between px-5 py-3 border-b shrink-0"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <h2 class="font-semibold text-white text-sm">Detail Permit — {{ $viewing->nomor }}</h2>
                <button wire:click="closePanel" class="text-white/70 hover:text-white text-lg leading-none">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto p-5 text-xs text-gray-700 space-y-4">
                <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                    <div><span class="text-gray-400">Nomor</span><p class="font-mono font-semibold mt-0.5">{{ $viewing->nomor }}</p></div>
                    <div><span class="text-gray-400">Unit</span><p class="font-semibold mt-0.5">{{ $viewing->unit }}</p></div>
                    <div><span class="text-gray-400">Tenant</span><p class="mt-0.5">{{ $viewing->tenant_name }}</p></div>
                    <div><span class="text-gray-400">Jenis</span>
                        <p class="mt-0.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold
                                {{ $viewing->jenis === 'Masuk' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                                {{ $viewing->jenis }}
                            </span>
                        </p>
                    </div>
                    <div><span class="text-gray-400">Tanggal Ijin</span><p class="mt-0.5">{{ $viewing->tanggal_ijin?->format('d/m/Y') }}</p></div>
                    <div><span class="text-gray-400">Jam</span><p class="mt-0.5">{{ $viewing->jam }}</p></div>
                    <div><span class="text-gray-400">Request By</span><p class="mt-0.5">{{ $viewing->request_by }}</p></div>
                    <div><span class="text-gray-400">Via</span><p class="mt-0.5">{{ $viewing->request_via }}</p></div>
                    <div class="col-span-2"><span class="text-gray-400">Deskripsi</span><p class="mt-0.5">{{ $viewing->descs ?: '-' }}</p></div>
                </div>

                {{-- Approval Progress --}}
                <div class="border-t border-gray-100 pt-3">
                    <p class="font-semibold text-gray-600 mb-3">Progres Approval</p>
                    @php
                        $stages = [
                            ['label' => 'Customer Service', 'by' => $viewing->approved_cs_by, 'at' => $viewing->approved_cs_at],
                            ['label' => 'Finance',          'by' => $viewing->approved_fa_by, 'at' => $viewing->approved_fa_at],
                            ['label' => 'Security',         'by' => $viewing->approved_sec_by,'at' => $viewing->approved_sec_at],
                        ];
                    @endphp
                    <div class="space-y-2">
                        @foreach($stages as $stage)
                        <div class="flex items-start gap-3">
                            <div class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold
                                {{ $stage['by'] ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400' }}">
                                {{ $stage['by'] ? '✓' : '–' }}
                            </div>
                            <div>
                                <p class="font-medium {{ $stage['by'] ? 'text-gray-800' : 'text-gray-400' }}">{{ $stage['label'] }}</p>
                                @if($stage['by'])
                                <p class="text-gray-400 text-[10px]">{{ $stage['by'] }} · {{ $stage['at']?->format('d/m/Y H:i') }}</p>
                                @else
                                <p class="text-gray-300 text-[10px]">Menunggu…</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($viewing->status === 'Tidak Disetujui')
                    <div class="mt-3 px-3 py-2 bg-red-50 border border-red-200 rounded text-red-700 text-xs">
                        Permit ini telah ditolak.
                    </div>
                    @endif
                </div>
            </div>

            <div class="shrink-0 border-t border-gray-200 bg-gray-50 px-5 py-3 flex gap-2">
                @if($viewing->status === 'Approve by Customer Service')
                <button wire:click="approve({{ $viewing->id }})"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded"
                        onclick="return confirm('Setujui permit ini?')">
                    Setujui FA
                </button>
                <button wire:click="reject({{ $viewing->id }})"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded"
                        onclick="return confirm('Tolak permit ini?')">
                    Tolak
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

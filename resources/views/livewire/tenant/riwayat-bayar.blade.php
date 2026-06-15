<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Invoice;

new #[Layout('layouts.tenant')] class extends Component {
    use WithPagination;

    public string $filterStatus = '';

    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function with(): array
    {
        $debtorAcct = auth()->user()?->tenant?->unit_number;

        $invoices = Invoice::when($debtorAcct, fn($q) => $q->where('debtor_acct', $debtorAcct))
            ->when($this->filterStatus, fn($q) => $q->where('status_bayar', $this->filterStatus))
            ->orderByDesc('inv_date')
            ->paginate(15);

        $totalLunas  = Invoice::when($debtorAcct, fn($q) => $q->where('debtor_acct', $debtorAcct))
            ->where('status_bayar', 'Lunas')->count();
        $totalBelum  = Invoice::when($debtorAcct, fn($q) => $q->where('debtor_acct', $debtorAcct))
            ->where('status_bayar', '!=', 'Lunas')->count();

        return compact('invoices', 'totalLunas', 'totalBelum');
    }
}
?>

<div class="max-w-3xl mx-auto px-4 py-5">
    <h2 class="text-base font-semibold text-gray-800 mb-4">Riwayat Pembayaran</h2>

    {{-- Summary --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="bg-green-50 border border-green-200 rounded-xl p-3 text-center">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide">Lunas</p>
            <p class="text-2xl font-bold text-green-700">{{ $totalLunas }}</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-center">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide">Belum Lunas</p>
            <p class="text-2xl font-bold text-red-600">{{ $totalBelum }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="flex gap-2 mb-3">
        @foreach(['' => 'Semua', 'Lunas' => 'Lunas', 'Belum Lunas' => 'Belum Lunas'] as $val => $label)
        <button wire:click="$set('filterStatus', '{{ $val }}')"
                style="{{ $filterStatus === $val ? 'background:#1a5c2e; color:white; border-color:#1a5c2e;' : 'background:white; color:#4b5563; border-color:#e5e7eb;' }} padding:4px 14px; font-size:12px; font-weight:600; border-radius:99px; border-width:1px; border-style:solid; cursor:pointer; transition:all 0.15s;">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- List --}}
    <div class="space-y-2">
        @forelse($invoices as $inv)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-800 font-mono">{{ $inv->no_invoice }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">
                        {{ $inv->inv_date?->format('d M Y') }} · {{ $inv->description ?? $inv->kategori }}
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold
                        {{ $inv->status_bayar === 'Lunas' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                        {{ $inv->status_bayar ?? 'Belum Lunas' }}
                    </span>
                    <p class="text-xs font-bold text-gray-800 mt-1">
                        Rp {{ number_format($inv->amount ?? 0, 0, ',', '.') }}
                    </p>
                </div>
            </div>
            @if($inv->tgl_bayar)
            <p class="text-[10px] text-green-600 mt-1.5">
                Dibayar: {{ $inv->tgl_bayar->format('d M Y') }}
                @if($inv->paid_by) · {{ $inv->paid_by }} @endif
            </p>
            @endif
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-10 text-center text-xs text-gray-400">
            Belum ada data invoice.
        </div>
        @endforelse
    </div>

    @if($invoices->hasPages())
    <div class="mt-4 flex justify-center gap-2 text-xs">
        <button wire:click="previousPage" @disabled($invoices->onFirstPage())
                class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-40">‹ Prev</button>
        <span class="px-3 py-1 text-gray-500">{{ $invoices->currentPage() }} / {{ $invoices->lastPage() }}</span>
        <button wire:click="nextPage" @disabled(!$invoices->hasMorePages())
                class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-40">Next ›</button>
    </div>
    @endif
</div>

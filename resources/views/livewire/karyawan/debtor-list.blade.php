<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void { $this->resetPage(); }

    public function with(): array
    {
        $q = Invoice::select(
                'debtor_acct',
                DB::raw('MAX(debtor_name) as debtor_name'),
                DB::raw('MAX(virtual_account) as virtual_account'),
                DB::raw('COUNT(*) as inv_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(CASE WHEN status_bayar = "Lunas" THEN amount ELSE 0 END) as total_paid'),
                DB::raw('SUM(CASE WHEN status_bayar = "Belum Lunas" THEN amount ELSE 0 END) as outstanding')
            )
            ->groupBy('debtor_acct')
            ->orderBy('debtor_acct');

        if ($this->search) {
            $q->where(function ($sub) {
                $sub->where('debtor_acct', 'like', "%{$this->search}%")
                    ->orWhere('debtor_name', 'like', "%{$this->search}%");
            });
        }

        return [
            'debtors'    => $q->paginate(50),
            'totalCount' => $q->toBase()->getCountForPagination(),
        ];
    }
};
?>

<div class="min-h-screen bg-white">

    {{-- ── Page Header ── --}}
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mx-5 mt-3 mb-0">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <span>DEBTOR ACCOUNT</span>
            <span class="text-xs font-normal opacity-80">Total: {{ $debtors->total() }} debtor</span>
        </div>
    </div>

    {{-- ── Filter ── --}}
    <div class="px-5 py-2.5 border-b border-gray-200 bg-[#f5faf5] flex items-center gap-3">
        <input wire:model.live.debounce.300ms="search"
               type="text" placeholder="Cari Debtor A/C atau Nama…"
               class="border border-gray-300 rounded px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] w-72">
        <span wire:loading class="text-xs text-gray-400">Loading…</span>
    </div>

    {{-- ── Table ── --}}
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs border-collapse">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-2 py-1.5 w-8 text-center font-semibold"></th>
                    <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold tracking-wide">DEBTOR ACCT ▲</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold tracking-wide">NAME</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold tracking-wide">LOT NO</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-center font-semibold tracking-wide">INV</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-right font-semibold tracking-wide">TOTAL TAGIHAN</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-right font-semibold tracking-wide">SUDAH BAYAR</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-right font-semibold tracking-wide">OUTSTANDING</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold tracking-wide">VIRTUAL ACCOUNT</th>
                    <th class="border border-blue-200 px-3 py-1.5 text-center font-semibold tracking-wide">AKSI</th>
                </tr>
                <tr class="bg-white">
                    <td class="border border-gray-200 px-2 py-1"></td>
                    <td colspan="9" class="border border-gray-200 px-2 py-1">
                        {{-- per-column search already handled via global search --}}
                    </td>
                </tr>
            </thead>
            <tbody>
                @forelse($debtors as $i => $d)
                <tr class="hover:bg-blue-50 cursor-pointer {{ $d->outstanding > 0 ? '' : '' }}">
                    <td class="border border-gray-200 px-2 py-1.5 text-center text-gray-400">
                        {{ ($debtors->currentPage() - 1) * $debtors->perPage() + $i + 1 }}
                    </td>
                    <td class="border border-gray-200 px-3 py-1.5 font-semibold" style="color: #3a9aaa;">
                        {{ $d->debtor_acct }}
                    </td>
                    <td class="border border-gray-200 px-3 py-1.5">{{ $d->debtor_name }}</td>
                    <td class="border border-gray-200 px-3 py-1.5 text-gray-600">{{ $d->debtor_acct }}</td>
                    <td class="border border-gray-200 px-3 py-1.5 text-center text-gray-600">{{ $d->inv_count }}</td>
                    <td class="border border-gray-200 px-3 py-1.5 text-right">
                        {{ number_format($d->total_amount, 0, ',', '.') }}
                    </td>
                    <td class="border border-gray-200 px-3 py-1.5 text-right text-green-700">
                        {{ number_format($d->total_paid, 0, ',', '.') }}
                    </td>
                    <td class="border border-gray-200 px-3 py-1.5 text-right {{ $d->outstanding > 0 ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                        {{ number_format($d->outstanding, 0, ',', '.') }}
                    </td>
                    <td class="border border-gray-200 px-3 py-1.5 font-mono text-gray-600">
                        {{ $d->virtual_account ?: '-' }}
                    </td>
                    <td class="border border-gray-200 px-3 py-1.5 text-center">
                        <a href="{{ route('karyawan.debtor.statement', $d->debtor_acct) }}" target="_blank"
                           class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] bg-blue-600 text-white hover:bg-blue-700 rounded font-medium transition-colors">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Statement
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="border border-gray-200 px-3 py-8 text-center text-gray-400">
                        Tidak ada data debtor.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($debtors->count() > 0)
            <tfoot>
                <tr style="background-color: #eef6f7;">
                    <td colspan="5" class="border border-gray-300 px-3 py-1.5 text-right font-semibold text-gray-600">TOTAL</td>
                    <td class="border border-gray-300 px-3 py-1.5 text-right font-bold text-gray-800">
                        {{ number_format($debtors->sum('total_amount'), 0, ',', '.') }}
                    </td>
                    <td class="border border-gray-300 px-3 py-1.5 text-right font-bold text-green-700">
                        {{ number_format($debtors->sum('total_paid'), 0, ',', '.') }}
                    </td>
                    <td class="border border-gray-300 px-3 py-1.5 text-right font-bold text-red-600">
                        {{ number_format($debtors->sum('outstanding'), 0, ',', '.') }}
                    </td>
                    <td colspan="2" class="border border-gray-300"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    {{-- ── Pagination Bar ── --}}
    @php
        $cur  = $debtors->currentPage();
        $last = $debtors->lastPage();
        $nums = collect();
        for ($p = 1; $p <= $last; $p++) {
            if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
        }
        $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
        $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
        $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
    @endphp
    <div class="flex items-center gap-1 px-4 py-2 border-t border-gray-300 text-[11px] text-gray-500"
         style="background-color: #f0f0f0;">
        @if($debtors->onFirstPage())
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
        @if($debtors->hasMorePages())
            <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
            <button wire:click="setPage({{ $last }})" class="{{ $pBtn }}">›|</button>
        @else
            <span class="{{ $pDis }}">›</span>
            <span class="{{ $pDis }}">›|</span>
        @endif

        <span class="ml-4 text-gray-400">
            View {{ $debtors->firstItem() }}–{{ $debtors->lastItem() }} of {{ $debtors->total() }}
        </span>

        <div class="ml-auto flex items-center gap-1">
            <span>Rows:</span>
            <select wire:model.live="perPage" onchange="this.form && this.form.submit()"
                    class="border border-gray-400 text-[11px] px-1 py-0.5 bg-white">
                <option>25</option>
                <option selected>50</option>
                <option>100</option>
            </select>
        </div>
    </div>

    {{-- ── Bottom action bar (mirrors the "Statement of Account" tab in screenshot) ── --}}
    <div class="px-4 py-2 border-t border-gray-200 bg-gray-50 flex items-center gap-2">
        <span class="text-xs text-gray-500 font-medium">Statement of Account</span>
        <span class="text-xs text-gray-400">— klik tombol "Statement" pada baris debtor untuk mencetak</span>
    </div>

</div>


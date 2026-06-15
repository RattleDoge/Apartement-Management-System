@if ($paginator->hasPages())
@php
    $cur  = $paginator->currentPage();
    $last = $paginator->lastPage();
    $nums = collect();
    for ($p = 1; $p <= $last; $p++) {
        if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
    }
    $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
    $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
    $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
@endphp
<div class="flex items-center justify-between mt-3 select-none">

    <div class="text-[11px] text-gray-500">
        Menampilkan {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}
        dari {{ $paginator->total() }} data
    </div>

    <div class="flex items-center gap-1">

        {{-- |‹ --}}
        @if ($paginator->onFirstPage())
            <span class="{{ $pDis }}">|‹</span>
        @else
            <button wire:click="setPage(1)" wire:loading.attr="disabled" class="{{ $pBtn }}">|‹</button>
        @endif

        {{-- ‹ --}}
        @if ($paginator->onFirstPage())
            <span class="{{ $pDis }}">‹</span>
        @else
            <button wire:click="previousPage" wire:loading.attr="disabled" class="{{ $pBtn }}">‹</button>
        @endif

        {{-- Nomor halaman --}}
        @php $prev = null; @endphp
        @foreach ($nums as $page)
            @if ($prev !== null && $page - $prev > 1)
                <span class="{{ $pDis }}">…</span>
            @endif
            @if ($page == $cur)
                <span class="{{ $pAct }}">{{ $page }}</span>
            @else
                <button wire:click="setPage({{ $page }})" wire:loading.attr="disabled" class="{{ $pBtn }}">{{ $page }}</button>
            @endif
            @php $prev = $page; @endphp
        @endforeach

        {{-- › --}}
        @if ($paginator->hasMorePages())
            <button wire:click="nextPage" wire:loading.attr="disabled" class="{{ $pBtn }}">›</button>
        @else
            <span class="{{ $pDis }}">›</span>
        @endif

        {{-- ›| --}}
        @if ($paginator->hasMorePages())
            <button wire:click="setPage({{ $last }})" wire:loading.attr="disabled" class="{{ $pBtn }}">›|</button>
        @else
            <span class="{{ $pDis }}">›|</span>
        @endif

    </div>
</div>
@endif

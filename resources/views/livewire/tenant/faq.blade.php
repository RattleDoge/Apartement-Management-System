<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Faq;

new #[Layout('layouts.tenant')] class extends Component {

    public string $filterKategori = '';
    public string $search         = '';
    public ?int   $openId         = null;

    public function toggle(int $id): void
    {
        $this->openId = $this->openId === $id ? null : $id;
    }

    public function with(): array
    {
        $faqs = Faq::where('is_active', true)
            ->when($this->filterKategori, fn($q) => $q->where('kategori', $this->filterKategori))
            ->when($this->search, fn($q) => $q->where(function ($q2) {
                $q2->where('pertanyaan', 'like', "%{$this->search}%")
                   ->orWhere('jawaban', 'like', "%{$this->search}%");
            }))
            ->orderBy('urutan')
            ->orderBy('kategori')
            ->get();

        $kategoriList = Faq::where('is_active', true)->distinct()->pluck('kategori')->filter();

        return compact('faqs', 'kategoriList');
    }
}
?>

<div class="max-w-2xl mx-auto px-4 py-5">
    <h2 class="text-base font-semibold text-gray-800 mb-1">FAQ</h2>
    <p class="text-xs text-gray-500 mb-4">Pertanyaan yang sering ditanyakan</p>

    {{-- Search + filter --}}
    <div class="flex flex-wrap gap-2 mb-4">
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="Cari pertanyaan..."
               class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] w-full sm:w-48">
        <button wire:click="$set('filterKategori', '')"
                style="{{ $filterKategori === '' ? 'background:#1a5c2e; color:white; border-color:#1a5c2e;' : 'background:white; color:#4b5563; border-color:#e5e7eb;' }} padding:4px 14px; font-size:12px; font-weight:600; border-radius:99px; border-width:1px; border-style:solid; cursor:pointer; transition:all 0.15s;">
            Semua
        </button>
        @foreach($kategoriList as $kat)
        <button wire:click="$set('filterKategori', '{{ $kat }}')"
                style="{{ $filterKategori === $kat ? 'background:#1a5c2e; color:white; border-color:#1a5c2e;' : 'background:white; color:#4b5563; border-color:#e5e7eb;' }} padding:4px 14px; font-size:12px; font-weight:600; border-radius:99px; border-width:1px; border-style:solid; cursor:pointer; transition:all 0.15s;">
            {{ $kat }}
        </button>
        @endforeach
    </div>

    {{-- Accordion --}}
    <div class="space-y-2">
        @forelse($faqs as $faq)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <button wire:click="toggle({{ $faq->id }})"
                    class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 transition-colors">
                <span class="text-xs font-semibold text-gray-800 pr-3">{{ $faq->pertanyaan }}</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform {{ $openId === $faq->id ? 'rotate-180' : '' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            @if($openId === $faq->id)
            <div class="px-4 pb-4 border-t border-gray-50">
                <p class="text-xs text-gray-600 leading-relaxed pt-3">{!! nl2br(e($faq->jawaban)) !!}</p>
                @if($faq->kategori)
                <span class="mt-2 inline-block text-[10px] px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded">{{ $faq->kategori }}</span>
                @endif
            </div>
            @endif
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-10 text-center text-xs text-gray-400">
            Tidak ada FAQ yang ditemukan.
        </div>
        @endforelse
    </div>
</div>

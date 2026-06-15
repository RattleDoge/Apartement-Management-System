<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.tenant')] class extends Component {

    public string $filterKategori = '';

    public function with(): array
    {
        $documents = Document::where('is_active', true)
            ->when($this->filterKategori, fn($q) => $q->where('kategori', $this->filterKategori))
            ->orderBy('kategori')
            ->orderBy('judul')
            ->get();

        $kategoriList = Document::where('is_active', true)
            ->distinct()->pluck('kategori');

        return compact('documents', 'kategoriList');
    }
}
?>

<div class="max-w-2xl mx-auto px-4 py-5">
    <h2 class="text-base font-semibold text-gray-800 mb-4">Dokumen Penting</h2>

    {{-- Filter --}}
    <div class="flex flex-wrap gap-2 mb-4">
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

    {{-- Documents --}}
    <div class="space-y-2">
        @forelse($documents as $doc)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-[#e8f5e9] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-[#1a5c2e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-800 truncate">{{ $doc->judul }}</p>
                @if($doc->deskripsi)
                <p class="text-[10px] text-gray-400 mt-0.5 truncate">{{ $doc->deskripsi }}</p>
                @endif
                <span class="text-[10px] px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded font-medium">{{ $doc->kategori }}</span>
            </div>
            <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
               class="shrink-0 flex items-center gap-1 text-[10px] text-[#1a5c2e] border border-[#1a5c2e] rounded-lg px-2.5 py-1.5 hover:bg-[#e8f5e9] font-semibold transition-colors">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Unduh
            </a>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-10 text-center text-xs text-gray-400">
            Belum ada dokumen yang tersedia.
        </div>
        @endforelse
    </div>
</div>

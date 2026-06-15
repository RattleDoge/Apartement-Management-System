<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\FacilityReservation;

new #[Layout('layouts.tenant')] class extends Component {

    public string $filterFasilitas = '';
    public string $filterTanggal  = '';

    public function mount(): void
    {
        $this->filterTanggal = now()->format('Y-m-d');
    }

    public function with(): array
    {
        $reservations = FacilityReservation::whereNotIn('status', ['Ditolak'])
            ->when($this->filterFasilitas, fn($q) => $q->where('nama_fasilitas', $this->filterFasilitas))
            ->when($this->filterTanggal,  fn($q) => $q->whereDate('tanggal_reservasi', $this->filterTanggal))
            ->orderBy('tanggal_reservasi')
            ->orderBy('jam_mulai')
            ->get();

        $fasilitasOptions = FacilityReservation::select('nama_fasilitas')
            ->distinct()->pluck('nama_fasilitas');

        return compact('reservations', 'fasilitasOptions');
    }
}
?>

<div class="max-w-3xl mx-auto px-4 py-5">
    <h2 class="text-base font-semibold text-gray-800 mb-4">Jadwal Fasilitas</h2>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <input wire:model.live="filterTanggal" type="date"
               class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
        <select wire:model.live="filterFasilitas"
                class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
            <option value="">Semua Fasilitas</option>
            @foreach($fasilitasOptions as $f)
            <option value="{{ $f }}">{{ $f }}</option>
            @endforeach
        </select>
    </div>

    {{-- Schedule --}}
    <div class="space-y-2">
        @forelse($reservations as $r)
        @php
            $statusColor = match($r->status) {
                'Pesan Diterima'     => 'bg-gray-100 text-gray-600',
                'Disetujui CS'       => 'bg-blue-100 text-blue-700',
                'Siap Pelaksanaan'   => 'bg-amber-100 text-amber-700',
                'Sedang Berlangsung' => 'bg-orange-100 text-orange-700',
                'Selesai'            => 'bg-green-100 text-green-700',
                default              => 'bg-gray-100 text-gray-500',
            };
            $isOwn = optional(auth()->user()?->tenant)->unit_number === $r->unit;
        @endphp
        <div class="bg-white rounded-xl border shadow-sm px-4 py-3
                    {{ $isOwn ? 'border-[#1a5c2e]' : 'border-gray-200' }}">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-gray-800">{{ $r->nama_fasilitas }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">
                        {{ $r->tanggal_reservasi?->format('d M Y') }}
                        · {{ $r->jam_mulai }} – {{ $r->jam_selesai }}
                    </p>
                    @if($isOwn)
                    <p class="text-[10px] text-[#1a5c2e] font-semibold mt-0.5">Reservasi Anda</p>
                    @endif
                </div>
                <div class="text-right">
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold {{ $statusColor }}">
                        {{ $r->status }}
                    </span>
                    @if($r->jumlah_tamu)
                    <p class="text-[10px] text-gray-400 mt-1">{{ $r->jumlah_tamu }} tamu</p>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-10 text-center text-xs text-gray-400">
            Tidak ada reservasi untuk filter yang dipilih.
        </div>
        @endforelse
    </div>

    <div class="mt-4 text-center">
        <a href="{{ route('tenant.facility-reservation') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#1a5c2e] text-white text-xs rounded-lg hover:bg-[#154d26] font-semibold transition-colors">
            + Buat Reservasi Baru
        </a>
    </div>
</div>

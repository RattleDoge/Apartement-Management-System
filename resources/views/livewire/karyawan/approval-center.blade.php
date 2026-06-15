<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\WorkOrder;
use App\Models\InOutPermit;
use App\Models\FacilityReservation;

new #[Layout('layouts.karyawan')] class extends Component {

    public function with(): array
    {
        $woBerbayarPending = WorkOrder::where('is_berbayar', true)
            ->whereNull('fin_approved_at')
            ->whereNotNull('assign_staff')
            ->count();

        $permitPending = InOutPermit::whereIn('status', ['Menunggu', 'Pesan Diterima'])->count();

        $fasilitasPending = FacilityReservation::where('status', 'Pesan Diterima')->count();
        $fasilitasBayarPending = FacilityReservation::where('is_berbayar', true)
            ->where('status_bayar', '!=', 'Lunas')
            ->whereNotIn('status', ['Ditolak', 'Selesai'])
            ->count();

        return compact(
            'woBerbayarPending', 'permitPending',
            'fasilitasPending', 'fasilitasBayarPending'
        );
    }
}
?>

<div class="p-5">
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-5">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            APPROVAL CENTER
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

        {{-- WO Berbayar --}}
        <a href="{{ route('karyawan.fa.wo-approval') }}"
           class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:border-[#1a5c2e] hover:shadow-md transition-all group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">WO Berbayar</p>
                    <p class="text-3xl font-bold text-gray-800 group-hover:text-[#1a5c2e] transition-colors">
                        {{ $woBerbayarPending }}
                    </p>
                    <p class="text-[11px] text-gray-500 mt-1">Menunggu approval Finance</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            @if($woBerbayarPending > 0)
            <div class="mt-3 flex items-center gap-1 text-[11px] text-[#1a5c2e] font-semibold">
                Proses sekarang <span>→</span>
            </div>
            @endif
        </a>

        {{-- In-Out Permit --}}
        <a href="{{ route('karyawan.fa.permit-approval') }}"
           class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:border-[#1a5c2e] hover:shadow-md transition-all group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">In-Out Permit</p>
                    <p class="text-3xl font-bold text-gray-800 group-hover:text-[#1a5c2e] transition-colors">
                        {{ $permitPending }}
                    </p>
                    <p class="text-[11px] text-gray-500 mt-1">Menunggu approval</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            @if($permitPending > 0)
            <div class="mt-3 flex items-center gap-1 text-[11px] text-[#1a5c2e] font-semibold">Proses sekarang <span>→</span></div>
            @endif
        </a>

        {{-- Fasilitas --}}
        <a href="{{ route('karyawan.fa.facility-approval') }}"
           class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:border-[#1a5c2e] hover:shadow-md transition-all group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Reservasi Fasilitas</p>
                    <p class="text-3xl font-bold text-gray-800 group-hover:text-[#1a5c2e] transition-colors">
                        {{ $fasilitasPending }}
                    </p>
                    <p class="text-[11px] text-gray-500 mt-1">
                        Menunggu approval · {{ $fasilitasBayarPending }} belum bayar
                    </p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            @if($fasilitasPending > 0)
            <div class="mt-3 flex items-center gap-1 text-[11px] text-[#1a5c2e] font-semibold">Proses sekarang <span>→</span></div>
            @endif
        </a>
    </div>
</div>

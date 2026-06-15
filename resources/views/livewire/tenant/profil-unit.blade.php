<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\HandoverUnit;

new #[Layout('layouts.tenant')] class extends Component {

    public function with(): array
    {
        $user   = auth()->user();
        $tenant = $user->tenant;
        $ho     = $tenant ? HandoverUnit::where('lot_no', $tenant->unit_number)->first() : null;

        return compact('user', 'tenant', 'ho');
    }
}
?>

<div class="max-w-2xl mx-auto px-4 py-6">
    <h2 class="text-base font-semibold text-gray-800 mb-5">Profil & Informasi Unit</h2>

    {{-- Akun --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-4 overflow-hidden">
        <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Akun</p>
        </div>
        <div class="px-4 py-3 space-y-2.5 text-sm">
            <div class="flex items-center gap-3">
                <span class="w-32 text-gray-400 text-xs">Nama</span>
                <span class="font-semibold text-gray-800">{{ $user->name }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="w-32 text-gray-400 text-xs">Email</span>
                <span class="text-gray-700">{{ $user->email }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="w-32 text-gray-400 text-xs">No. HP</span>
                <span class="text-gray-700">{{ $user->phone ?? '—' }}</span>
            </div>
            <div class="pt-1">
                <a href="{{ route('profile') }}"
                   class="inline-flex items-center gap-1.5 text-xs text-green-800 border border-green-700 rounded-lg px-3 py-1.5 hover:bg-green-50 transition-colors font-medium">
                    Ubah Password / Profil
                </a>
            </div>
        </div>
    </div>

    {{-- Unit --}}
    @if($tenant)
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-4 overflow-hidden">
        <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Informasi Unit</p>
        </div>
        <div class="px-4 py-3 grid grid-cols-2 gap-x-6 gap-y-2.5 text-sm">
            @php
                $fields = [
                    'No. Unit'        => $tenant->unit_number,
                    'Status'          => $tenant->status ?? '—',
                    'Tgl. STR'        => $ho?->str_date?->format('d M Y') ?? '—',
                    'Tgl. CMG'        => $ho?->cmg_date?->format('d M Y') ?? '—',
                    'No. Intercom'    => $ho?->no_intercom ?? '—',
                    'No. Telpon'      => $ho?->no_telpon ?? '—',
                    'No. Access Card' => $ho?->no_access_card ?? '—',
                ];
            @endphp
            @foreach($fields as $label => $value)
            <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                <p class="font-medium text-gray-800 text-xs mt-0.5">{{ $value }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Dokumen Handover --}}
    @if($ho)
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Dokumen Serah Terima</p>
        </div>
        <div class="px-4 py-3 space-y-2">
            @php
                $docs = [
                    'PPJB'       => $ho->ppjb,
                    'BAST'       => $ho->bast,
                    'House Rule' => $ho->house_rule,
                ];
            @endphp
            @foreach($docs as $name => $checked)
            <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold
                    {{ $checked ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">
                    {{ $checked ? '✓' : '—' }}
                </span>
                <span class="text-xs {{ $checked ? 'text-gray-700' : 'text-gray-400' }}">{{ $name }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

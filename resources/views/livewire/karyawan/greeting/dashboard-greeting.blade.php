<?php

use App\Models\GreetingTemplate;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.karyawan')] class extends Component
{
    public function with(): array
    {
        $totalTemplates  = GreetingTemplate::count();
        $activeTemplates = GreetingTemplate::where('status', 'Active')->count();
        $totalKirim      = GreetingTemplate::sum('kirim_count');
        $tenantCount     = User::where('role', 'tenant')->count();

        $byJenis = GreetingTemplate::select('jenis', \Illuminate\Support\Facades\DB::raw('COUNT(*) as jumlah'), \Illuminate\Support\Facades\DB::raw('SUM(kirim_count) as total_kirim'))
            ->groupBy('jenis')
            ->get();

        $recentTemplates = GreetingTemplate::orderByDesc('updated_at')->limit(5)->get();

        return compact('totalTemplates', 'activeTemplates', 'totalKirim', 'tenantCount', 'byJenis', 'recentTemplates');
    }
};
?>

<div class="px-5 py-4">

    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            DASHBOARD GREETING
        </div>
    </div>

    {{-- ── Stat Cards ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Total Template</p>
            <p class="text-2xl font-bold text-gray-800">{{ $totalTemplates }}</p>
        </div>
        <div class="bg-white border border-green-200 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Template Aktif</p>
            <p class="text-2xl font-bold text-green-700">{{ $activeTemplates }}</p>
        </div>
        <div class="bg-white border border-blue-200 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Total Terkirim</p>
            <p class="text-2xl font-bold text-blue-700">{{ number_format($totalKirim) }}</p>
        </div>
        <div class="bg-white border border-purple-200 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Akun Penghuni</p>
            <p class="text-2xl font-bold text-purple-700">{{ number_format($tenantCount) }}</p>
        </div>
    </div>

    {{-- ── Main 2-col layout ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- By Jenis --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Distribusi per Jenis</p>
            </div>
            @if($byJenis->isEmpty())
            <div class="px-4 py-8 text-center text-xs text-gray-400">Belum ada template.</div>
            @else
            <table class="w-full text-xs">
                <thead>
                    <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                        <th class="border border-blue-200 px-3 py-1.5 text-left font-semibold">JENIS</th>
                        <th class="border border-blue-200 px-3 py-1.5 text-center font-semibold w-20">TEMPLATE</th>
                        <th class="border border-blue-200 px-3 py-1.5 text-center font-semibold w-24">TOTAL KIRIM</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byJenis as $row)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="border border-gray-100 px-3 py-1.5 text-gray-700">{{ $row->jenis }}</td>
                        <td class="border border-gray-100 px-3 py-1.5 text-center font-semibold text-gray-800">{{ $row->jumlah }}</td>
                        <td class="border border-gray-100 px-3 py-1.5 text-center text-blue-700 font-semibold">{{ number_format($row->total_kirim) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Recent Templates --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Template Terbaru</p>
                <a href="{{ route('karyawan.greeting.template') }}" class="text-[10px] text-[#1a5c2e] hover:underline">Lihat semua →</a>
            </div>
            @if($recentTemplates->isEmpty())
            <div class="px-4 py-8 text-center text-xs text-gray-400">Belum ada template.</div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($recentTemplates as $tpl)
                <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50">
                    @if($tpl->cover_img)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($tpl->cover_img) }}"
                         alt="" class="w-12 h-8 object-cover rounded border border-gray-200 shrink-0">
                    @else
                    <div class="w-12 h-8 rounded border border-gray-200 bg-gray-100 flex items-center justify-center shrink-0">
                        <span class="text-[10px] text-gray-400">IMG</span>
                    </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 truncate">{{ $tpl->nama_template }}</p>
                        <p class="text-[10px] text-gray-400">{{ $tpl->jenis }} · {{ $tpl->updated_at->diffForHumans() }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold
                            {{ $tpl->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $tpl->status }}
                        </span>
                        <p class="text-[10px] text-gray-400 mt-0.5">Kirim: {{ $tpl->kirim_count }}x</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

    {{-- Quick link --}}
    <div class="mt-5 flex gap-3">
        <a href="{{ route('karyawan.greeting.template') }}"
           class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-[#154d26] transition-colors">
            <span>+ Input Template Baru</span>
        </a>
    </div>

</div>


@php
    $role = auth()->user()?->role;
    if ($role === 'tenant') {
        redirect()->route('tenant.dashboard')->send();
        return;
    }
@endphp

{{-- ══════════════════════════════════════════
     KARYAWAN DASHBOARD
══════════════════════════════════════════ --}}
@if($role === 'karyawan')
@php
    try {
        $trStatusCounts = \App\Models\TenantRequest::select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status')
            ->pluck('jumlah', 'status')
            ->toArray();
    } catch (\Exception $e) {
        $trStatusCounts = [];
    }
    $trStatuses = \App\Models\TenantRequest::statusOptions();

    try {
        $strCount  = \App\Models\HandoverUnit::count();
        $woPending = \App\Models\WorkOrder::pending()->count();
        $woClose   = \App\Models\WorkOrder::where('status_comp', 'Work Order Close')->count();
        $complain  = \App\Models\TenantRequest::whereNotIn('status', ['Selesai'])->count();
    } catch (\Exception $e) {
        $strCount = $woPending = $woClose = $complain = 0;
    }
@endphp
<x-layouts.karyawan :woPending="$woPending">

    <div class="px-5 py-4">

        {{-- ── Summary Stat Row ── --}}
        <div class="flex justify-center gap-4 mb-5 flex-wrap">
            <div class="bg-white border border-gray-300 rounded shadow-sm px-4 py-2 text-center min-w-[120px]">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Unit STR</p>
                <p class="text-xl font-bold text-gray-800">{{ number_format($strCount) }}</p>
            </div>
            <div class="bg-white border border-red-200 rounded shadow-sm px-4 py-2 text-center min-w-[120px]">
                <p class="text-xs font-bold text-red-400 uppercase tracking-wide">WO Pending</p>
                <p class="text-xl font-bold text-red-600">{{ number_format($woPending) }}</p>
            </div>
            <div class="bg-white border border-green-200 rounded shadow-sm px-4 py-2 text-center min-w-[120px]">
                <p class="text-xs font-bold text-green-600 uppercase tracking-wide">WO Selesai</p>
                <p class="text-xl font-bold text-green-700">{{ number_format($woClose) }}</p>
            </div>
            <div class="bg-white border border-yellow-200 rounded shadow-sm px-4 py-2 text-center min-w-[120px]">
                <p class="text-xs font-bold text-yellow-500 uppercase tracking-wide">Request Open</p>
                <p class="text-xl font-bold text-yellow-600">{{ number_format($complain) }}</p>
            </div>
        </div>

        {{-- ── Tenant Request (centered) ── --}}
        <div class="flex justify-center mb-5">
            <div class="border border-gray-400 shadow-sm" style="width: 400px;">
                <div class="flex items-center justify-between px-3 py-1.5"
                     style="background: linear-gradient(to bottom, #d6d6d6, #c8c8c8); border-bottom: 1px solid #b0b0b0;">
                    <span class="text-sm font-semibold text-gray-700">Tenant Request</span>
                    <a href="{{ route('karyawan.cs.tenant-request-belum') }}"
                       class="text-[10px] text-[#1a5c2e] hover:underline">Lihat semua →</a>
                </div>

                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr style="background-color: #c5dde2;">
                            <th class="border border-gray-400 px-2 py-1.5 w-8 text-center font-semibold text-gray-600"></th>
                            <th class="border border-gray-400 px-4 py-1.5 text-left font-semibold text-gray-600 tracking-wide">STATUS</th>
                            <th class="border border-gray-400 px-4 py-1.5 text-center font-semibold text-gray-600 tracking-wide">JUMLAH</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trStatuses as $i => $status)
                        <tr class="bg-white hover:bg-blue-50 cursor-pointer"
                            onclick="window.location='{{ route('karyawan.cs.tenant-request-belum') }}'">
                            <td class="border border-gray-300 px-2 py-1.5 text-center text-gray-500">{{ $i + 1 }}</td>
                            <td class="border border-gray-300 px-4 py-1.5" style="color: #3a9aaa;">{{ $status }}</td>
                            <td class="border border-gray-300 px-4 py-1.5 text-center text-gray-700 font-semibold">
                                {{ number_format($trStatusCounts[$status] ?? 0) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #eef6f7;">
                            <td colspan="2" class="border border-gray-300 px-4 py-1.5 text-center font-semibold text-gray-600">Total</td>
                            <td class="border border-gray-300 px-4 py-1.5 text-center font-bold text-gray-800">
                                {{ number_format(array_sum($trStatusCounts)) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <div class="px-2 py-1 border-t border-gray-400 text-[11px] text-gray-500"
                     style="background-color: #f0f0f0;">
                    Page 1 of 1
                </div>
            </div>
        </div>

        {{-- ── Unit Search (di bawah tabel, centered) ── --}}
        <div class="flex justify-center mb-5">
            <livewire:karyawan.unit-search />
        </div>

        {{-- WO Chart --}}
        <div class="mt-5">
            <livewire:dashboard.wo-chart />
        </div>

    </div>

</x-layouts.karyawan>

{{-- ══════════════════════════════════════════
     TENANT / ADMIN DASHBOARD
══════════════════════════════════════════ --}}
@else
<x-layouts.admin>
    <x-slot name="header">Dashboard</x-slot>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <x-admin.stat-card label="Total Pengguna" value="1,284" icon="users"  color="indigo" change="12%"  changeType="up" />
        <x-admin.stat-card label="Pendapatan"     value="Rp 48,5jt" icon="cash" color="green"  change="8.2%" changeType="up" />
        <x-admin.stat-card label="Tugas Selesai"  value="342"    icon="check" color="yellow" change="3%"   changeType="down" />
        <x-admin.stat-card label="Pertumbuhan"    value="24.5%"  icon="chart" color="red"    change="5.1%" changeType="up" />
    </div>

    {{-- Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Aktivitas Terbaru --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-semibold text-gray-700 dark:text-gray-200">Aktivitas Terbaru</h2>
                <a href="#" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Lihat semua</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ([
                    ['user' => 'Budi Santoso',  'action' => 'mendaftar sebagai pengguna baru', 'time' => '2 menit lalu',  'color' => 'bg-green-500'],
                    ['user' => 'Siti Rahma',    'action' => 'memperbarui profil',              'time' => '15 menit lalu', 'color' => 'bg-blue-500'],
                    ['user' => 'Ahmad Fauzi',   'action' => 'mengirim laporan baru',           'time' => '1 jam lalu',    'color' => 'bg-yellow-500'],
                    ['user' => 'Dewi Kusuma',   'action' => 'menyelesaikan tugas #47',         'time' => '3 jam lalu',    'color' => 'bg-indigo-500'],
                    ['user' => 'Rizky Pratama', 'action' => 'mengubah pengaturan akun',        'time' => '5 jam lalu',    'color' => 'bg-pink-500'],
                ] as $item)
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full {{ $item['color'] }} flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr($item['user'], 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700 dark:text-gray-200">
                            <span class="font-medium">{{ $item['user'] }}</span> {{ $item['action'] }}
                        </p>
                        <p class="text-xs text-gray-400">{{ $item['time'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="flex flex-col gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-700 dark:text-gray-200">Status Sistem</h2>
                </div>
                <div class="px-5 py-4 space-y-3">
                    @foreach ([
                        ['label' => 'Server',   'status' => 'Online', 'color' => 'bg-green-500'],
                        ['label' => 'Database', 'status' => 'Online', 'color' => 'bg-green-500'],
                        ['label' => 'Cache',    'status' => 'Online', 'color' => 'bg-green-500'],
                        ['label' => 'Queue',    'status' => 'Idle',   'color' => 'bg-yellow-500'],
                    ] as $s)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $s['label'] }}</span>
                        <span class="flex items-center gap-1.5 text-xs">
                            <span class="w-2 h-2 rounded-full {{ $s['color'] }}"></span>
                            <span class="text-gray-600 dark:text-gray-300">{{ $s['status'] }}</span>
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm flex-1">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-700 dark:text-gray-200">Pengguna Baru</h2>
                    <a href="#" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Semua</a>
                </div>
                <div class="px-5 py-3 space-y-3">
                    @foreach ([
                        ['name' => 'Budi Santoso', 'email' => 'budi@mail.com',  'color' => 'bg-green-500'],
                        ['name' => 'Siti Rahma',   'email' => 'siti@mail.com',  'color' => 'bg-blue-500'],
                        ['name' => 'Ahmad Fauzi',  'email' => 'ahmad@mail.com', 'color' => 'bg-yellow-500'],
                    ] as $u)
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full {{ $u['color'] }} flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                            {{ strtoupper(substr($u['name'], 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate">{{ $u['name'] }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $u['email'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
@endif

@php
    $userDept = auth()->user()?->karyawan?->departemen ?? '';
    try {
        $woPending    = \App\Models\WorkOrder::pending()->count();
    } catch (\Exception $e) {
        $woPending = 0;
    }
    try {
        $complainCount = \App\Models\TenantRequest::where('is_selesai', false)->count();
    } catch (\Exception $e) {
        $complainCount = 0;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AMS — ' . config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|montserrat:800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }

        /* Pastikan semua select punya ruang cukup agar teks tidak tertindih panah dropdown */
        select { padding-right: 1.5rem !important; min-width: 3rem; }
    </style>
</head>
<body class="font-sans antialiased bg-white text-gray-800 text-sm">

    {{-- ── Header ── --}}
    <header style="background: linear-gradient(to right, #e8f5e9 0%, #f5faf5 40%, #ffffff 100%);
                   border-bottom: 1px solid #d0ddd0;">
        <div class="flex items-center justify-between px-5 py-2">

            {{-- Logo --}}
            <div class="leading-none select-none">
                <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:2rem;letter-spacing:0.15em;line-height:1;background:linear-gradient(135deg,#1a5c2e 0%,#2d9e56 60%,#4ade80 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">AMS</div>
                <div style="font-size:10px;color:#6b7280;letter-spacing:0.07em;margin-top:1px;font-weight:500;">Apartement Management System</div>
            </div>

            {{-- Info Bar --}}
            <div class="flex items-center gap-2 text-[12px] text-gray-600 flex-wrap justify-end">
                <span class="font-medium">PROYEK : Madison Park (MAP)</span>
                <span class="text-gray-300">|</span>
                <span>User Login : <span class="font-semibold uppercase">{{ auth()->user()?->name }}</span></span>
                <span class="text-gray-300">|</span>
                @php
                    $woPendingUrl  = in_array($userDept, ['AM','CS','ENG'])
                        ? route('karyawan.cs.work-order')
                        : route('dashboard');
                    $complainUrl   = in_array($userDept, ['AM','CS','ENG'])
                        ? route('karyawan.cs.tenant-request-belum')
                        : route('dashboard');
                @endphp
                <a href="{{ $woPendingUrl }}"
                   class="flex items-center gap-1 font-medium hover:underline"
                   style="color: #cc0000;">
                    WO Pending
                    <span class="inline-flex items-center justify-center min-w-[20px] h-5
                                 bg-red-600 text-white text-[10px] font-bold rounded-full px-1.5">
                        {{ $woPending }}
                    </span>
                </a>
                <span class="text-gray-300">|</span>
                <a href="{{ $complainUrl }}"
                   class="flex items-center gap-1 font-medium hover:underline"
                   style="color: #cc0000;">
                    Complain
                    <span class="inline-flex items-center justify-center min-w-[20px] h-5
                                 bg-red-600 text-white text-[10px] font-bold rounded-full px-1.5">
                        {{ $complainCount }}
                    </span>
                </a>
                <span class="text-gray-300">|</span>
                @auth
                @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="relative flex items-center gap-1 text-gray-600 hover:text-[#1a5c2e] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @if($unreadCount > 0)
                        <span class="absolute -top-1.5 -right-1.5 flex items-center justify-center min-w-[15px] h-[15px]
                                     bg-red-600 text-white text-[9px] font-bold rounded-full px-0.5 leading-none">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                        @endif
                    </button>

                    {{-- Notification Dropdown --}}
                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute right-0 top-full mt-2 w-80 bg-white border border-gray-200 rounded shadow-xl z-50"
                         style="margin-top: 4px;">
                        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100 bg-gray-50 rounded-t">
                            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Notifikasi Eskalasi WO</span>
                            @if($unreadCount > 0)
                            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                                @csrf
                                <button type="submit" class="text-[10px] text-[#2a8c56] hover:underline">Tandai semua dibaca</button>
                            </form>
                            @endif
                        </div>
                        @php
                            $notifs = auth()->user()->notifications()
                                ->where('type', 'App\Notifications\WoEscalationNotification')
                                ->latest()
                                ->limit(8)
                                ->get();
                        @endphp
                        @forelse($notifs as $notif)
                        <div class="px-4 py-2.5 border-b border-gray-50 hover:bg-gray-50 transition-colors
                                    {{ is_null($notif->read_at) ? 'bg-yellow-50' : '' }}">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 shrink-0 text-sm">
                                    {{ ($notif->data['level'] ?? 1) === 2 ? '🚨' : '⚠️' }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-medium text-gray-800 leading-snug">
                                        {{ $notif->data['message'] ?? '-' }}
                                    </p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                        {{ $notif->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="px-4 py-5 text-center text-xs text-gray-400">Tidak ada notifikasi.</div>
                        @endforelse
                        <div class="px-4 py-2 text-center border-t border-gray-100 bg-gray-50 rounded-b">
                            <a href="{{ route('notifications.index') }}" class="text-[11px] text-[#2a8c56] hover:underline font-medium">
                                Lihat semua notifikasi →
                            </a>
                        </div>
                    </div>
                </div>
                <span class="text-gray-300">|</span>
                @endauth
                <a href="{{ route('profile') }}"
                   class="text-[#2a8c56] hover:underline font-medium">Change Password</a>
                <span class="text-gray-300">|</span>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-[#2a8c56] hover:underline font-medium">Logout</button>
                </form>
            </div>
        </div>
    </header>

    {{-- ── Navigation ── --}}
    <nav class="border-b border-gray-300 bg-white" x-data>
        <div class="flex items-center px-4">

            {{-- Home --}}
            <x-karyawan.nav-tab route="dashboard" :active="request()->routeIs('dashboard')">
                Home
            </x-karyawan.nav-tab>

            {{-- Setup (AM, CS only) --}}
            @if(in_array($userDept, ['AM','CS']))
            <div x-data="{ open: false }"
                 @mouseenter="open = true"
                 @mouseleave="open = false"
                 class="relative">

                <button
                    class="px-4 py-2.5 inline-flex items-center gap-1 border-b-2 transition-colors text-sm
                           {{ request()->routeIs('karyawan.setup') || request()->routeIs('karyawan.table-staff')
                              ? 'border-[#1a5c2e] text-[#1a5c2e] font-semibold'
                              : 'border-transparent text-gray-600 hover:text-[#1a5c2e] hover:border-gray-300' }}"
                    :class="{ 'border-[#1a5c2e] text-[#1a5c2e]': open }"
                >
                    Setup
                    <svg class="w-3 h-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak
                     class="absolute left-0 top-full z-50 bg-white border border-gray-300 shadow-lg min-w-40 py-1"
                     style="margin-top: -1px;">
                    @php
                        $setupMenus = [
                            ['route' => 'karyawan.setup',       'label' => 'Setup'],
                            ['route' => 'karyawan.table-staff', 'label' => 'Staff'],
                        ];
                    @endphp
                    @foreach($setupMenus as $menu)
                    @php
                        try { $href = route($menu['route']); } catch (\Exception $e) { $href = '#'; }
                        $isActive = request()->routeIs($menu['route']);
                    @endphp
                    <a href="{{ $href }}"
                       class="block px-4 py-1.5 text-sm hover:bg-[#e8f5e9] whitespace-nowrap
                              {{ $isActive ? 'bg-[#e8f5e9] text-[#1a5c2e] font-semibold' : 'text-gray-700' }}">
                        {{ $menu['label'] }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Customer Services (AM, CS, ENG, FA, HKP, SEC) --}}
            @if(in_array($userDept, ['AM','CS','ENG','FA','HKP','SEC']))
            <div x-data="{ open: false }"
                 @mouseenter="open = true"
                 @mouseleave="open = false"
                 class="relative">

                <button
                    class="px-4 py-2.5 inline-flex items-center gap-1 border-b-2 transition-colors text-sm
                           {{ request()->routeIs('karyawan.cs.*')
                              ? 'border-[#1a5c2e] text-[#1a5c2e] font-semibold'
                              : 'border-transparent text-gray-600 hover:text-[#1a5c2e] hover:border-gray-300' }}"
                    :class="{ 'border-[#1a5c2e] text-[#1a5c2e]': open }"
                >
                    Customer Services
                    <svg class="w-3 h-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak
                     class="absolute left-0 top-full z-50 bg-white border border-gray-300 shadow-lg min-w-48 py-1"
                     style="margin-top: -1px;">
                    @php
                        // WO + Tenant Request + Report (tanpa Price List)
                        $csMenusWo = [
                            ['route' => 'karyawan.cs.work-order',            'label' => 'Work Order'],
                            ['route' => 'karyawan.cs.work-order-close',      'label' => 'Work Order Close'],
                            ['route' => 'karyawan.cs.tenant-request-belum',  'label' => 'Tenant Request Belum Selesai'],
                            ['route' => 'karyawan.cs.tenant-request-selesai','label' => 'Tenant Request Selesai'],
                            ['route' => 'karyawan.cs.work-order-report',     'label' => 'Work Order Report'],
                            ['route' => 'karyawan.cs.grafik',                'label' => 'Grafik'],
                        ];
                        // Price List (AM, CS, ENG, FA — bukan HKP/SEC)
                        $csMenuPriceList  = ['route' => 'karyawan.cs.item-master',          'label' => 'Price List'];
                        $csMenuInOutPermit = ['route' => 'karyawan.cs.in-out-permit',        'label' => 'In Out Permit'];
                        $csMenuFasilitas   = ['route' => 'karyawan.cs.facility-reservation', 'label' => 'Facility Reservation'];
                        // Hanya AM & CS
                        $csMenusAmCs = [
                            ['route' => 'karyawan.emergency',           'label' => 'Emergency List'],
                            ['route' => 'karyawan.broadcast-pesan',     'label' => 'Broadcast Pesan'],
                            ['route' => 'karyawan.kelola-faq',          'label' => 'Kelola FAQ'],
                            ['route' => 'karyawan.kelola-dokumen',      'label' => 'Kelola Dokumen'],
                        ];

                        if (in_array($userDept, ['AM','CS'])) {
                            $csMenus = array_merge($csMenusWo, [$csMenuPriceList, $csMenuInOutPermit, $csMenuFasilitas], $csMenusAmCs);
                        } elseif ($userDept === 'ENG') {
                            // WO + Price List + InOutPermit + Fasilitas
                            $csMenus = array_merge($csMenusWo, [$csMenuPriceList, $csMenuInOutPermit, $csMenuFasilitas]);
                        } elseif ($userDept === 'FA') {
                            // WO + Price List + InOutPermit (approval WO berbayar & permit)
                            $csMenus = array_merge($csMenusWo, [$csMenuPriceList, $csMenuInOutPermit]);
                        } elseif ($userDept === 'SEC') {
                            // WO + InOutPermit + Fasilitas (tanpa Price List)
                            $csMenus = array_merge($csMenusWo, [$csMenuInOutPermit, $csMenuFasilitas]);
                        } elseif ($userDept === 'HKP') {
                            // WO + Fasilitas (tanpa Price List, tanpa InOutPermit)
                            $csMenus = array_merge($csMenusWo, [$csMenuFasilitas]);
                        } else {
                            $csMenus = [];
                        }
                    @endphp
                    @foreach ($csMenus as $menu)
                    @php
                        try { $href = route($menu['route']); } catch (\Exception $e) { $href = '#'; }
                        $isActive = request()->routeIs($menu['route']);
                    @endphp
                    <a href="{{ $href }}"
                       class="block px-4 py-1.5 text-sm hover:bg-[#e8f5e9] whitespace-nowrap
                              {{ $isActive ? 'bg-[#e8f5e9] text-[#1a5c2e] font-semibold' : 'text-gray-700' }}">
                        {{ $menu['label'] }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Finance (AM, FA: semua; CS: SOA saja) --}}
            @if(in_array($userDept, ['AM','CS','FA']))
            <div x-data="{ open: false }"
                 @mouseenter="open = true"
                 @mouseleave="open = false"
                 class="relative">

                <button
                    class="px-4 py-2.5 inline-flex items-center gap-1 border-b-2 transition-colors text-sm
                           {{ request()->routeIs('karyawan.fa.*')
                              ? 'border-[#1a5c2e] text-[#1a5c2e] font-semibold'
                              : 'border-transparent text-gray-600 hover:text-[#1a5c2e] hover:border-gray-300' }}"
                    :class="{ 'border-[#1a5c2e] text-[#1a5c2e]': open }"
                >
                    Finance
                    <svg class="w-3 h-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak
                     class="absolute left-0 top-full z-50 bg-white border border-gray-300 shadow-lg min-w-48 py-1"
                     style="margin-top: -1px;">
                    @php
                        $faMenusAll = [
                            ['route' => 'karyawan.fa.invoice-list',       'label' => 'Invoice'],
                            ['route' => 'karyawan.debtor',                'label' => 'Statement of Account'],
                            ['route' => 'karyawan.fa.daya-unit',          'label' => 'Daya Unit'],
                            ['route' => 'karyawan.billing-status',        'label' => 'Status Billing Unit'],
                            ['route' => 'karyawan.fa.wo-approval',        'label' => 'Approval WO Berbayar'],
                            ['route' => 'karyawan.fa.permit-approval',    'label' => 'Approval In Out Permit'],
                            ['route' => 'karyawan.fa.facility-approval',  'label' => 'Konfirmasi Bayar Fasilitas'],
                        ];
                        $faMenusCs = [
                            ['route' => 'karyawan.debtor', 'label' => 'Statement of Account'],
                        ];
                        $faMenus = in_array($userDept, ['AM','FA']) ? $faMenusAll : $faMenusCs;
                    @endphp
                    @foreach($faMenus as $menu)
                    @php
                        try { $href = route($menu['route']); } catch (\Exception $e) { $href = '#'; }
                        $isActive = request()->routeIs($menu['route']);
                    @endphp
                    <a href="{{ $href }}"
                       class="block px-4 py-1.5 text-sm hover:bg-[#e8f5e9] whitespace-nowrap
                              {{ $isActive ? 'bg-[#e8f5e9] text-[#1a5c2e] font-semibold' : 'text-gray-700' }}">
                        {{ $menu['label'] }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Greeting (AM, CS only) --}}
            @if(in_array($userDept, ['AM','CS']))
            <div x-data="{ open: false }"
                 @mouseenter="open = true"
                 @mouseleave="open = false"
                 class="relative">

                <button
                    class="px-4 py-2.5 inline-flex items-center gap-1 border-b-2 transition-colors text-sm
                           {{ request()->routeIs('karyawan.greeting.*')
                              ? 'border-[#1a5c2e] text-[#1a5c2e] font-semibold'
                              : 'border-transparent text-gray-600 hover:text-[#1a5c2e] hover:border-gray-300' }}"
                    :class="{ 'border-[#1a5c2e] text-[#1a5c2e]': open }"
                >
                    Greeting
                    <svg class="w-3 h-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak
                     class="absolute left-0 top-full z-50 bg-white border border-gray-300 shadow-lg min-w-44 py-1"
                     style="margin-top: -1px;">
                    @php
                        $greetingMenus = [
                            ['route' => 'karyawan.greeting.dashboard', 'label' => 'Dashboard Greeting'],
                            ['route' => 'karyawan.greeting.template',  'label' => 'Template'],
                            ['route' => 'karyawan.greeting.banner',    'label' => 'Banner / Pengumuman'],
                        ];
                    @endphp
                    @foreach($greetingMenus as $menu)
                    @php
                        try { $href = route($menu['route']); } catch (\Exception $e) { $href = '#'; }
                        $isActive = request()->routeIs($menu['route']);
                    @endphp
                    <a href="{{ $href }}"
                       class="block px-4 py-1.5 text-sm hover:bg-[#e8f5e9] whitespace-nowrap
                              {{ $isActive ? 'bg-[#e8f5e9] text-[#1a5c2e] font-semibold' : 'text-gray-700' }}">
                        {{ $menu['label'] }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Fasilitas, Serah Terima Unit, Laporan (AM, CS only) --}}
            @if(in_array($userDept, ['AM','CS']))

            <x-karyawan.nav-tab route="karyawan.fasilitas" :active="request()->routeIs('karyawan.fasilitas')">
                Fasilitas
            </x-karyawan.nav-tab>

            {{-- Serah Terima Unit (dropdown) --}}
            <div x-data="{ open: false }"
                 @mouseenter="open = true"
                 @mouseleave="open = false"
                 class="relative">

                <button
                    class="px-4 py-2.5 inline-flex items-center gap-1 border-b-2 transition-colors text-sm
                           {{ request()->routeIs('karyawan.serah-terima') || request()->routeIs('karyawan.checklist-unit')
                              ? 'border-[#1a5c2e] text-[#1a5c2e] font-semibold'
                              : 'border-transparent text-gray-600 hover:text-[#1a5c2e] hover:border-gray-300' }}"
                    :class="{ 'border-[#1a5c2e] text-[#1a5c2e]': open }"
                >
                    Serah Terima Unit
                    <svg class="w-3 h-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak
                     class="absolute left-0 top-full z-50 bg-white border border-gray-300 shadow-lg min-w-44 py-1"
                     style="margin-top: -1px;">
                    @php
                        $strMenus = [
                            ['route' => 'karyawan.serah-terima',   'label' => 'Serah Terima Unit'],
                            ['route' => 'karyawan.checklist-unit', 'label' => 'Checklist Unit'],
                        ];
                    @endphp
                    @foreach($strMenus as $menu)
                    @php
                        try { $href = route($menu['route']); } catch (\Exception $e) { $href = '#'; }
                        $isActive = request()->routeIs($menu['route']);
                    @endphp
                    <a href="{{ $href }}"
                       class="block px-4 py-1.5 text-sm hover:bg-[#e8f5e9] whitespace-nowrap
                              {{ $isActive ? 'bg-[#e8f5e9] text-[#1a5c2e] font-semibold' : 'text-gray-700' }}">
                        {{ $menu['label'] }}
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Laporan --}}
            <div x-data="{ open: false }"
                 @mouseenter="open = true"
                 @mouseleave="open = false"
                 class="relative">

                <button
                    class="px-4 py-2.5 inline-flex items-center gap-1 border-b-2 transition-colors text-sm
                           {{ request()->routeIs('karyawan.laporan-bulanan') || request()->routeIs('karyawan.approval-center')
                              ? 'border-[#1a5c2e] text-[#1a5c2e] font-semibold'
                              : 'border-transparent text-gray-600 hover:text-[#1a5c2e] hover:border-gray-300' }}"
                    :class="{ 'border-[#1a5c2e] text-[#1a5c2e]': open }"
                >
                    Laporan
                    <svg class="w-3 h-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak
                     class="absolute left-0 top-full z-50 bg-white border border-gray-300 shadow-lg min-w-44 py-1"
                     style="margin-top: -1px;">
                    @php
                        $laporanMenus = [
                            ['route' => 'karyawan.laporan-bulanan', 'label' => 'Laporan Bulanan'],
                            ['route' => 'karyawan.approval-center', 'label' => 'Approval Center'],
                        ];
                    @endphp
                    @foreach($laporanMenus as $menu)
                    @php
                        try { $href = route($menu['route']); } catch (\Exception $e) { $href = '#'; }
                        $isActive = request()->routeIs($menu['route']);
                    @endphp
                    <a href="{{ $href }}"
                       class="block px-4 py-1.5 text-sm hover:bg-[#e8f5e9] whitespace-nowrap
                              {{ $isActive ? 'bg-[#e8f5e9] text-[#1a5c2e] font-semibold' : 'text-gray-700' }}">
                        {{ $menu['label'] }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Engineering (ENG only) --}}
            @if($userDept === 'ENG')
            <div x-data="{ open: false }"
                 @mouseenter="open = true"
                 @mouseleave="open = false"
                 class="relative">

                <button
                    class="px-4 py-2.5 inline-flex items-center gap-1 border-b-2 transition-colors text-sm
                           {{ request()->routeIs('karyawan.wo-saya') || request()->routeIs('karyawan.preventive-maintenance') || request()->routeIs('karyawan.laporan-harian') || request()->routeIs('karyawan.billing-status') || request()->routeIs('karyawan.fa.daya-unit')
                              ? 'border-[#1a5c2e] text-[#1a5c2e] font-semibold'
                              : 'border-transparent text-gray-600 hover:text-[#1a5c2e] hover:border-gray-300' }}"
                    :class="{ 'border-[#1a5c2e] text-[#1a5c2e]': open }"
                >
                    Engineering
                    <svg class="w-3 h-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak
                     class="absolute left-0 top-full z-50 bg-white border border-gray-300 shadow-lg min-w-48 py-1"
                     style="margin-top: -1px;">
                    @php
                        $engMenus = [
                            ['route' => 'karyawan.wo-saya',                'label' => 'WO Saya'],
                            ['route' => 'karyawan.preventive-maintenance', 'label' => 'Preventive Maintenance'],
                            ['route' => 'karyawan.laporan-harian',         'label' => 'Laporan Harian'],
                            ['route' => 'karyawan.fa.daya-unit',           'label' => 'Daya Unit'],
                            ['route' => 'karyawan.billing-status',         'label' => 'Status Billing Unit'],
                        ];
                    @endphp
                    @foreach($engMenus as $menu)
                    @php
                        try { $href = route($menu['route']); } catch (\Exception $e) { $href = '#'; }
                        $isActive = request()->routeIs($menu['route']);
                    @endphp
                    <a href="{{ $href }}"
                       class="block px-4 py-1.5 text-sm hover:bg-[#e8f5e9] whitespace-nowrap
                              {{ $isActive ? 'bg-[#e8f5e9] text-[#1a5c2e] font-semibold' : 'text-gray-700' }}">
                        {{ $menu['label'] }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </nav>

    {{-- ── Content ── --}}
    <main class="min-h-screen bg-white">
        {{ $slot }}
    </main>

</body>
</html>

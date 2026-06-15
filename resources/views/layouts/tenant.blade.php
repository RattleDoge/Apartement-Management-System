@php
    use Illuminate\Support\Facades\Storage;
    $tenantProfile = auth()->user()?->tenant;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Tenant Portal — ' . config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|montserrat:800,900&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        select { padding-right: 1.5rem !important; min-width: 3rem; }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-800 text-sm">

    {{-- ── Header ── --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30"
            style="box-shadow: 0 1px 4px rgba(0,0,0,.06);">
        <div class="px-5 py-3 flex items-center justify-between">

            {{-- Logo + Property --}}
            <a href="{{ route('tenant.dashboard') }}" class="flex items-center gap-4 select-none">
                <div class="leading-none">
                    <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.7rem;letter-spacing:0.15em;line-height:1;background:linear-gradient(135deg,#1a5c2e 0%,#2d9e56 60%,#4ade80 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">AMS</div>
                    <div style="font-size:9px;color:#9ca3af;letter-spacing:0.07em;margin-top:1px;font-weight:500;">Apartement Management System</div>
                </div>
                <div class="h-8 w-px bg-gray-200"></div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-medium">Apartemen</p>
                    <p class="text-sm font-semibold text-gray-700 leading-tight">Madison Park (MAP)</p>
                </div>
            </a>

            {{-- Right: Unit + Name + Notifications + Logout --}}
            <div class="flex items-center gap-3" x-data>
                @if($tenantProfile?->unit_number)
                <span class="hidden sm:flex items-center gap-1.5 bg-[#e8f5e9] text-[#1a5c2e] text-xs font-semibold px-3 py-1.5 rounded-full border border-[#c8e6c9]">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.75L12 3l9 6.75V21H3V9.75z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 21V12h6v9"/>
                    </svg>
                    {{ $tenantProfile->unit_number }}
                </span>
                @endif

                <span class="hidden sm:block text-xs text-gray-600 font-medium">
                    {{ auth()->user()?->name }}
                </span>

                {{-- Notification Bell --}}
                @auth
                @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="relative flex items-center text-gray-400 hover:text-[#1a5c2e] transition-colors p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @if($unreadCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[14px] h-[14px]
                                     bg-red-500 text-white text-[8px] font-bold rounded-full px-0.5">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                        @endif
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute right-0 top-full mt-2 w-80 bg-white rounded-xl border border-gray-100 shadow-xl z-50">
                        <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50 rounded-t-xl flex items-center justify-between">
                            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Notifikasi</p>
                            @if($unreadCount > 0)
                            <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-[10px] text-[#1a5c2e] hover:underline font-medium">
                                    Tandai semua dibaca
                                </button>
                            </form>
                            @endif
                        </div>
                        @php
                            $recentNotifs = auth()->user()->notifications()->latest()->take(6)->get();
                        @endphp
                        @if($recentNotifs->isEmpty())
                        <div class="px-4 py-5 text-center text-xs text-gray-400">Tidak ada notifikasi.</div>
                        @else
                        <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto">
                            @foreach($recentNotifs as $notif)
                            @php
                                $isRead      = $notif->read_at !== null;
                                $isGreeting  = ($notif->data['type'] ?? '') === 'greeting';
                                $coverImg    = $isGreeting && !empty($notif->data['cover_img'])
                                                ? Storage::url($notif->data['cover_img'])
                                                : null;
                            @endphp
                            <div class="px-4 py-3 {{ $isRead ? 'bg-white' : ($isGreeting ? 'bg-green-50' : 'bg-blue-50') }}">
                                @if($coverImg)
                                <img src="{{ $coverImg }}" alt="" class="w-full h-16 object-cover rounded mb-1.5 border border-gray-100">
                                @endif
                                <div class="flex items-start gap-1.5">
                                    @if($isGreeting)
                                    <span class="text-[10px] px-1.5 py-0.5 bg-green-100 text-green-700 rounded font-semibold shrink-0">
                                        {{ $notif->data['jenis'] ?? 'Info' }}
                                    </span>
                                    @endif
                                    <p class="text-xs {{ $isRead ? 'text-gray-600' : 'text-gray-800 font-semibold' }} leading-snug">
                                        {{ $notif->data['message'] ?? 'Notifikasi baru' }}
                                    </p>
                                </div>
                                @if(!empty($notif->data['no_wo']))
                                <p class="text-[10px] text-gray-400 mt-0.5 font-mono">{{ $notif->data['no_wo'] }}</p>
                                @endif
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $notif->created_at->diffForHumans() }}</p>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        <div class="px-4 py-2 border-t border-gray-100 bg-gray-50 rounded-b-xl text-center">
                            <a href="{{ route('notifications.index') }}" class="text-[10px] text-[#1a5c2e] hover:underline font-medium">
                                Lihat semua notifikasi
                            </a>
                        </div>
                    </div>
                </div>
                @endauth

                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="text-xs font-medium text-gray-500 hover:text-red-600 px-3 py-1.5 rounded-lg
                                   border border-gray-200 hover:border-red-200 hover:bg-red-50 transition-colors">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- ── Tenant Navigation ── --}}
    <nav class="bg-white border-b border-gray-200 sticky top-[57px] z-20"
         style="box-shadow: 0 1px 3px rgba(0,0,0,.04);">
        <div class="px-5 overflow-x-auto">
            <div class="flex items-center gap-0.5 whitespace-nowrap py-0.5">
                @php
                    $tenantNavs = [
                        ['route' => 'tenant.dashboard',          'label' => 'Beranda'],
                        ['route' => 'tenant.profil-unit',        'label' => 'Profil Unit'],
                        ['route' => 'tenant.tracking-wo',        'label' => 'Tracking WO'],
                        ['route' => 'tenant.request',            'label' => 'Complain'],
                        ['route' => 'tenant.riwayat-bayar',      'label' => 'Riwayat Bayar'],
                        ['route' => 'tenant.cek-invoice',        'label' => 'Invoice'],
                        ['route' => 'tenant.in-out-permit',      'label' => 'In-Out Permit'],
                        ['route' => 'tenant.facility-reservation','label' => 'Fasilitas'],
                        ['route' => 'tenant.jadwal-fasilitas',   'label' => 'Jadwal Fasilitas'],
                        ['route' => 'tenant.dokumen-penting',    'label' => 'Dokumen'],
                        ['route' => 'tenant.faq',                'label' => 'FAQ'],
                    ];
                @endphp
                @foreach($tenantNavs as $nav)
                @php
                    try { $href = route($nav['route']); } catch (\Exception $e) { $href = '#'; }
                    $isActive = request()->routeIs($nav['route']);
                @endphp
                <a href="{{ $href }}"
                   class="px-3 py-2.5 text-xs font-medium border-b-2 transition-colors whitespace-nowrap
                          {{ $isActive
                              ? 'border-[#1a5c2e] text-[#1a5c2e] font-semibold'
                              : 'border-transparent text-gray-500 hover:text-[#1a5c2e] hover:border-gray-300' }}">
                    {{ $nav['label'] }}
                </a>
                @endforeach
            </div>
        </div>
    </nav>

    {{-- ── Content ── --}}
    <main class="min-h-screen">
        {{ $slot }}
    </main>

    {{-- ── Emergency Floating Button ── --}}
    @auth
    <div x-data="{ open: false }" class="fixed bottom-6 right-6 z-50">

        {{-- Button --}}
        <button @click="open = !open"
                class="flex items-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-full shadow-lg transition-colors"
                style="box-shadow: 0 4px 16px rgba(220,38,38,.45);">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
            </svg>
            Emergency
        </button>

        {{-- Modal --}}
        <div x-show="open" x-cloak @click.outside="open = false"
             class="absolute bottom-12 right-0 w-80 bg-white rounded-xl border border-gray-200 shadow-2xl overflow-hidden"
             style="max-height: 70vh;">

            <div class="px-4 py-2.5 bg-red-600 flex items-center justify-between">
                <div class="flex items-center gap-2 text-white">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <span class="text-sm font-bold">Kontak Darurat</span>
                </div>
                <button @click="open = false" class="text-red-200 hover:text-white text-lg leading-none">✕</button>
            </div>

            @php
                $emergencyContacts = \App\Models\EmergencyContact::orderBy('kategori')->orderBy('nama')->get();
            @endphp

            @if($emergencyContacts->isEmpty())
            <div class="px-4 py-6 text-center text-xs text-gray-400">
                Belum ada kontak darurat.
            </div>
            @else
            <div class="overflow-y-auto" style="max-height: calc(70vh - 48px);">
                @php $lastKat = null; @endphp
                @foreach($emergencyContacts as $ec)
                @if($ec->kategori !== $lastKat)
                @php
                    $katColor = match($ec->kategori) {
                        'Rumah Sakit','Klinik' => 'text-red-700 bg-red-50',
                        'Kantor Polisi'         => 'text-blue-700 bg-blue-50',
                        'Pemadam Kebakaran'     => 'text-orange-700 bg-orange-50',
                        'Ambulans'              => 'text-pink-700 bg-pink-50',
                        'PLN'                   => 'text-yellow-700 bg-yellow-50',
                        'PDAM'                  => 'text-cyan-700 bg-cyan-50',
                        default                 => 'text-gray-700 bg-gray-50',
                    };
                    $lastKat = $ec->kategori;
                @endphp
                <div class="px-4 py-1.5 {{ $katColor }} border-b border-gray-100">
                    <p class="text-[10px] font-bold uppercase tracking-wide">{{ $ec->kategori }}</p>
                </div>
                @endif
                <div class="px-4 py-2.5 border-b border-gray-50 hover:bg-gray-50 transition-colors">
                    <p class="text-xs font-semibold text-gray-800 leading-snug">{{ $ec->nama }}</p>
                    @if($ec->alamat)
                    <p class="text-[10px] text-gray-400 mt-0.5 leading-snug">{{ $ec->alamat }}</p>
                    @endif
                    <div class="flex items-center gap-3 mt-1">
                        @if($ec->telp)
                        <a href="tel:{{ $ec->telp }}"
                           class="flex items-center gap-1 text-[10px] text-[#1a5c2e] hover:underline font-semibold">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                            </svg>
                            {{ $ec->telp }}
                        </a>
                        @endif
                        @if($ec->no_wa)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $ec->no_wa) }}" target="_blank"
                           class="flex items-center gap-1 text-[10px] text-green-600 hover:underline font-semibold">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            WA
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endauth

    {{-- ── Footer ── --}}
    <footer class="border-t border-gray-200 bg-white mt-8">
        <div class="px-5 py-4 flex items-center justify-between text-xs text-gray-400">
            <span>© {{ date('Y') }} AMS — Madison Park. All rights reserved.</span>
            <span>Powered by Apartement Management System</span>
        </div>
    </footer>

</body>
</html>
